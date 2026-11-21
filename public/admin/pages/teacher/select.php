<?php
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['teacher_csrf_token']) || !is_string($_SESSION['teacher_csrf_token'])) {
    $_SESSION['teacher_csrf_token'] = bin2hex(random_bytes(32));
}

$subjects = [];
$subjectResult = $conn->query('SELECT id, name FROM subjects ORDER BY name');
while ($subjectRow = $subjectResult->fetch_assoc()) {
    $subjects[(int)$subjectRow['id']] = $subjectRow['name'];
}

$search = isset($_GET['search']) && is_scalar($_GET['search']) ? trim((string)$_GET['search']) : '';
$subjectInput = isset($_GET['subject_id']) && is_scalar($_GET['subject_id']) ? (string)$_GET['subject_id'] : '';
$subjectFilter = $subjectInput === '' ? null : filter_var($subjectInput, FILTER_VALIDATE_INT);
$genderFilter = isset($_GET['gender']) && is_scalar($_GET['gender']) ? (string)$_GET['gender'] : '';
$categoryFilter = isset($_GET['category']) && is_scalar($_GET['category']) ? (string)$_GET['category'] : '';
$genderFilter = in_array($genderFilter, ['male', 'female'], true) ? $genderFilter : '';
$categoryFilter = in_array($categoryFilter, ['1', '2', 'highest'], true) ? $categoryFilter : '';

$conditions = [];
$queryParams = [];
if ($search !== '') {
    $conditions[] = '(t.surname LIKE ? OR t.name LIKE ? OR t.patronymic LIKE ?)';
    $searchPattern = '%' . $search . '%';
    array_push($queryParams, $searchPattern, $searchPattern, $searchPattern);
}
if ($subjectInput !== '') {
    $conditions[] = 't.subject_id = ?';
    $queryParams[] = $subjectFilter === false ? 0 : $subjectFilter;
}
if ($genderFilter !== '') {
    $conditions[] = 't.gender = ?';
    $queryParams[] = $genderFilter;
}
if ($categoryFilter !== '') {
    $conditions[] = 't.category_of_specialty = ?';
    $queryParams[] = $categoryFilter;
}
$whereSql = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
$totalTeachers = (int)$conn->query('SELECT COUNT(*) AS total FROM teachers')->fetch_assoc()['total'];
$totalFiltered = 0;
$teacherResult = null;
$teacherError = '';
try {
    $countStmt = $conn->prepare('SELECT COUNT(*) AS total FROM teachers t' . $whereSql);
    if ($queryParams) {
        $countStmt->execute($queryParams);
    } else {
        $countStmt->execute();
    }
    $totalFiltered = (int)$countStmt->get_result()->fetch_assoc()['total'];
    $countStmt->close();

    $teacherStmt = $conn->prepare(
        'SELECT t.id, t.name, t.surname, t.patronymic, t.age, t.birth, t.gender, t.category_of_specialty, t.subject_id, s.name AS subject_name
         FROM teachers t LEFT JOIN subjects s ON s.id = t.subject_id' . $whereSql . ' ORDER BY t.surname, t.name'
    );
    if ($queryParams) {
        $teacherStmt->execute($queryParams);
    } else {
        $teacherStmt->execute();
    }
    $teacherResult = $teacherStmt->get_result();
} catch (mysqli_sql_exception $error) {
    error_log('Teacher list query failed: ' . $error->getMessage());
    $teacherError = 'Не удалось загрузить список учителей.';
}
?>

