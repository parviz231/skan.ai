<?php
/* ============================================================
   students.php — Управление учениками
   Страница: ?page=3
   ============================================================ */

require_once("config.php");

/* ---------- Утилита: URL с сохранением всех GET-параметров ---------- */
function pageUrl($pageNum = null) {
    $params = $_GET;
    $params['page'] = 3;
    if ($pageNum !== null) $params['p'] = $pageNum;
    return '?' . http_build_query($params);
}

/* ---------- Общее количество учеников (для бейджа) ---------- */
$totalQuery  = "SELECT COUNT(*) AS total FROM users";
$totalResult = mysqli_query($conn, $totalQuery);
$totalRow    = mysqli_fetch_assoc($totalResult);
$totalAll    = (int)$totalRow['total'];

/* ---------- Параметры пагинации ---------- */
$perPage     = 20;
$currentPage = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$offset      = ($currentPage - 1) * $perPage;

/* ---------- Формируем WHERE по фильтрам ---------- */
$where = [];

if (isset($_GET['grade']) && $_GET['grade'] !== '') {
    $where[] = "grade = " . intval($_GET['grade']);
}

if (isset($_GET['class']) && $_GET['class'] !== '') {
    $where[] = "class = '" . mysqli_real_escape_string($conn, $_GET['class']) . "'";
}

if (isset($_GET['gender']) && $_GET['gender'] !== '') {
    $g = $_GET['gender'];
    if (in_array($g, ['М','male','m','муж','мужской'], true)) {
        $where[] = "gender IN ('М','male','m','муж','мужской')";
    } elseif (in_array($g, ['Ж','female','f','жен','женский'], true)) {
        $where[] = "gender IN ('Ж','female','f','жен','женский')";
    }
}

if (isset($_GET['search']) && $_GET['search'] !== '') {
    $s = mysqli_real_escape_string($conn, $_GET['search']);
    $where[] = "(name LIKE '%$s%' OR surname LIKE '%$s%')";
}

$whereClause = '';
if (!empty($where)) {
    $whereClause = " WHERE " . implode(" AND ", $where);
}

/* ---------- Подсчёт записей с фильтром ---------- */
$countSql    = "SELECT COUNT(*) AS total FROM users" . $whereClause;
$countResult = mysqli_query($conn, $countSql);
$countRow    = mysqli_fetch_assoc($countResult);
$totalRecords = (int)$countRow['total'];
$totalPages   = $totalRecords > 0 ? ceil($totalRecords / $perPage) : 0;

if ($currentPage > $totalPages && $totalPages > 0) {
    $currentPage = $totalPages;
    $offset      = ($currentPage - 1) * $perPage;
}

