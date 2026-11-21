<?php
require_once("config.php");

if ($conn->connect_error) die("Ошибка подключения: " . $conn->connect_error);

// Получаем параметры фильтрации
$search        = trim((string)($_POST['search'] ?? $_GET['search'] ?? ''));
$grade_filter  = (int)($_POST['grade_filter'] ?? $_GET['grade_filter'] ?? 0);
$class_filter  = trim((string)($_POST['class_filter'] ?? $_GET['class_filter'] ?? ''));
$gender_filter = trim((string)($_POST['gender_filter'] ?? $_GET['gender_filter'] ?? ''));

// Приводим пол к единому виду (М/male/1 -> М, Ж/female/0 -> Ж)
if ($gender_filter === 'male' || $gender_filter === '1' || $gender_filter === 'М') {
    $gender_filter = 'М'; 
} elseif ($gender_filter === 'female' || $gender_filter === '0' || $gender_filter === 'Ж') {
    $gender_filter = 'Ж';
} else {
    $gender_filter = ''; 
}

// Пагинация
$perPage = 20;
$page = isset($_GET['page_num']) ? max(1, (int)$_GET['page_num']) : 1;
$offset = ($page - 1) * $perPage;

// ============================================================
// 1. СТРОИМ WHERE ДЛЯ USERS
// ============================================================
$where = [];
$params = [];
$types = "";

if (!empty($search)) {
    $where[] = "(u.surname LIKE ? OR u.name LIKE ?)";
    $sp = "%$search%";
    $params[] = $sp;
    $params[] = $sp;
    $types .= "ss";
}
if ($grade_filter > 0) {
    $where[] = "u.grade = ?";
    $params[] = $grade_filter;
    $types .= "i";
}
if (!empty($class_filter)) {
    $where[] = "u.class = ?";
    $params[] = $class_filter;
    $types .= "s";
}
if (!empty($gender_filter)) {
    $where[] = "u.gender = ?";
    $params[] = $gender_filter;
    $types .= "s";
}

$whereSQL = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// ============================================================
// 2. ЗАПРОС 1 — ТОЛЬКО ученики
// ============================================================
$sqlUsers = "SELECT u.id, u.surname, u.name, u.patronymic, u.grade, u.class, u.gender
             FROM users u
             $whereSQL
             ORDER BY u.id";
$stmt = $conn->prepare($sqlUsers);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$resUsers = $stmt->get_result();
$users = [];
while ($row = $resUsers->fetch_assoc()) {
    $users[$row['id']] = $row;
}
$stmt->close();

