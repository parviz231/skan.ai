<?php
/* ============================================================
   achievements.php — Достижения учеников (?page=20)
   ============================================================ */

require_once("config.php");
if ($conn->connect_error) die("Ошибка БД: " . $conn->connect_error);
date_default_timezone_set("Asia/Dushanbe");

/* ---------- Фильтры (из POST) ---------- */
$grade            = isset($_POST['grade'])            && $_POST['grade']            !== '' ? (int)$_POST['grade']     : 0;
$class            = isset($_POST['class'])            && $_POST['class']            !== '' ? trim($_POST['class'])    : '';
$search_student   = isset($_POST['search_student'])   ? trim($_POST['search_student'])   : '';
$school_year      = isset($_POST['school_year'])      ? trim($_POST['school_year'])      : '';
$competition_type = isset($_POST['competition_type']) ? trim($_POST['competition_type']) : '';
$place            = isset($_POST['place'])            ? trim($_POST['place'])            : '';
$competition_name = isset($_POST['competition_name']) ? trim($_POST['competition_name']) : '';

/* ---------- Пагинация ---------- */
$perPage = 20;
$pageNum = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$offset  = ($pageNum - 1) * $perPage;

/* ---------- WHERE ---------- */
$where  = [];
$params = [];
$types  = "";

if ($grade > 0) {
    $where[]  = "u.grade = ?";
    $params[] = $grade;
    $types   .= "i";
}
if ($class !== '') {
    $where[]  = "u.class = ?";
    $params[] = $class;
    $types   .= "s";
}
if ($search_student !== '') {
    $where[]  = "(u.surname LIKE ? OR u.name LIKE ?)";
    $s = "%$search_student%";
    $params[] = $s; $params[] = $s;
    $types   .= "ss";
}
if ($school_year !== '') {
    $where[]  = "a.school_year = ?";
    $params[] = $school_year;
    $types   .= "s";
}
if ($competition_type !== '') {
    $where[]  = "a.competition_type = ?";
    $params[] = $competition_type;
    $types   .= "s";
}
if ($place !== '') {
    // LIKE — чтобы "1" находил и "1 место", и "1-е место"
    $where[]  = "a.place LIKE ?";
    $params[] = "%$place%";
    $types   .= "s";
}
if ($competition_name !== '') {
    $where[]  = "a.competition_name LIKE ?";
    $params[] = "%$competition_name%";
    $types   .= "s";
}

$whereSQL = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

/* ---------- Общее количество ---------- */
$countSQL = "
    SELECT COUNT(*) AS total
    FROM achievements a
    JOIN users u ON a.student_id = u.id
    $whereSQL
";
if (!empty($params)) {
    $countStmt = $conn->prepare($countSQL);
    $countStmt->bind_param($types, ...$params);
    $countStmt->execute();
    $total_count = (int)$countStmt->get_result()->fetch_assoc()['total'];
    $countStmt->close();
} else {
    $total_count = (int)$conn->query($countSQL)->fetch_assoc()['total'];
}

$totalPages = $total_count > 0 ? (int)ceil($total_count / $perPage) : 1;
if ($pageNum > $totalPages) {
    $pageNum = $totalPages;
    $offset  = ($pageNum - 1) * $perPage;
}

/* ---------- Данные страницы ---------- */
$query = "
    SELECT 
        a.id AS ach_id,
        u.name, u.surname, u.patronymic, u.grade, u.class,
        a.competition_name, a.competition_type, a.place, a.school_year
    FROM achievements a
    JOIN users u ON a.student_id = u.id
    $whereSQL
    ORDER BY u.surname, u.name
    LIMIT $offset, $perPage
";

if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($query);
}

