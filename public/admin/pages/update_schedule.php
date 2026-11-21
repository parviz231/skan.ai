<?php
require_once('config.php');
if ($conn->connect_error) die("Ошибка подключения: " . $conn->connect_error);

$msg = "";
$msg_type = "info";
$class_name = 'А';
$grade_name = 5;
$day = "Monday";
$current_schedule = [];

// Получаем список всех учителей
$all_teachers = [];
$teachers_res = $conn->query("SELECT id, surname, name, patronymic FROM teachers ORDER BY surname, name");
if ($teachers_res) {
    while ($t = $teachers_res->fetch_assoc()) {
        $all_teachers[] = $t;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    $class_name = $conn->real_escape_string($_POST["class"] ?? $class_name);
    $grade_name = intval($_POST["grade"] ?? $grade_name);
    $day = $conn->real_escape_string($_POST["day"] ?? $day);

    if ($action === 'load') {
        // Загружаем расписание
        $sql = "SELECT s.*, sub.name AS subject
                FROM schedule s
                LEFT JOIN subjects sub ON sub.id = s.subject_id
                WHERE s.class='$class_name' AND s.grade=$grade_name AND s.day_of_week='$day'
                ORDER BY lesson_number";
        
        $res = $conn->query($sql);
        $current_schedule = [];
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $current_schedule[$row['lesson_number']] = $row;
            }
            $msg = "Расписание загружено для: {$grade_name}{$class_name}, " . getDayName($day);
            $msg_type = "success";
        } else {
            $msg = "Расписание не найдено для: {$grade_name}{$class_name}, " . getDayName($day);
            $msg_type = "info";
        }
    } elseif ($action === 'save') {
        $saved_count = 0;
        try {
            $conn->begin_transaction();
            $deleteStmt = $conn->prepare("DELETE FROM schedule WHERE class = ? AND grade = ? AND day_of_week = ?");
            $deleteStmt->bind_param("sis", $class_name, $grade_name, $day);
            $deleteStmt->execute();
            $deleteStmt->close();

            $subjectValue = '';
            $subjectId = 0;
            $room = '';
            $lessonNumber = 0;
            $teacherId = null;
            $subjectStmt = $conn->prepare("SELECT id FROM subjects WHERE name = ? LIMIT 1");
            $newSubjectStmt = $conn->prepare("INSERT INTO subjects (name) VALUES (?)");
            $insertStmt = $conn->prepare("
                INSERT INTO schedule (class, grade, day_of_week, lesson_number, subject_id, teacher_id, room)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            if (is_array($_POST["subject"] ?? null)) {
                foreach ($_POST["subject"] as $lessonNumRaw => $subjectRaw) {
                    if (!is_string($subjectRaw) || trim($subjectRaw) === '') continue;
                    $lessonNumber = filter_var($lessonNumRaw, FILTER_VALIDATE_INT);
                    if ($lessonNumber === false || $lessonNumber < 1 || $lessonNumber > 7) continue;

                    $subjectValue = trim($subjectRaw);
                    $room = trim((string)($_POST["room"][$lessonNumRaw] ?? ''));
                    $teacherIdRaw = filter_var($_POST["teacher_id"][$lessonNumRaw] ?? 0, FILTER_VALIDATE_INT);
                    $teacherId = ($teacherIdRaw !== false && $teacherIdRaw > 0) ? $teacherIdRaw : null;

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
                        $class_name,
                        $grade_name,
                        $day,
                        $lessonNumber,
                        $subjectId,
                        $teacherId,
                        $room
                    );
                    $insertStmt->execute();
                    $saved_count++;
                }
            }

            $subjectStmt->close();
            $newSubjectStmt->close();
            $insertStmt->close();
            $conn->commit();
            $msg = "Расписание сохранено! Уроков: $saved_count";
            $msg_type = "success";
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            error_log('Schedule update failed: ' . $e->getMessage());
            $msg = "Не удалось сохранить расписание. Проверьте подключение к базе данных.";
            $msg_type = "danger";
        }

        // Перезагружаем расписание
        $res = $conn->query("SELECT s.*, sub.name AS subject
                            FROM schedule s
                            LEFT JOIN subjects sub ON sub.id = s.subject_id
                            WHERE s.class='$class_name' AND s.grade=$grade_name AND s.day_of_week='$day'
                            ORDER BY lesson_number");
        $current_schedule = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $current_schedule[$row['lesson_number']] = $row;
            }
        }

    }
}

function getDayName($day) {
    $days = [
        "Monday" => "Понедельник",
        "Tuesday" => "Вторник",
        "Wednesday" => "Среда",
        "Thursday" => "Четверг",
        "Friday" => "Пятница",
        "Saturday" => "Суббота"
    ];
    return $days[$day] ?? $day;
}

