<?php
require_once('config.php');
if ($conn->connect_error) die("Ошибка подключения: " . $conn->connect_error);

$classes = $conn->query("SELECT DISTINCT grade, class FROM users ORDER BY grade, class");
$msg = "";
$class_name = 'А';
$grade_name = 5;
$day = 'Monday';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $class_name = $_POST["class"] ?? $class_name;
    $grade_name = filter_var($_POST["grade"] ?? $grade_name, FILTER_VALIDATE_INT);
    $day = $_POST["day"] ?? $day;
    $validDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    $postedSubjects = is_array($_POST["subject"] ?? null) ? $_POST["subject"] : [];
    $hasSubject = false;
    foreach ($postedSubjects as $subjectRaw) {
        if (is_string($subjectRaw) && trim($subjectRaw) !== '') {
            $hasSubject = true;
            break;
        }
    }

    if (!in_array($class_name, ['А', 'Б', 'В'], true)
        || $grade_name === false || $grade_name < 5 || $grade_name > 11
        || !in_array($day, $validDays, true)) {
        $msg = "Некорректный класс, параллель или день недели.";
    } elseif (!$hasSubject) {
        $msg = "Укажите хотя бы один предмет.";
    } else {
        $class = $class_name;
        $grade = (int)$grade_name;
        $roomValue = '';
        $subjectValue = '';
        $teacherName = '';
        $teacherSurname = '';
        $teacherId = null;
        $subjectId = 0;
        $lessonNumber = 0;

        try {
            $conn->begin_transaction();

            $deleteStmt = $conn->prepare("DELETE FROM schedule WHERE class = ? AND grade = ? AND day_of_week = ?");
            $deleteStmt->bind_param("sis", $class, $grade, $day);
            $deleteStmt->execute();
            $deleteStmt->close();

            $teacherStmt = $conn->prepare("SELECT id FROM teachers WHERE `name` = ? AND `surname` = ? LIMIT 1");
            $subjectStmt = $conn->prepare("SELECT id FROM subjects WHERE name = ? LIMIT 1");
            $newSubjectStmt = $conn->prepare("INSERT INTO subjects (name) VALUES (?)");
            $insertStmt = $conn->prepare("
                INSERT INTO schedule (class, grade, day_of_week, lesson_number, subject_id, teacher_id, room)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            foreach ($postedSubjects as $lessonNumRaw => $subjectRaw) {
                if (!is_string($subjectRaw) || trim($subjectRaw) === '') continue;
                $lessonNumber = filter_var($lessonNumRaw, FILTER_VALIDATE_INT);
                if ($lessonNumber === false || $lessonNumber < 1 || $lessonNumber > 6) continue;

                $subjectValue = trim($subjectRaw);
                $roomValue = trim((string)($_POST["room"][$lessonNumRaw] ?? ''));
                $teacherRaw = $_POST["teacher"][$lessonNumRaw] ?? '';
                $teacherId = null;
                $teacherTrimmed = is_string($teacherRaw) ? trim($teacherRaw) : '';
                if ($teacherTrimmed !== '') {
                    $parts = preg_split('/\s+/', $teacherTrimmed, 2);
                    $teacherSurname = $parts[0] ?? '';
                    $teacherName = $parts[1] ?? '';
                    if ($teacherName !== '') {
                        $teacherStmt->bind_param("ss", $teacherName, $teacherSurname);
                        $teacherStmt->execute();
                        $teacherResult = $teacherStmt->get_result();
                        $teacherRow = $teacherResult->fetch_assoc();
                        if ($teacherRow) $teacherId = (int)$teacherRow['id'];
                    }
                }

                $subjectStmt->bind_param("s", $subjectValue);
                $subjectStmt->execute();
                $subjectResult = $subjectStmt->get_result();
                $subjectRow = $subjectResult->fetch_assoc();
                if ($subjectRow) {
                    $subjectId = (int)$subjectRow['id'];
                } else {
                    $newSubjectStmt->bind_param("s", $subjectValue);
                    $newSubjectStmt->execute();
                    $subjectId = (int)$conn->insert_id;
                }

                $insertStmt->bind_param(
                    "sisiiis",
                    $class,
                    $grade,
                    $day,
                    $lessonNumber,
                    $subjectId,
                    $teacherId,
                    $roomValue
                );
                $insertStmt->execute();
            }

            $teacherStmt->close();
            $subjectStmt->close();
            $newSubjectStmt->close();
            $insertStmt->close();
            $conn->commit();
            $msg = "Расписание успешно сохранено!";
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            error_log('Schedule creation failed: ' . $e->getMessage());
            $msg = "Не удалось сохранить расписание. Проверьте подключение к базе данных.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить расписание</title>
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
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --text-light: #9ca3af;
            --bg-main: #f9fafb;
            --bg-card: #ffffff;
            --border-color: #e5e7eb;
            --border-focus: #4f46e5;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --radius-sm: 6px;
            --radius-md: 8px;
            --radius-lg: 12px;
            --radius-xl: 16px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
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
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #4f46e5, #7c3aed, #10b981);
            z-index: 1000;
            animation: gradientShift 3s ease infinite;
            background-size: 200% 200%;
        }
        
        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        .wrap {
            max-width: 900px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }
        
        .box {
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
        
        .box::before {
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
        
        .box:hover::before {
            opacity: 1;
        }
        
        @keyframes slideInUp {
            from { opacity: 0; transform: translateY(40px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        
        .head {
            display: flex;
            align-items: center;
            gap: 15px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f3f4f6;
            margin-bottom: 25px;
        }
        
        .head .icon-wrapper {
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
        
        .head h2 {
            font-size: 24px;
            font-weight: 800;
            color: var(--text-primary);
            letter-spacing: -0.5px;
        }
        
        .msg {
            padding: 14px 18px;
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
            border: 1px solid #86efac;
            border-radius: var(--radius-md);
            margin-bottom: 20px;
            font-size: 14px;
            color: #166534;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: fadeInUp 0.5s ease;
            font-weight: 500;
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        
        .form-group select {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: 14px;
            height: 42px;
            background: white;
            color: var(--text-primary);
            transition: var(--transition);
            font-family: 'Inter', sans-serif;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            padding-right: 40px;
            cursor: pointer;
        }
        
        .form-group select:focus {
            border-color: var(--border-focus);
            outline: none;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }
        
        .table-wrap {
            overflow-x: auto;
            margin: 20px 0;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-lg);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            min-width: 500px;
        }
        
        table th {
            background: linear-gradient(135deg, #f9fafb, #f3f4f6);
            color: var(--text-secondary);
            font-weight: 600;
            padding: 14px 12px;
            text-align: center;
            border-bottom: 2px solid var(--border-color);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        table td {
            padding: 8px;
            border-bottom: 1px solid #f3f4f6;
            text-align: center;
            vertical-align: middle;
        }
        
        table tr:hover td {
            background: #f9fafb;
        }
        
        table td:first-child {
            font-weight: 600;
            color: var(--text-secondary);
            width: 60px;
        }
        
        table input {
            width: 100%;
            padding: 8px 12px;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-size: 13px;
            height: 38px;
            background: #fafafa;
            transition: var(--transition);
            outline: none;
            color: var(--text-primary);
            font-family: 'Inter', sans-serif;
        }
        
        table input:focus {
            border-color: var(--border-focus);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
            background: white;
        }
        
        table input::placeholder {
            color: #b0b0b0;
        }
        
        .btn {
            padding: 10px 30px;
            border: none;
            border-radius: var(--radius-md);
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            height: 45px;
            font-family: 'Inter', sans-serif;
            position: relative;
            overflow: hidden;
        }
        .head .icon-wrapper {
    /* ... */
    animation: iconWobble 2s ease-in-out infinite;
}

@keyframes iconWobble {
    0%, 100% { transform: rotate(0deg) scale(1); }
    25% { transform: rotate(-10deg) scale(1.05); }
    50% { transform: rotate(0deg) scale(1); }
    75% { transform: rotate(10deg) scale(1.05); }
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
        
        .btn-group {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 15px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            padding: 15px 0 5px;
            font-size: 13px;
            color: var(--text-secondary);
        }
        
        .info-row .selected {
            background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
            padding: 6px 14px;
            border-radius: 50px;
            font-weight: 600;
            color: #3730a3;
        }
        
        @media (max-width: 768px) {
            body { padding: 20px 10px; }
            .box { padding: 20px 15px; border-radius: var(--radius-lg); }
            .form-row { grid-template-columns: 1fr; gap: 15px; }
            .head h2 { font-size: 20px; }
            .head .icon-wrapper { width: 40px; height: 40px; font-size: 18px; }
            table { font-size: 13px; min-width: 400px; }
            table input { font-size: 12px; height: 34px; padding: 6px 10px; }
            .btn { width: 100%; justify-content: center; }
            .btn-group { flex-direction: column; }
        }
        
        @media (max-width: 480px) {
            .box { padding: 15px 10px; }
            .head h2 { font-size: 18px; }
            table td { padding: 5px; }
            table input { font-size: 11px; height: 30px; padding: 4px 8px; }
        }
    </style>
</head>
<body>

<div class="wrap">
    <div class="box">
        <div class="head">
            <div class="icon-wrapper">
                <i class="fas fa-calendar-plus"></i>
            </div>
            <h2>Добавить расписание уроков</h2>
        </div>

        <?php if ($msg): ?>
            <div class="msg">
                <i class="fas fa-check-circle"></i>
                <?= $msg ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-graduation-cap"></i> Класс</label>
                    <select name="grade" required>
                        <?php for ($g = 5; $g <= 11; $g++): ?>
                            <option value="<?= $g ?>" <?= $g == $grade_name ? 'selected' : '' ?>><?= $g ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-layer-group"></i> Буква</label>
                    <select name="class" required>
                        <?php foreach (['А', 'Б', 'В'] as $c): ?>
                            <option <?= $c == $class_name ? 'selected' : '' ?>><?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-calendar-day"></i> День недели</label>
                    <select name="day" required>
                        <option value="Monday" <?= $day == 'Monday' ? 'selected' : '' ?>>Понедельник</option>
                        <option value="Tuesday" <?= $day == 'Tuesday' ? 'selected' : '' ?>>Вторник</option>
                        <option value="Wednesday" <?= $day == 'Wednesday' ? 'selected' : '' ?>>Среда</option>
                        <option value="Thursday" <?= $day == 'Thursday' ? 'selected' : '' ?>>Четверг</option>
                        <option value="Friday" <?= $day == 'Friday' ? 'selected' : '' ?>>Пятница</option>
                        <option value="Saturday" <?= $day == 'Saturday' ? 'selected' : '' ?>>Суббота</option>
                    </select>
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>№</th>
                            <th>Предмет</th>
                            <th>Учитель</th>
                            <th>Кабинет</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($i = 1; $i <= 6; $i++): ?>
                        <tr>
                            <td><?= $i ?></td>
                            <td><input type="text" name="subject[<?= $i ?>]" placeholder="Название предмета"></td>
                            <td><input type="text" name="teacher[<?= $i ?>]" placeholder="Фамилия Имя"></td>
                            <td><input type="text" name="room[<?= $i ?>]" placeholder="№ кабинета"></td>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>

            <div class="info-row">
                <span><i class="far fa-clock"></i> Можно добавить до 6 уроков</span>
                <span class="selected">Класс: <?= $grade_name . $class_name ?></span>
            </div>

            <div class="btn-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Сохранить расписание
                </button>
                <a href="?page=16" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Назад
                </a>
            </div>
        </form>
    </div>
</div>

<script src="js/bootstrap.bundle.js"></script></body>
</html>