/* ---------- Утилита: ссылка пагинации с сохранением POST-фильтров ---------- */
function pageUrl($p) {
    $params = $_POST;
    $params['page'] = 20;
    $params['p']    = $p;
    return '?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Достижения учеников</title>
<link rel="stylesheet" href="css/bootstrap.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    :root {
        --primary: #4f46e5;
        --primary-light: #818cf8;
        --primary-dark: #3730a3;
        --secondary: #7c3aed;
        --success: #10b981;
        --success-dark: #059669;
        --danger: #ef4444;
        --warning: #f59e0b;
        --text-primary: #1f2937;
        --text-secondary: #6b7280;
        --text-light: #9ca3af;
        --bg-main: #f9fafb;
        --bg-card: #ffffff;
        --border-color: #e5e7eb;
        --border-focus: #4f46e5;
        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        --radius-sm: 6px;
        --radius-md: 8px;
        --radius-lg: 12px;
        --radius-xl: 16px;
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        background: linear-gradient(180deg, #f9fafb 0%, #f3f4f6 100%);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        min-height: 100vh;
        padding: 30px 20px;
        position: relative;
        overflow-x: hidden;
    }
    body::before {
        content: '';
        position: fixed; top: 0; left: 0; right: 0; height: 3px;
        background: linear-gradient(90deg, #4f46e5, #7c3aed, #10b981);
        z-index: 1000;
        animation: gradientShift 3s ease infinite;
        background-size: 200% 200%;
    }
    @keyframes gradientShift {
        0%, 100% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
    }
    .container { max-width: 1400px; margin: 0 auto; position: relative; z-index: 1; }
    .card {
        background: var(--bg-card);
        border-radius: var(--radius-xl);
        box-shadow: var(--shadow-xl);
        padding: 30px;
        margin-bottom: 30px;
        animation: slideInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        border: 1px solid rgba(229, 231, 235, 0.5);
        position: relative;
        overflow: hidden;
    }
    .card::before {
        content: '';
        position: absolute; top: 0; left: 0; right: 0; height: 4px;
        background: linear-gradient(90deg, #4f46e5, #7c3aed);
        opacity: 0; transition: var(--transition);
    }
    .card:hover::before { opacity: 1; }
    @keyframes slideInUp {
        from { opacity: 0; transform: translateY(40px) scale(0.95); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }
    .card-header {
        padding: 0 0 25px 0;
        border-bottom: 2px solid #f3f4f6;
        margin-bottom: 25px;
        display: flex; justify-content: space-between; align-items: center;
        flex-wrap: wrap; gap: 20px;
    }
    .card-header h2 {
        margin: 0; font-size: 28px; font-weight: 800;
        color: var(--text-primary);
        display: flex; align-items: center; gap: 15px;
        letter-spacing: -0.5px;
    }
    .card-header h2 .icon-wrapper {
        width: 45px; height: 45px;
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        border-radius: var(--radius-lg);
        display: flex; align-items: center; justify-content: center;
        color: white; font-size: 20px;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        animation: iconBounce 2s ease-in-out infinite;
    }
    @keyframes iconBounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-5px); }
    }
    .card-header .stats .stat-badge {
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: white; padding: 8px 16px; border-radius: 50px;
        font-weight: 600; font-size: 14px;
        display: flex; align-items: center; gap: 8px;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
    }
    .filter-wrap {
        background: linear-gradient(135deg, #f9fafb, #f3f4f6);
        padding: 20px;
        border-radius: var(--radius-lg);
        margin-bottom: 25px;
        border: 1px solid var(--border-color);
        animation: fadeInUp 0.5s ease 0.2s forwards;
        opacity: 0;
    }
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .filter-wrap form { display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end; }
    .filter-wrap .filter-group {
        display: flex; flex-direction: column; gap: 6px;
        flex: 1 1 160px; min-width: 140px;
    }
    .filter-wrap label {
        font-size: 11px; font-weight: 600; color: var(--text-secondary);
        text-transform: uppercase; letter-spacing: 0.8px;
    }
    .filter-wrap select, .filter-wrap input {
        padding: 10px 15px;
        border: 2px solid var(--border-color);
        border-radius: var(--radius-md);
        font-size: 14px; background: white;
        height: 42px; transition: var(--transition);
        color: var(--text-primary);
        font-family: 'Inter', sans-serif; width: 100%;
    }
    .filter-wrap select:hover, .filter-wrap input:hover {
        border-color: #d1d5db; box-shadow: var(--shadow-sm);
    }
    .filter-wrap select:focus, .filter-wrap input:focus {
        border-color: var(--border-focus); outline: none;
        box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
    }
    .filter-wrap select {
        appearance: none; -webkit-appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: right 15px center;
        padding-right: 40px; cursor: pointer;
    }
    .filter-wrap .btn-group { display: flex; gap: 8px; flex: 0 0 auto; }
    .btn {
        font-size: 14px; padding: 10px 20px; border-radius: var(--radius-md);
        border: none; cursor: pointer; transition: var(--transition);
        text-decoration: none;
        display: inline-flex; align-items: center; justify-content: center;
        gap: 8px; font-weight: 600; height: 42px;
        letter-spacing: 0.3px; font-family: 'Inter', sans-serif;
        position: relative; overflow: hidden;
    }
    .btn-primary {
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: #fff; box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
    }
    .btn-primary:hover {
        box-shadow: 0 8px 25px rgba(79, 70, 229, 0.4);
        transform: translateY(-2px); color: white;
    }
    .btn-danger {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: #fff; box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
    }
    .btn-danger:hover {
        box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4);
        transform: translateY(-2px); color: white;
    }
    .btn-success {
        background: linear-gradient(135deg, #10b981, #059669);
        color: #fff; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
    }
    .btn-success:hover {
        box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
        transform: translateY(-2px); color: white;
    }
    .btn-clear {
        background: #fff; color: var(--text-primary);
        border: 2px solid var(--border-color);
    }
    .btn-clear:hover {
        background: #f9fafb; border-color: #d1d5db;
        transform: translateY(-2px); box-shadow: var(--shadow-md);
        color: var(--text-primary);
    }
    .btn-sm { padding: 6px 14px; font-size: 13px; height: 32px; gap: 5px; }
    .btn-add {
        background: linear-gradient(135deg, #10b981, #059669);
        color: #fff; padding: 12px 24px; border-radius: var(--radius-md);
        border: none; font-size: 15px; font-weight: 600;
        text-decoration: none;
        display: inline-flex; align-items: center; gap: 8px;
        transition: var(--transition);
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
    }
    .btn-add:hover {
        box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
        transform: translateY(-2px); color: white;
    }
    .table-responsive {
        overflow-x: auto; -webkit-overflow-scrolling: touch;
        animation: fadeInUp 0.5s ease 0.3s forwards; opacity: 0;
    }
    table {
        width: 100%; border-collapse: collapse;
        font-size: 14px; min-width: 800px;
    }
    table th {
        background: linear-gradient(135deg, #f9fafb, #f3f4f6);
        color: var(--text-secondary); font-weight: 600;
        padding: 14px 10px; text-align: center;
        border-bottom: 2px solid var(--border-color);
        white-space: nowrap; font-size: 12px;
        text-transform: uppercase; letter-spacing: 0.5px;
        position: sticky; top: 0; z-index: 10;
    }
    table td {
        padding: 14px 10px; text-align: center;
        border-bottom: 1px solid #f3f4f6;
        vertical-align: middle; transition: var(--transition);
    }
    table tbody tr { transition: var(--transition); }
    table tbody tr:hover { background: #f9fafb; transform: translateX(2px); }
    .badge-place {
        display: inline-block; padding: 6px 12px;
        border-radius: 50px; font-size: 12px;
        font-weight: 600; letter-spacing: 0.3px;
    }
    .badge-1 { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #92400e; }
    .badge-2 { background: linear-gradient(135deg, #f3f4f6, #e5e7eb); color: #374151; }
    .badge-3 { background: linear-gradient(135deg, #fed7aa, #fdba74); color: #9a3412; }
    .badge-cert { background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #065f46; }
    .badge-part { background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #1e40af; }
    .badge-type {
        display: inline-block; padding: 6px 12px;
        border-radius: 50px; font-size: 11px; font-weight: 500;
        background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
        color: #374151;
    }
    .badge-primary {
        display: inline-block; padding: 6px 12px;
        border-radius: 50px; font-size: 12px; font-weight: 600;
        background: #eef2ff; color: #4f46e5;
    }
    .empty-state {
        text-align: center; padding: 60px 20px;
        color: var(--text-light);
    }
    .empty-state .empty-icon {
        font-size: 48px; margin-bottom: 20px; color: #d1d5db;
        animation: float 3s ease-in-out infinite;
    }
    @keyframes float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-10px); }
    }
    .empty-state h5 {
        margin: 0 0 10px 0; font-weight: 600;
        color: var(--text-secondary); font-size: 18px;
    }
    .empty-state p { margin: 0; font-size: 14px; }
    .table-footer {
        display: flex; justify-content: space-between;
        align-items: center; flex-wrap: wrap;
        gap: 15px; padding-top: 20px;
        border-top: 2px solid #f3f4f6; margin-top: 20px;
        animation: fadeInUp 0.5s ease 0.4s forwards; opacity: 0;
    }
    .table-footer .info {
        font-size: 14px; color: var(--text-secondary);
        display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
    }
    .table-footer .info strong { color: var(--text-primary); font-weight: 700; }
    .table-footer .info .filter-tag {
        background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
        padding: 6px 12px; border-radius: 50px;
        font-size: 12px; color: #3730a3; font-weight: 600;
        display: flex; align-items: center; gap: 5px;
    }
    .action-buttons { display: flex; gap: 5px; justify-content: center; }
    .pagination-wrap {
        display: flex; justify-content: center; gap: 6px;
        padding-top: 20px; flex-wrap: wrap;
    }
    @media (max-width: 768px) {
        body { padding: 20px 10px; }
        .card { padding: 20px 15px; border-radius: var(--radius-lg); }
        .card-header { flex-direction: column; align-items: flex-start; }
        .filter-wrap form { flex-direction: column; align-items: stretch; }
        .filter-wrap .filter-group { flex: 1 1 100%; min-width: 100%; }
        .filter-wrap .btn-group { flex-direction: column; width: 100%; }
        .filter-wrap .btn-group .btn { width: 100%; }
        table { font-size: 13px; min-width: 600px; }
        .table-footer { flex-direction: column; align-items: flex-start; }
        .btn-add { width: 100%; justify-content: center; }
        .action-buttons { flex-direction: column; gap: 3px; }
    }
    @media (max-width: 480px) {
        .card { padding: 15px 10px; }
        .card-header h2 { font-size: 20px; }
        .table-footer .info { flex-direction: column; align-items: flex-start; gap: 5px; }
    }
</style>
</head>
<body>

<div class="container">
<div class="card">

    <!-- Шапка -->
    <div class="card-header">
        <h2>
            <span class="icon-wrapper"><i class="fas fa-trophy"></i></span>
            Достижения учеников
        </h2>
        <div class="stats">
            <span class="stat-badge">
                <i class="fas fa-medal"></i>
                Всего: <?= $total_count ?>
            </span>
        </div>
    </div>

    <!-- Фильтры -->
    <div class="filter-wrap">
        <form action="?page=20" method="POST" id="filterForm">
            <div class="filter-group">
                <label><i class="fas fa-graduation-cap"></i> Класс</label>
                <select name="grade">
                    <option value="">Все</option>
                    <?php for ($i = 5; $i <= 11; $i++): ?>
                        <option value="<?= $i ?>" <?= ($grade == $i) ? 'selected' : '' ?>><?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="filter-group">
                <label><i class="fas fa-layer-group"></i> Группа</label>
                <select name="class">
                    <option value="">Все</option>
                    <?php foreach (['А','Б','В'] as $l): ?>
                        <option value="<?= $l ?>" <?= ($class === $l) ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label><i class="fas fa-search"></i> Ученик</label>
                <input type="text" name="search_student" placeholder="Фамилия или имя"
                       value="<?= htmlspecialchars($search_student) ?>">
            </div>
            <div class="filter-group">
                <label><i class="fas fa-calendar"></i> Учебный год</label>
                <select name="school_year">
                    <option value="">Все</option>
                    <?php
                    $current_year = (int)date('Y');
                    for ($y = $current_year - 3; $y <= $current_year + 1; $y++) {
                        $label = "$y/" . ($y + 1);
                        $sel   = ($school_year === $label) ? 'selected' : '';
                        echo "<option value=\"$label\" $sel>$label</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="filter-group">
                <label><i class="fas fa-flag"></i> Тип</label>
                <select name="competition_type">
                    <option value="">Все</option>
                    <?php
                    $types = ['Олимпиада', 'Конкурс', 'Спорт', 'Фестиваль', 'Конференция'];
                    foreach ($types as $t):
                        $sel = ($competition_type === $t) ? 'selected' : '';
                    ?>
                        <option value="<?= $t ?>" <?= $sel ?>><?= htmlspecialchars($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label><i class="fas fa-award"></i> Место</label>
                <select name="place">
                    <option value="">Все</option>
                    <?php
                    $places = [
                        '1'    => '1 место',
                        '2'    => '2 место',
                        '3'    => '3 место',
                        'Гран' => 'Гран-при',
                    ];
                    foreach ($places as $val => $label):
                        $sel = ($place === $val) ? 'selected' : '';
                    ?>
                        <option value="<?= $val ?>" <?= $sel ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label><i class="fas fa-medal"></i> Название</label>
                <input type="text" name="competition_name" placeholder="Поиск по названию"
                       value="<?= htmlspecialchars($competition_name) ?>">
            </div>
            <div class="filter-group" style="flex:0 0 auto; min-width:auto;">
                <label>&nbsp;</label>
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Найти
                    </button>
                    <a href="?page=20" class="btn btn-clear">
                        <i class="fas fa-undo"></i> Сброс
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Таблица -->
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th style="text-align:left; min-width:150px;">Ученик</th>
                    <th style="width:70px;">Класс</th>
                    <th style="width:100px;">Год</th>
                    <th style="text-align:left; min-width:200px;">Соревнование</th>
                    <th style="width:110px;">Тип</th>
                    <th style="width:110px;">Место</th>
                    <th style="width:160px;">Действия</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <?php $i = $offset; while ($row = $result->fetch_assoc()): $i++; ?>
                    <?php
                    $place_val = $row['place'];
                    if     (stripos($place_val, '1') !== false)    $placeClass = 'badge-1';
                    elseif (stripos($place_val, '2') !== false)    $placeClass = 'badge-2';
                    elseif (stripos($place_val, '3') !== false)    $placeClass = 'badge-3';
                    elseif (stripos($place_val, 'Гран') !== false) $placeClass = 'badge-cert';
                    else                                           $placeClass = 'badge-part';
                    ?>
                    <tr>
                        <td class="text-muted"><?= $i ?></td>
                        <td style="text-align:left; font-weight:500;">
                            <?= htmlspecialchars($row['surname'] . ' ' . $row['name']) ?>
                        </td>
                        <td><span class="badge-primary"><?= (int)$row['grade'] . htmlspecialchars($row['class']) ?></span></td>
                        <td><?= htmlspecialchars($row['school_year']) ?></td>
                        <td style="text-align:left;"><?= htmlspecialchars($row['competition_name']) ?></td>
                        <td><span class="badge-type"><?= htmlspecialchars($row['competition_type']) ?></span></td>
                        <td><span class="badge-place <?= $placeClass ?>"><?= htmlspecialchars($place_val) ?></span></td>
                        <td>
                            <div class="action-buttons">
                                <a class="btn btn-success btn-sm" href="?page=22&id=<?= (int)$row['ach_id'] ?>">
                                    <i class="fas fa-edit"></i> Изменить
                                </a>
                                <a class="btn btn-danger btn-sm" href="?page=21&id=<?= (int)$row['ach_id'] ?>"
                                   onclick="return confirm('Удалить достижение?')">
                                    <i class="fas fa-trash"></i> Удалить
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8">
                        <div class="empty-state">
                            <div class="empty-icon"><i class="fas fa-trophy"></i></div>
                            <h5>Нет достижений</h5>
                            <p>Попробуйте изменить параметры фильтрации</p>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Пагинация -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination-wrap">
            <?php if ($pageNum > 1): ?>
                <a class="btn btn-clear btn-sm" href="<?= pageUrl($pageNum - 1) ?>">
                    <i class="fas fa-angle-left"></i>
                </a>
            <?php endif; ?>

            <?php
            $start = max(1, $pageNum - 2);
            $end   = min($totalPages, $pageNum + 2);
            for ($p = $start; $p <= $end; $p++):
                $cls = ($p == $pageNum) ? 'btn-primary' : 'btn-clear';
            ?>
                <a class="btn <?= $cls ?> btn-sm" href="<?= pageUrl($p) ?>"><?= $p ?></a>
            <?php endfor; ?>

            <?php if ($pageNum < $totalPages): ?>
                <a class="btn btn-clear btn-sm" href="<?= pageUrl($pageNum + 1) ?>">
                    <i class="fas fa-angle-right"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Подвал -->
    <div class="table-footer">
        <div class="info">
            Найдено: <strong><?= $total_count ?></strong> достижений
            <?php if ($grade || $class || $search_student || $school_year || $competition_type || $place || $competition_name): ?>
                <span class="filter-tag">
                    <i class="fas fa-filter"></i> Фильтры активны
                </span>
            <?php endif; ?>
        </div>
        <a href="?page=19" class="btn-add">
            <i class="fas fa-plus"></i> Добавить достижение
        </a>
    </div>

</div>
</div>

<script src="js/bootstrap.bundle.js"></script>
</body>
</html>