// Функция для получения имени учителя по ID
function getTeacherName($teacher_id, $all_teachers) {
    foreach ($all_teachers as $t) {
        if ($t['id'] == $teacher_id) {
            return $t['surname'] . ' ' . $t['name'] . (!empty($t['patronymic']) ? ' ' . $t['patronymic'] : '');
        }
    }
    return '';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Изменить расписание</title>
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
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
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
            background: linear-gradient(135deg, #f59e0b, #d97706);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }
        
        .head h2 {
            font-size: 24px;
            font-weight: 800;
            color: var(--text-primary);
        }
        
        .msg {
            padding: 14px 18px;
            border-radius: var(--radius-md);
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }
        
        .msg-success {
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
            border: 1px solid #86efac;
            color: #166534;
        }
        
        .msg-info {
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            border: 1px solid #93c5fd;
            color: #1e40af;
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
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            padding-right: 40px;
        }
        
        .form-group select:focus {
            border-color: var(--border-focus);
            outline: none;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: var(--radius-md);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            height: 42px;
            font-family: 'Inter', sans-serif;
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #fff;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
        }
        
        .btn-warning:hover {
            box-shadow: 0 8px 25px rgba(245, 158, 11, 0.4);
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: #fff;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }
        
        .btn-success:hover {
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #fff;
            color: var(--text-primary);
            border: 2px solid var(--border-color);
        }
        
        .btn-group {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 15px;
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
            min-width: 600px;
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
        }
        
        table td {
            padding: 8px;
            border-bottom: 1px solid #f3f4f6;
            text-align: center;
        }
        
        table td:first-child {
            font-weight: 700;
            color: var(--primary);
            width: 50px;
        }
        
        table input,
        table select {
            width: 100%;
            padding: 8px 12px;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-size: 13px;
            height: 38px;
            font-family: 'Inter', sans-serif;
        }
        
        table input:focus,
        table select:focus {
            border-color: var(--border-focus);
            outline: none;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }
        
        table input.has-value {
            border-color: var(--primary-light);
            background: #f8faff;
        }
        
        table select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            padding-right: 35px;
        }
        
        @media (max-width: 768px) {
            body { padding: 20px 10px; }
            .box { padding: 20px 15px; }
            .form-row { grid-template-columns: 1fr; gap: 15px; }
            .head h2 { font-size: 20px; }
            .btn { width: 100%; justify-content: center; }
            .btn-group { flex-direction: column; }
            table { min-width: 500px; }
        }.head .icon-wrapper {
    /* ... */
    animation: iconWobble 2s ease-in-out infinite;
}

@keyframes iconWobble {
    0%, 100% { transform: rotate(0deg) scale(1); }
    25% { transform: rotate(-10deg) scale(1.05); }
    50% { transform: rotate(0deg) scale(1); }
    75% { transform: rotate(10deg) scale(1.05); }
}
    </style>
</head>
<body>

<div class="wrap">
    <div class="box">
        <div class="head">
            <div class="icon-wrapper">
                <i class="fas fa-edit"></i>
            </div>
            <h2>Изменить расписание уроков</h2>
        </div>

        <?php if ($msg): ?>
            <div class="msg <?= $msg_type === 'success' ? 'msg-success' : 'msg-info' ?>">
                <i class="fas <?= $msg_type === 'success' ? 'fa-check-circle' : 'fa-info-circle' ?>"></i>
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Класс</label>
                    <select name="grade" required>
                        <?php for ($g = 5; $g <= 11; $g++): ?>
                            <option value="<?= $g ?>" <?= $g == $grade_name ? 'selected' : '' ?>><?= $g ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Буква</label>
                    <select name="class" required>
                        <?php foreach (['А', 'Б', 'В'] as $c): ?>
                            <option value="<?= $c ?>" <?= $c == $class_name ? 'selected' : '' ?>><?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>День недели</label>
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

            <button type="submit" name="action" value="load" class="btn btn-warning">
                <i class="fas fa-download"></i>
                Загрузить расписание
            </button>

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
                        <?php for ($i = 1; $i <= 6; $i++):
                            $subj = $current_schedule[$i]['subject'] ?? '';
                            $room = $current_schedule[$i]['room'] ?? '';
                            $teacher_id = $current_schedule[$i]['teacher_id'] ?? 0;
                            $hasValue = !empty($subj);
                        ?>
                        <tr>
                            <td><?= $i ?></td>
                            <td>
                                <input type="text" name="subject[<?= $i ?>]" 
                                       value="<?= htmlspecialchars($subj) ?>" 
                                       placeholder="Предмет"
                                       class="<?= $hasValue ? 'has-value' : '' ?>">
                            </td>
                            <td>
                                <select name="teacher_id[<?= $i ?>]">
                                    <option value="0">— Выберите учителя —</option>
                                    <?php foreach ($all_teachers as $t): ?>
                                        <option value="<?= $t['id'] ?>" <?= $t['id'] == $teacher_id ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($t['surname'] . ' ' . $t['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="room[<?= $i ?>]" 
                                       value="<?= htmlspecialchars($room) ?>" 
                                       placeholder="Кабинет">
                            </td>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>

            <div class="btn-group">
                <button type="submit" name="action" value="save" class="btn btn-success">
                    <i class="fas fa-save"></i>
                    Сохранить изменения
                </button>
                <a href="?page=15" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Назад
                </a>
            </div>
        </form>
    </div>
</div>

<script src="js/bootstrap.bundle.js"></script>
</body>
</html>