// ============================================================
// 3. АГРЕГАЦИЯ VISITS
// ============================================================
$visitsByUser = [];
$resV = $conn->query("
    SELECT daily.user_id,
           COUNT(*) AS total,
           SUM(daily.visit IN ('visited', 'late')) AS visited
    FROM (
        SELECT v.user_id, v.visit
        FROM visits v
        JOIN (
            SELECT user_id, DATE(`time`) AS visit_day, MAX(visit_id) AS visit_id
            FROM visits
            WHERE user_id IS NOT NULL
            GROUP BY user_id, DATE(`time`)
        ) latest ON latest.visit_id = v.visit_id
    ) daily
    GROUP BY daily.user_id
");
if ($resV) {
    while ($row = $resV->fetch_assoc()) {
        $total = (int)$row['total'];
        $visited = (int)$row['visited'];
        $visitsByUser[$row['user_id']] = $total > 0
            ? round($visited / $total * 100, 2)
            : 0;
    }
    $resV->free();
}

// ============================================================
// 4. АГРЕГАЦИЯ GRADES
// ============================================================
$gradesByUser = [];
$resG = $conn->query("
    SELECT user_id, AVG(CAST(grade_value AS DECIMAL(5,2))) AS avg_g
    FROM grades
    WHERE user_id IS NOT NULL
      AND grade_value REGEXP '^[0-9]+$'
    GROUP BY user_id
");
if ($resG) {
    while ($row = $resG->fetch_assoc()) {
        $gradesByUser[$row['user_id']] = round((float)$row['avg_g'], 2);
    }
    $resG->free();
}

// ============================================================
// 5. ОБЪЕДИНЯЕМ
// ============================================================
$allStudents = [];
foreach ($users as $id => $u) {
    $attendance = $visitsByUser[$id] ?? 0;
    $avg_grade  = $gradesByUser[$id] ?? 0;

    $rating = $avg_grade > 0
        ? round(($attendance + $avg_grade * 10) / 2, 2)
        : round($attendance / 2, 2);

    $u['attendance'] = $attendance;
    $u['avg_grade']  = $avg_grade;
    $u['rating']     = $rating;
    $allStudents[]   = $u;
}

usort($allStudents, static function ($a, $b) {
    $ratingOrder = $b['rating'] <=> $a['rating'];
    if ($ratingOrder !== 0) return $ratingOrder;
    $surnameOrder = strcasecmp($a['surname'], $b['surname']);
    return $surnameOrder !== 0 ? $surnameOrder : strcasecmp($a['name'], $b['name']);
});

// ============================================================
// 6. СТАТУСЫ
// ============================================================
foreach ($allStudents as &$s) {
    $avg = (float)$s['avg_grade'];
    if ($avg >= 8) {
        $s['status'] = 'excellent'; $s['status_text'] = 'Отличник';
        $s['status_class'] = 'badge-success'; $s['bar_class'] = 'green';
    } elseif ($avg >= 6) {
        $s['status'] = 'good'; $s['status_text'] = 'Хорошист';
        $s['status_class'] = 'badge-info'; $s['bar_class'] = 'green';
    } elseif ($avg >= 4) {
        $s['status'] = 'average'; $s['status_text'] = 'Средний';
        $s['status_class'] = 'badge-warning'; $s['bar_class'] = 'orange';
    } elseif ($avg >= 2) {
        $s['status'] = 'struggling'; $s['status_text'] = 'Отстающий';
        $s['status_class'] = 'badge-warning'; $s['bar_class'] = 'orange';
    } else {
        $s['status'] = 'critical'; $s['status_text'] = 'Критический';
        $s['status_class'] = 'badge-danger'; $s['bar_class'] = 'red';
    }
}
unset($s);

// ============================================================
// 7. ПАГИНАЦИЯ
// ============================================================
$totalStudents = count($allStudents);
$totalPages    = $totalStudents > 0 ? (int)ceil($totalStudents / $perPage) : 1;
$page          = min($page, $totalPages);
$offset        = ($page - 1) * $perPage;
$pageStudents  = array_slice($allStudents, $offset, $perPage);

date_default_timezone_set("Asia/Dushanbe");
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Рейтинг учеников</title>
    <link rel="stylesheet" href="css/bootstrap.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-light: #818cf8;
            --secondary: #7c3aed;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --text-light: #9ca3af;
            --bg-main: #f9fafb;
            --bg-card: #ffffff;
            --border: #e5e7eb;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.1);
            --shadow-lg: 0 8px 24px rgba(0,0,0,0.12);
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            margin: 0;
            padding: 30px 20px;
            color: var(--text-primary);
            min-height: 100vh;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .card {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            padding: 30px;
            margin-bottom: 20px;
            animation: cardIn 0.5s cubic-bezier(0.16, 1, 0.3, 1);
            border: 1px solid rgba(229, 231, 235, 0.5);
        }
        
        @keyframes cardIn {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.98);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f3f4f6;
            margin-bottom: 25px;
        }
        
        .card-header h2 {
            margin: 0;
            font-size: 24px;
            font-weight: 800;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 12px;
            letter-spacing: -0.5px;
        }
        
        .card-header h2 i {
            color: var(--primary);
            font-size: 28px;
        }
        
        .stats {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .stat-item {
            background: #f9fafb;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }
        
        .stat-item span {
            background: var(--primary);
            color: #fff;
            padding: 2px 10px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 13px;
        }
        
        .filter-wrap {
            background: linear-gradient(135deg, #f8f9fc, #f3f4f6);
            padding: 20px;
            border-radius: var(--radius-md);
            margin-bottom: 25px;
            border: 1px solid var(--border);
        }
        
        .filter-wrap .row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: flex-end;
        }
        
        .filter-group {
            flex: 1 1 180px;
            min-width: 150px;
        }
        
        .filter-group label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-wrapper i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 14px;
            pointer-events: none;
            transition: var(--transition);
        }
        
        .filter-wrap input,
        .filter-wrap select {
            width: 100%;
            padding: 8px 12px 8px 38px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 14px;
            background: #fff;
            height: 38px;
            transition: var(--transition);
            color: var(--text-primary);
            font-family: 'Inter', sans-serif;
        }
        
        .filter-wrap input:focus,
        .filter-wrap select:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        .filter-wrap select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 35px;
            cursor: pointer;
        }
        
        .btn-group {
            display: flex;
            gap: 8px;
            flex: 0 0 auto;
        }
        
        .btn {
            padding: 8px 20px;
            border-radius: var(--radius-sm);
            border: none;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            height: 38px;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            font-family: 'Inter', sans-serif;
            letter-spacing: 0.3px;
        }
        
        .btn-primary {
            background: var(--primary);
            color: #fff;
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.3);
        }
        
        .btn-primary:hover {
            background: #4338ca;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.4);
        }
        
        .btn-secondary {
            background: #fff;
            color: var(--text-primary);
            border: 1px solid var(--border);
        }
        
        .btn-secondary:hover {
            background: #f9fafb;
            transform: translateY(-2px);
        }
        
        .table-responsive {
            overflow-x: auto;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            min-width: 750px;
        }
        
        table th {
            background: linear-gradient(135deg, #f8f9fc, #f3f4f6);
            color: var(--text-secondary);
            font-weight: 600;
            padding: 12px 15px;
            text-align: center;
            border-bottom: 2px solid var(--border);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }
        
        table td {
            padding: 14px 15px;
            text-align: center;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
            font-size: 14px;
            transition: var(--transition);
        }
        
        table tbody tr {
            transition: var(--transition);
        }
        
        table tbody tr:hover {
            background: #f9fafb;
            transform: scale(1.01);
            box-shadow: var(--shadow-sm);
        }
        
        .rank {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            font-weight: 700;
            font-size: 13px;
            color: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        
        .rank-1 { background: linear-gradient(135deg, #fbbf24, #f59e0b); }
        .rank-2 { background: linear-gradient(135deg, #e5e7eb, #9ca3af); }
        .rank-3 { background: linear-gradient(135deg, #fb923c, #ea580c); }
        .rank-other { background: #f3f4f6; color: var(--text-secondary); }
        
        .badge {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        
        .badge-class { background: #eef2ff; color: #4f46e5; }
        .badge-success { background: #f0fdf4; color: #16a34a; }
        .badge-warning { background: #fffbeb; color: #d97706; }
        .badge-danger { background: #fef2f2; color: #dc2626; }
        .badge-info { background: #eff6ff; color: #2563eb; }
        
        .metric {
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
        }
        
        .metric .value {
            font-weight: 700;
            font-size: 11px;
            min-width: 30px;
            color: var(--text-primary);
        }
        
        .bar {
            height: 6px;
            border-radius: 3px;
            background: #f3f4f6;
            overflow: hidden;
            width: 70px;
            flex-shrink: 0;
            position: relative;
        }
        
        .bar .fill {
            height: 100%;
            border-radius: 3px;
            transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .bar .fill.green { background: linear-gradient(90deg, #10b981, #059669); }
        .bar .fill.orange { background: linear-gradient(90deg, #f59e0b, #d97706); }
        .bar .fill.red { background: linear-gradient(90deg, #ef4444, #dc2626); }
        
        .pagination-wrap {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            padding-top: 20px;
            border-top: 2px solid #f3f4f6;
            margin-top: 20px;
        }
        
        .pagination-info {
            font-size: 13px;
            color: var(--text-secondary);
        }
        
        .pagination-info strong {
            color: var(--text-primary);
            font-weight: 700;
        }
        
        .pagination-links {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }
        
        .pagination-links a,
        .pagination-links span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            height: 36px;
            padding: 0 12px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            color: var(--text-secondary);
            background: #fff;
            border: 1px solid var(--border);
            transition: var(--transition);
        }
        
        .pagination-links a:hover {
            background: #f9fafb;
            border-color: #d1d5db;
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }
        
        .pagination-links .active {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.3);
        }
        
        .pagination-links .disabled {
            opacity: 0.4;
            cursor: not-allowed;
            pointer-events: none;
        }
        
        .empty {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-light);
        }
        
        .empty i {
            font-size: 48px;
            margin-bottom: 20px;
            color: #d1d5db;
        }
        
        .empty h5 {
            margin: 0;
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 18px;
        }
        
        .empty p {
            margin: 8px 0 0 0;
            font-size: 14px;
        }

        .card-header h2 i {
            color: var(--primary);
            font-size: 28px;
            animation: iconBounce 2s ease-in-out infinite;
        }

        @keyframes iconBounce {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            25% { transform: translateY(-5px) rotate(-5deg); }
            50% { transform: translateY(0) rotate(0deg); }
            75% { transform: translateY(-3px) rotate(5deg); }
        }
        
        @media (max-width: 768px) {
            body { padding: 15px 10px; }
            .card { padding: 20px 15px; }
            .filter-wrap .row { flex-direction: column; }
            .filter-group { flex: 1 1 100%; min-width: 100%; }
            .btn-group { width: 100%; }
            .btn-group .btn { flex: 1; }
            table { font-size: 13px; min-width: 600px; }
            table th, table td { padding: 10px 8px; }
            .bar { width: 60px; }
            .pagination-wrap { flex-direction: column; text-align: center; }
            .pagination-links { justify-content: center; }
            .card-header { flex-direction: column; align-items: flex-start; }
            .stats { width: 100%; }
        }
        
        @media (max-width: 480px) {
            .card { padding: 15px 10px; }
            .card-header h2 { font-size: 20px; }
            .rank { width: 28px; height: 28px; font-size: 12px; }
            .metric .value { font-size: 12px; min-width: 35px; }
            .bar { width: 50px; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <!-- ШАПКА -->
        <div class="card-header">
            <h2>
                <i class="fas fa-trophy"></i>
                Рейтинг учеников
            </h2>
            <div class="stats">
                <div class="stat-item">
                    <i class="fas fa-users"></i>
                    Всего: <span><?= $totalStudents ?></span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-eye"></i>
                    Показано: <span><?= count($pageStudents) ?></span>
                </div>
            </div>
        </div>

        <!-- ФИЛЬТРЫ -->
        <div class="filter-wrap">
            <!-- Отправка формы методом GET для сохранения параметров в URL -->
            <form action="" method="GET" id="filterForm">
                <input type="hidden" name="page" value="18">
                <div class="row">
                    <div class="filter-group">
                        <label>Поиск</label>
                        <div class="input-wrapper">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" placeholder="Фамилия или имя" 
                                   value="<?= htmlspecialchars($search) ?>">
                        </div>
                    </div>
                    <div class="filter-group">
                        <label>Класс</label>
                        <div class="input-wrapper">
                            <i class="fas fa-graduation-cap"></i>
                            <select name="grade_filter">
                                <option value="">Все</option>
                                <?php for ($i = 5; $i <= 11; $i++): ?>
                                    <option value="<?= $i ?>" <?= $grade_filter == $i ? 'selected' : '' ?>><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="filter-group">
                        <label>Группа</label>
                        <div class="input-wrapper">
                            <i class="fas fa-users"></i>
                            <select name="class_filter">
                                <option value="">Все</option>
                                <?php foreach (['А', 'Б', 'В'] as $c): ?>
                                    <option value="<?= $c ?>" <?= $class_filter == $c ? 'selected' : '' ?>><?= $c ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="filter-group">
                        <label>Пол</label>
                        <div class="input-wrapper">
                            <i class="fas fa-venus-mars"></i>
                            <select name="gender_filter">
                                <option value="">Все</option>
                                <option value="М" <?= $gender_filter == 'М' ? 'selected' : '' ?>>М</option>
                                <option value="Ж" <?= $gender_filter == 'Ж' ? 'selected' : '' ?>>Ж</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Найти
                        </button>
                        <a href="?page=18" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Сброс
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- ТАБЛИЦА -->
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th style="width:50px;">#</th>
                        <th style="text-align:left;">Ученик</th>
                        <th style="width:70px;">Кл.</th>
                        <th style="width:150px;">Посещаемость</th>
                        <th style="width:110px;">Ср. оценка</th>
                        <th style="width:120px;">Рейтинг</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($pageStudents)): ?>
                        <?php 
                        $rank = $offset + 1;
                        foreach ($pageStudents as $row): 
                            $fio = $row["surname"] . " " . $row["name"];
                            $class = $row["grade"] . $row["class"];
                            $attendance = (float)($row["attendance"] ?? 0);
                            $avg_grade = (float)($row["avg_grade"] ?? 0);
                            $rating = (float)($row["rating"] ?? 0);

                            if ($rank == 1) { $rankClass = "rank-1"; $rankLabel = "1"; }
                            elseif ($rank == 2) { $rankClass = "rank-2"; $rankLabel = "2"; }
                            elseif ($rank == 3) { $rankClass = "rank-3"; $rankLabel = "3"; }
                            else { $rankClass = "rank-other"; $rankLabel = $rank; }
                        ?>
                        <tr>
                            <td><span class="rank <?= $rankClass ?>"><?= $rankLabel ?></span></td>
                            <td style="text-align:left; font-weight:500;">
                                <?= htmlspecialchars($fio) ?>
                            </td>
                            <td><span class="badge badge-class"><?= htmlspecialchars($class) ?></span></td>
                            <td>
                                <div class="metric">
                                    <span class="value"><?= number_format($attendance, 0) ?>%</span>
                                    <div class="bar"><div class="fill <?= $row['bar_class'] ?>" style="width: <?= min($attendance, 100) ?>%;"></div></div>
                                </div>
                            </td>
                            <td><strong><?= number_format($avg_grade, 1) ?></strong></td>
                            <td>
                                <div class="metric">
                                    <span class="value"><?= number_format($rating, 0) ?></span>
                                    <div class="bar"><div class="fill <?= $row['bar_class'] ?>" style="width: <?= min($rating, 70) ?>%;"></div></div>
                                </div>
                            </td>
                        </tr>
                        <?php $rank++; endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty">
                                    <i class="fas fa-inbox"></i>
                                    <h5>Нет данных</h5>
                                    <p>Попробуйте изменить параметры фильтра</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- ПАГИНАЦИЯ -->
        <div class="pagination-wrap">
            <div class="pagination-info">
                Показано <strong><?= $offset + 1 ?></strong> – 
                <strong><?= min($offset + $perPage, $totalStudents) ?></strong> 
                из <strong><?= $totalStudents ?></strong> учеников
            </div>
            <div class="pagination-links">
                <?php 
                // Базовые параметры для пагинации
                $paginationQuery = "page=18&search=" . urlencode($search) . 
                                   "&grade_filter=" . $grade_filter . 
                                   "&class_filter=" . urlencode($class_filter) . 
                                   "&gender_filter=" . urlencode($gender_filter);
                ?>
                
                <?php if ($page > 1): ?>
                    <a href="?<?= $paginationQuery ?>&page_num=<?= $page - 1 ?>"><i class="fas fa-angle-left"></i></a>
                <?php else: ?>
                    <span class="disabled"><i class="fas fa-angle-left"></i></span>
                <?php endif; ?>

                <?php
                $start = max(1, $page - 2);
                $end = min($totalPages, $page + 2);
                
                for ($i = $start; $i <= $end; $i++) {
                    if ($i == $page) {
                        echo '<span class="active">' . $i . '</span>';
                    } else {
                        echo '<a href="?' . $paginationQuery . '&page_num=' . $i . '">' . $i . '</a>';
                    }
                }
                ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?<?= $paginationQuery ?>&page_num=<?= $page + 1 ?>"><i class="fas fa-angle-right"></i></a>
                <?php else: ?>
                    <span class="disabled"><i class="fas fa-angle-right"></i></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="js/bootstrap.bundle.js"></script>
</body>
</html>