/* ---------- Получаем данные для текущей страницы ---------- */
$sql  = "SELECT * FROM users" . $whereClause;
$sql .= " ORDER BY surname, name ASC";
$sql .= " LIMIT $offset, $perPage";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление учениками</title>
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
        .container-fluid { max-width: 1300px; margin: 0 auto; position: relative; z-index: 1; }
        .card {
            background: var(--bg-card);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            padding: 30px;
            margin-bottom: 30px;
            animation: slideInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
            border: 1px solid rgba(229, 231, 235, 0.5);
            position: relative; overflow: hidden;
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
            position: relative; overflow: hidden;
        }
        @keyframes iconBounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }
        .card-header .stats {
            display: flex; align-items: center; gap: 10px;
            font-size: 14px; color: var(--text-secondary);
        }
        .card-header .stats .stat-badge {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white; padding: 8px 16px; border-radius: 50px;
            font-weight: 600; display: flex; align-items: center; gap: 8px;
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
        .filter-wrap .row { margin: 0 -8px; }
        .filter-wrap .row > div { padding: 0 8px; margin-bottom: 15px; }
        .filter-wrap label {
            display: block; font-size: 12px; font-weight: 600;
            color: var(--text-secondary); margin-bottom: 8px;
            letter-spacing: 1px; text-transform: uppercase;
        }
        .filter-wrap select, .filter-wrap input {
            width: 100%; padding: 10px 15px;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: 14px; background: white;
            transition: var(--transition);
            height: 42px; color: var(--text-primary);
            font-family: 'Inter', sans-serif;
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
            background-repeat: no-repeat;
            background-position: right 15px center;
            padding-right: 40px; cursor: pointer;
        }
        .btn-group-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn {
            font-size: 14px; padding: 10px 20px;
            border-radius: var(--radius-md); border: none;
            cursor: pointer; transition: var(--transition);
            text-decoration: none;
            display: inline-flex; align-items: center; justify-content: center;
            gap: 8px; font-weight: 600; height: 42px;
            letter-spacing: 0.3px; position: relative; overflow: hidden;
            font-family: 'Inter', sans-serif;
        }
        .btn-primary {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: #fff; box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
        }
        .btn-primary:hover {
            box-shadow: 0 8px 25px rgba(79, 70, 229, 0.4);
            transform: translateY(-2px); color: white;
        }
        .btn-secondary {
            background: #fff; color: var(--text-primary);
            border: 2px solid var(--border-color);
        }
        .btn-secondary:hover {
            background: #f9fafb; border-color: #d1d5db;
            transform: translateY(-2px); box-shadow: var(--shadow-md);
            color: var(--text-primary);
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
        .btn-add {
            background: linear-gradient(135deg, #10b981, #059669);
            color: #fff; font-weight: 600;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }
        .btn-add:hover {
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
            transform: translateY(-2px); color: white;
        }
        .btn-sm { padding: 6px 14px; font-size: 13px; height: 32px; gap: 5px; }
        .table-responsive {
            overflow-x: auto; -webkit-overflow-scrolling: touch;
            margin: 0 -4px;
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
        .badge {
            display: inline-block; padding: 6px 12px;
            border-radius: 50px; font-size: 12px;
            font-weight: 600; letter-spacing: 0.3px;
        }
        .badge-primary {
            background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
            color: #3730a3;
        }
        .badge-secondary {
            background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
            color: #374151;
        }
        .text-muted { color: var(--text-light); }
        .gender-m {
            color: #3b82f6; font-weight: 600;
            display: inline-flex; align-items: center; gap: 5px;
        }
        .gender-f {
            color: #ec4899; font-weight: 600;
            display: inline-flex; align-items: center; gap: 5px;
        }
        .gender-m::before { content: '♂'; font-size: 16px; }
        .gender-f::before { content: '♀'; font-size: 16px; }
        .action-buttons { display: flex; gap: 5px; justify-content: center; }
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
        .table-footer .info .tag {
            background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
            padding: 6px 12px; border-radius: 50px;
            font-size: 12px; color: #3730a3; font-weight: 600;
        }
        .pagination-wrap {
            display: flex; justify-content: center; align-items: center;
            gap: 8px; margin-top: 20px; flex-wrap: wrap;
            animation: fadeInUp 0.5s ease 0.5s forwards; opacity: 0;
        }
        .pagination {
            display: flex; gap: 5px;
            list-style: none; padding: 0; margin: 0;
        }
        .pagination .page-item { display: inline-block; }
        .pagination .page-link {
            display: flex; align-items: center; justify-content: center;
            min-width: 40px; height: 40px; padding: 0 12px;
            border-radius: var(--radius-md);
            border: 2px solid var(--border-color);
            background: white; color: var(--text-secondary);
            font-size: 14px; font-weight: 600;
            text-decoration: none; transition: var(--transition);
            cursor: pointer; font-family: 'Inter', sans-serif;
        }
        .pagination .page-link:hover {
            border-color: var(--primary); color: var(--primary);
            background: #f9fafb; transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }
        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            border-color: transparent; color: white;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }
        .pagination .page-item.disabled .page-link {
            opacity: 0.5; pointer-events: none; cursor: not-allowed;
        }
        .pagination-info {
            font-size: 14px; color: var(--text-secondary);
            margin-left: 15px;
        }
        @media (max-width: 768px) {
            body { padding: 20px 10px; }
            .card { padding: 20px 15px; border-radius: var(--radius-lg); }
            .card-header { flex-direction: column; align-items: flex-start; }
            .card-header h2 { font-size: 24px; }
            .filter-wrap .row > div { flex: 0 0 50%; max-width: 50%; }
            .filter-wrap .row > div:last-child { flex: 0 0 100%; max-width: 100%; }
            .btn-group-actions { width: 100%; }
            .btn-group-actions .btn { flex: 1; }
            table { font-size: 13px; min-width: 600px; }
            .table-footer { flex-direction: column; align-items: flex-start; }
            .btn-add { width: 100%; }
        }
        @media (max-width: 480px) {
            .filter-wrap .row > div { flex: 0 0 100%; max-width: 100%; }
            .action-buttons { flex-direction: column; gap: 3px; }
            .action-buttons .btn { width: 100%; }
        }
    </style>
</head>
<body>

<div class="container-fluid">
<div class="card">

    <!-- Шапка -->
    <div class="card-header">
        <h2>
            <span class="icon-wrapper"><i class="fas fa-users"></i></span>
            Ученики
        </h2>
        <div class="stats">
            <span class="stat-badge">
                <i class="fas fa-user-graduate"></i>
                Всего: <?= $totalAll ?>
            </span>
        </div>
    </div>

    <!-- Фильтры -->
    <div class="filter-wrap">
        <form action="" method="GET">
            <input type="hidden" name="page" value="3">
            <div class="row">

                <div class="col-md-3 col-sm-6">
                    <label><i class="fas fa-graduation-cap"></i> Класс</label>
                    <select name="grade">
                        <option value="">Все классы</option>
                        <?php for ($i = 5; $i <= 11; $i++): ?>
                            <option value="<?= $i ?>" <?= (isset($_GET['grade']) && $_GET['grade'] == $i) ? 'selected' : '' ?>>
                                <?= $i ?> класс
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="col-md-2 col-sm-6">
                    <label><i class="fas fa-layer-group"></i> Группа</label>
                    <select name="class">
                        <option value="">Все группы</option>
                        <?php foreach (['А','Б','В'] as $g): ?>
                            <option value="<?= $g ?>" <?= (isset($_GET['class']) && $_GET['class'] === $g) ? 'selected' : '' ?>>
                                Группа <?= $g ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2 col-sm-6">
                    <label><i class="fas fa-venus-mars"></i> Пол</label>
                    <select name="gender">
                        <option value="">Все</option>
                        <option value="М" <?= (($_GET['gender'] ?? '') === 'М') ? 'selected' : '' ?>>Мужской</option>
                        <option value="Ж" <?= (($_GET['gender'] ?? '') === 'Ж') ? 'selected' : '' ?>>Женский</option>
                    </select>
                </div>

                <div class="col-md-3 col-sm-6">
                    <label><i class="fas fa-search"></i> Поиск</label>
                    <input type="text" name="search" placeholder="Имя или фамилия..."
                           value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                </div>

                <div class="col-md-2 col-sm-12">
                    <label>&nbsp;</label>
                    <div class="btn-group-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Найти
                        </button>
                        <a href="?page=3" class="btn btn-secondary">
                            <i class="fas fa-undo"></i> Сброс
                        </a>
                    </div>
                </div>

            </div>
        </form>
    </div>

    <!-- Таблица -->
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Фамилия</th>
                    <th>Имя</th>
                    <th>Отчество</th>
                    <th>Дата рожд.</th>
                    <th>Пол</th>
                    <th>Класс</th>
                    <th>Группа</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php $i = $offset; while ($row = mysqli_fetch_assoc($result)): $i++; ?>
                        <?php
                        $isMale = in_array($row['gender'], ['М','male','m','муж','мужской'], true);
                        $gender = $isMale
                            ? '<span class="gender-m">Мужской</span>'
                            : '<span class="gender-f">Женский</span>';
                        ?>
                        <tr>
                            <td class="text-muted"><?= $i ?></td>
                            <td><strong><?= htmlspecialchars($row['surname']) ?></strong></td>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td class="text-muted"><?= htmlspecialchars($row['patronymic'] ?: '—') ?></td>
                            <td><?= date('d.m.Y', strtotime($row['birth'])) ?></td>
                            <td><?= $gender ?></td>
                            <td><span class="badge badge-primary"><?= (int)$row['grade'] ?> класс</span></td>
                            <td><span class="badge badge-secondary"><?= htmlspecialchars($row['class']) ?></span></td>
                            <td>
                                <div class="action-buttons">
                                    <a class="btn btn-success btn-sm" href="?page=4&id=<?= (int)$row['id'] ?>">
                                        <i class="fas fa-edit"></i> Изменить
                                    </a>
                                    <a class="btn btn-danger btn-sm" href="?page=6&id=<?= (int)$row['id'] ?>"
                                       onclick="return confirm('Удалить ученика?')">
                                        <i class="fas fa-trash"></i> Удалить
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">
                                <div class="empty-icon"><i class="fas fa-search"></i></div>
                                <h5>Ничего не найдено</h5>
                                <p>Попробуйте изменить параметры фильтра или добавить нового ученика</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Подвал -->
    <div class="table-footer">
        <div class="info">
            Показано: <strong><?= min($perPage, max(0, $totalRecords - $offset)) ?></strong>
            из <strong><?= $totalRecords ?></strong> учеников

            <?php if (!empty($_GET['grade'])): ?>
                <span class="tag"><i class="fas fa-graduation-cap"></i> Класс <?= (int)$_GET['grade'] ?></span>
            <?php endif; ?>
            <?php if (!empty($_GET['class'])): ?>
                <span class="tag"><i class="fas fa-layer-group"></i> Группа <?= htmlspecialchars($_GET['class']) ?></span>
            <?php endif; ?>
            <?php if (!empty($_GET['gender'])): ?>
                <span class="tag"><i class="fas fa-venus-mars"></i> <?= $_GET['gender'] === 'М' ? 'Мужской' : 'Женский' ?></span>
            <?php endif; ?>
            <?php if (!empty($_GET['search'])): ?>
                <span class="tag"><i class="fas fa-search"></i> Поиск: <?= htmlspecialchars($_GET['search']) ?></span>
            <?php endif; ?>
        </div>
        <a class="btn btn-add" href="?page=1">
            <i class="fas fa-plus"></i> Добавить ученика
        </a>
    </div>

    <!-- Пагинация -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination-wrap">
            <ul class="pagination">
                <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= pageUrl($currentPage - 1) ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>

                <?php if ($currentPage > 3): ?>
                    <li class="page-item"><a class="page-link" href="<?= pageUrl(1) ?>">1</a></li>
                    <?php if ($currentPage > 4): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                <?php endif; ?>

                <?php
                $startPage = max(1, $currentPage - 2);
                $endPage   = min($totalPages, $currentPage + 2);
                for ($p = $startPage; $p <= $endPage; $p++):
                ?>
                    <li class="page-item <?= $p == $currentPage ? 'active' : '' ?>">
                        <a class="page-link" href="<?= pageUrl($p) ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($currentPage < $totalPages - 2): ?>
                    <?php if ($currentPage < $totalPages - 3): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                    <li class="page-item"><a class="page-link" href="<?= pageUrl($totalPages) ?>"><?= $totalPages ?></a></li>
                <?php endif; ?>

                <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= pageUrl($currentPage + 1) ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            </ul>
            <span class="pagination-info">
                Страница <?= $currentPage ?> из <?= $totalPages ?>
            </span>
        </div>
    <?php endif; ?>

</div>
</div>

<script src="js/bootstrap.bundle.js"></script>
</body>
</html>