<style>
        .teacher-list-container {
            --primary: #4f46e5;
            --primary-light: #818cf8;
            --primary-dark: #3730a3;
            --secondary: #7c3aed;
            --success: #10b981;
            --success-dark: #059669;
            --danger: #ef4444;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --text-light: #9ca3af;
            --bg-main: #f9fafb;
            --bg-card: #ffffff;
            --border-color: #e5e7eb;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            --radius-sm: 6px;
            --radius-md: 8px;
            --radius-lg: 12px;
            --radius-xl: 16px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        .teacher-list-container {
            max-width: 1400px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }
        
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
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #4f46e5, #7c3aed);
            opacity: 0;
            transition: var(--transition);
        }
        
        .card:hover::before {
            opacity: 1;
        }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(40px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .card-header {
            padding: 0 0 25px 0;
            border-bottom: 2px solid #f3f4f6;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .card-header h2 {
            margin: 0;
            font-size: 28px;
            font-weight: 800;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 15px;
            letter-spacing: -0.5px;
        }
        
        .card-header h2 .icon-wrapper {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
            animation: iconBounce 2s ease-in-out infinite;
        }
        
        @keyframes iconBounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }
        
        .card-header .stat-badge {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
            padding: 8px 16px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
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
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .filter-wrap form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            flex: 1 1 160px;
            min-width: 140px;
        }
        
        .filter-group label {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        
        .filter-group input,
        .filter-group select {
            padding: 10px 15px;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: 14px;
            background: white;
            height: 42px;
            transition: var(--transition);
            color: var(--text-primary);
            font-family: 'Inter', sans-serif;
            width: 100%;
        }
        
        .filter-group input:focus,
        .filter-group select:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }
        
        .filter-group select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            padding-right: 40px;
            cursor: pointer;
        }
        
        .btn-group {
            display: flex;
            gap: 8px;
            flex: 0 0 auto;
        }
        
        .btn {
            font-size: 14px;
            padding: 10px 20px;
            border-radius: var(--radius-md);
            border: none;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-weight: 600;
            height: 42px;
            letter-spacing: 0.3px;
            font-family: 'Inter', sans-serif;
            position: relative;
            overflow: hidden;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .btn:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .btn:active {
            transform: scale(0.95);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: #fff;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
        }
        
        .btn-primary:hover {
            box-shadow: 0 8px 25px rgba(79, 70, 229, 0.4);
            transform: translateY(-2px);
            color: white;
        }
        
        .btn-secondary {
            background: #fff;
            color: var(--text-primary);
            border: 2px solid var(--border-color);
        }
        
        .btn-secondary:hover {
            background: #f9fafb;
            border-color: #d1d5db;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            color: var(--text-primary);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: #fff;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }
        
        .btn-success:hover {
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
            transform: translateY(-2px);
            color: white;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: #fff;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
        }
        
        .btn-danger:hover {
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4);
            transform: translateY(-2px);
            color: white;
        }
        
        .btn-sm {
            padding: 6px 14px;
            font-size: 13px;
            height: 32px;
            gap: 5px;
        }
        
        .table-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-lg);
            animation: fadeInUp 0.5s ease 0.3s forwards;
            opacity: 0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            min-width: 800px;
        }
        
        th {
            background: linear-gradient(135deg, #f9fafb, #f3f4f6);
            color: var(--text-secondary);
            font-weight: 600;
            padding: 14px 12px;
            text-align: center;
            border-bottom: 2px solid var(--border-color);
            white-space: nowrap;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        td {
            padding: 14px 12px;
            text-align: center;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
            transition: var(--transition);
        }
        
        tbody tr {
            transition: var(--transition);
        }
        
        tbody tr:hover {
            background: #f9fafb;
            transform: translateX(2px);
        }
        
        .badge-category {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-first {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #1e40af;
        }
        
        .badge-second {
            background: linear-gradient(135deg, #f3e8ff, #e9d5ff);
            color: #6b21a8;
        }
        
        .badge-highest {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #92400e;
        }
        
        .badge-subject {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 500;
            background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
            color: #374151;
        }
        
        .gender-m {
            color: #3b82f6;
            font-weight: 600;
        }
        
        .gender-f {
            color: #ec4899;
            font-weight: 600;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-light);
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 20px;
            color: #d1d5db;
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .empty-state h5 {
            margin: 0 0 10px 0;
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 18px;
        }
        
        .empty-state p {
            margin: 0;
            font-size: 14px;
        }
        
        .table-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            padding-top: 20px;
            border-top: 2px solid #f3f4f6;
            margin-top: 20px;
            animation: fadeInUp 0.5s ease 0.4s forwards;
            opacity: 0;
        }
        
        .table-footer .info {
            font-size: 14px;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .table-footer .info strong {
            color: var(--text-primary);
            font-weight: 700;
        }
        
        .table-footer .info .filter-tag {
            background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 12px;
            color: #3730a3;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
            justify-content: center;
        }
        
        @media (max-width: 768px) {
            .card {
                padding: 20px 15px;
                border-radius: var(--radius-lg);
            }
            .card-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .card-header h2 {
                font-size: 24px;
            }
            .card-header h2 .icon-wrapper {
                width: 40px;
                height: 40px;
                font-size: 18px;
            }
            .filter-wrap form {
                flex-direction: column;
                align-items: stretch;
            }
            .filter-group {
                flex: 1 1 100%;
                min-width: 100%;
            }
            .btn-group {
                flex-direction: column;
                width: 100%;
            }
            .btn-group .btn {
                width: 100%;
            }
            table {
                font-size: 13px;
                min-width: 600px;
            }
            th, td {
                padding: 10px 8px;
            }
            .table-footer {
                flex-direction: column;
                align-items: flex-start;
            }
            .action-buttons {
                flex-direction: column;
                gap: 3px;
            }
        }
        
        @media (max-width: 480px) {
            .card {
                padding: 15px 10px;
            }
            .card-header h2 {
                font-size: 20px;
            }
            .table-footer .info {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
        }
    </style>

<div class="teacher-list-container">

    <div class="card">
        <div class="card-header">
            <h2>
                <span class="icon-wrapper">
                    <i class="fas fa-chalkboard-teacher"></i>
                </span>
                Список учителей
            </h2>
            <span class="stat-badge">
                <i class="fas fa-users"></i>
                <?= $totalTeachers ?>
            </span>
        </div>

        <?php if (isset($_GET['added']) && $_GET['added'] === '1'): ?>
            <div class="alert alert-success" role="status">Учитель успешно добавлен.</div>
        <?php elseif (isset($_GET['saved']) && $_GET['saved'] === '1'): ?>
            <div class="alert alert-success" role="status">Данные учителя сохранены.</div>
        <?php elseif (isset($_GET['deleted']) && $_GET['deleted'] === '1'): ?>
            <div class="alert alert-success" role="status">Учитель удалён.</div>
        <?php endif; ?>
        <?php if ($teacherError !== ''): ?>
            <div class="alert alert-danger" role="alert"><?= htmlspecialchars($teacherError, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div class="filter-wrap">
            <form action="?page=11" method="get" id="filterForm">
                <input type="hidden" name="page" value="11">
                <div class="filter-group">
                    <label><i class="fas fa-search"></i> Поиск</label>
                    <input type="text" name="search" placeholder="Фамилия, имя..." 
                           value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="filter-group">
                    <label><i class="fas fa-book"></i> Предмет</label>
                    <select name="subject_id">
                        <option value="">Все предметы</option>
                        <?php foreach ($subjects as $subjectId => $subjectName): ?>
                            <option value="<?= $subjectId ?>" <?= (string)$subjectFilter === (string)$subjectId ? 'selected' : '' ?>><?= htmlspecialchars($subjectName, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label><i class="fas fa-venus-mars"></i> Пол</label>
                    <select name="gender">
                        <option value="">Все</option>
                        <option value="male" <?= $genderFilter === 'male' ? 'selected' : '' ?>>Мужской</option>
                        <option value="female" <?= $genderFilter === 'female' ? 'selected' : '' ?>>Женский</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label><i class="fas fa-star"></i> Категория</label>
                    <select name="category">
                        <option value="">Все</option>
                        <option value="1" <?= $categoryFilter === '1' ? 'selected' : '' ?>>Первая</option>
                        <option value="2" <?= $categoryFilter === '2' ? 'selected' : '' ?>>Вторая</option>
                        <option value="highest" <?= $categoryFilter === 'highest' ? 'selected' : '' ?>>Высшая</option>
                    </select>
                </div>

                <div class="filter-group" style="flex: 0 0 auto; min-width: auto;">
                    <label>&nbsp;</label>
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                            Найти
                        </button>
                        <a href="?page=11" class="btn btn-secondary">
                            <i class="fas fa-undo"></i>
                            Сброс
                        </a>
                        <a href="?page=9" class="btn btn-success">
                            <i class="fas fa-plus"></i>
                            Добавить
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="width:45px;">#</th>
                        <th style="text-align:left;min-width:160px;">ФИО</th>
                        <th style="width:65px;">Возраст</th>
                        <th style="width:110px;">Дата рождения</th>
                        <th style="width:55px;">Пол</th>
                        <th style="min-width:120px;">Предмет</th>
                        <th style="min-width:100px;">Категория</th>
                        <th style="width:130px;">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $i = 0;
                    if ($teacherResult && $teacherResult->num_rows > 0):
                        while ($row = $teacherResult->fetch_assoc()):
                            $i++;
                            $genderClass = ($row['gender'] === 'male') ? 'gender-m' : 'gender-f';
                            $genderIcon = ($row['gender'] === 'male') ? 'mars' : 'venus';
                            $genderLabel = ($row['gender'] === 'male') ? 'М' : 'Ж';

                            $cat = $row['category_of_specialty'];
                            if ($cat == '1') {
                                $catClass = 'badge-first';
                                $catLabel = 'Первая';
                            } elseif ($cat == '2') {
                                $catClass = 'badge-second';
                                $catLabel = 'Вторая';
                            } elseif ($cat == 'highest') {
                                $catClass = 'badge-highest';
                                $catLabel = 'Высшая';
                            } else {
                                $catClass = '';
                                $catLabel = (string)$cat;
                            }
                            $birthDate = DateTime::createFromFormat('!Y-m-d', (string)($row['birth'] ?? ''));
                            $birthLabel = $birthDate ? $birthDate->format('d.m.Y') : '—';
                    ?>
                        <tr>
                            <td class="text-muted"><?= $i ?></td>
                            <td style="text-align:left;font-weight:600;">
                                <?= htmlspecialchars($row['surname'] . ' ' . $row['name'], ENT_QUOTES, 'UTF-8') ?>
                                <?php if (!empty($row['patronymic'])): ?>
                                    <span style="color:var(--text-light);font-weight:400;"><?= htmlspecialchars($row['patronymic'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars((string)($row['age'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= $birthLabel ?></td>
                            <td><span class="<?= $genderClass ?>"><i class="fas fa-<?= $genderIcon ?>"></i> <?= $genderLabel ?></span></td>
                            <td><span class="badge-subject"><?= htmlspecialchars($row['subject_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td><span class="badge-category <?= htmlspecialchars($catClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($catLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td>
                                <div class="action-buttons">
                                    <a class="btn btn-success btn-sm" href="?page=12&amp;id=<?= (int)$row['id'] ?>">
                                        <i class="fas fa-edit"></i>
                                        Изменить
                                    </a>
                                    <form action="?page=14" method="post" onsubmit="return confirm('Удалить учителя?')">
                                        <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['teacher_csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                                        <button class="btn btn-danger btn-sm" type="submit"><i class="fas fa-trash"></i> Удалить</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php 
                        endwhile;
                    else: 
                    ?>
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <i class="fas fa-user-slash"></i>
                                    <h5>Нет учителей</h5>
                                    <p>Попробуйте изменить параметры фильтрации</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <div class="info">
                Найдено: <strong><?= $totalFiltered ?></strong> учителей
                <?php if ($search !== '' || $subjectInput !== '' || $genderFilter !== '' || $categoryFilter !== ''): ?>
                    <span class="filter-tag">
                        <i class="fas fa-filter"></i>
                        Фильтры активны
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>
