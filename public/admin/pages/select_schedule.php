<?php
require_once('config.php');
if ($conn->connect_error) die("Ошибка подключения: " . $conn->connect_error);

$class_name = $_POST["class"] ?? "А";
$grade_name = $_POST["grade"] ?? 5;

$current_schedule = [];

// Загружаем расписание с JOIN для получения учителей
$sql = "SELECT s.*, sub.name AS subject_display,
               CONCAT(t.surname, ' ', t.name) as teacher_full_name
        FROM schedule s
        LEFT JOIN subjects sub ON sub.id = s.subject_id
        LEFT JOIN teachers t ON s.teacher_id = t.id
        WHERE s.class = '$class_name' AND s.grade = '$grade_name'
        ORDER BY FIELD(s.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), 
                 s.lesson_number";

$res = $conn->query($sql);

if ($res && $res->num_rows > 0) {
    while($row = $res->fetch_assoc()) {
        $current_schedule[$row['day_of_week']][$row['lesson_number']] = $row;
    }
}

$days = [
    "Monday"    => "Понедельник",
    "Tuesday"   => "Вторник",
    "Wednesday" => "Среда",
    "Thursday"  => "Четверг",
    "Friday"    => "Пятница",
    "Saturday"  => "Суббота"
];

$dayIcons = [
    "Monday"    => "fa-sun",
    "Tuesday"   => "fa-calendar-day",
    "Wednesday" => "fa-calendar-check",
    "Thursday"  => "fa-calendar-alt",
    "Friday"    => "fa-star",
    "Saturday"  => "fa-moon"
];

// Предметы, для которых НЕ показываем учителя
// Если хотите показывать учителя физкультуры - уберите 'Физкультура' и 'Физ.культура' из массива
$noTeacherSubjects = ['Классный час'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Расписание класса</title>
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
            max-width: 1200px;
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
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f3f4f6;
            margin-bottom: 25px;
        }
        
        .head-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .head-left .icon-wrapper {
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
        }
        
        .head-left h2 {
            font-size: 24px;
            font-weight: 800;
            color: var(--text-primary);
        }
        
        .class-badge {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 16px;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }
        
        .form-row {
            display: flex;
            gap: 15px;
            align-items: flex-end;
            flex-wrap: wrap;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #f9fafb, #f3f4f6);
            padding: 20px;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            flex: 1 1 150px;
            min-width: 120px;
        }
        
        .form-group label {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        
        .form-group select {
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
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }
        
        .btn {
            padding: 10px 25px;
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
        
        .btn-primary {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: #fff;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
        }
        
        .btn-primary:hover {
            box-shadow: 0 8px 25px rgba(79, 70, 229, 0.4);
            transform: translateY(-2px);
        }
        
        .schedule-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 20px;
        }
        
        .day-card {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            border: 2px solid var(--border-color);
            overflow: hidden;
            transition: var(--transition);
        }
        
        .day-card:hover {
            border-color: var(--primary-light);
            box-shadow: var(--shadow-md);
            transform: translateY(-3px);
        }
        
        .day-card .day-header {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
            padding: 15px;
            font-weight: 700;
            font-size: 15px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .day-card table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        
        .day-card table th {
            background: linear-gradient(135deg, #f9fafb, #f3f4f6);
            color: var(--text-secondary);
            font-weight: 600;
            padding: 10px 8px;
            text-align: center;
            font-size: 10px;
            text-transform: uppercase;
            border-bottom: 1px solid var(--border-color);
        }
        
        .day-card table td {
            padding: 10px 8px;
            text-align: center;
            border-bottom: 1px solid #f3f4f6;
            font-size: 13px;
            vertical-align: middle;
        }
        
        .day-card table tr:last-child td {
            border-bottom: none;
        }
        
        .lesson-num {
            font-weight: 700;
            color: var(--primary);
            width: 35px;
        }
        
        .subject-name {
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .teacher-name {
            color: var(--text-secondary);
            font-size: 12px;
        }
        
        .room {
            color: var(--primary);
            font-weight: 600;
            font-size: 12px;
        }
        
        .empty-subject {
            color: var(--text-light);
        }
        
        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-light);
        }
        
        .no-data i {
            font-size: 48px;
            margin-bottom: 20px;
            color: #d1d5db;
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
        .no-data h3 {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 10px;
        }
        
        @media (max-width: 992px) {
            .schedule-grid { grid-template-columns: repeat(2, 1fr); }
        }
        
        @media (max-width: 768px) {
            body { padding: 20px 10px; }
            .box { padding: 20px 15px; }
            .head { flex-direction: column; align-items: flex-start; }
            .form-row { flex-direction: column; align-items: stretch; }
            .form-group select { width: 100%; }
            .btn { width: 100%; justify-content: center; }
            .schedule-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="wrap">
    <div class="box">
        <div class="head">
            <div class="head-left">
                <div class="icon-wrapper">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <h2>Расписание</h2>
            </div>
            <span class="class-badge"><?= $grade_name . $class_name ?></span>
        </div>

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
                <div class="form-group" style="justify-content: flex-end;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                        Показать
                    </button>
                </div>
            </div>
        </form>

        <?php if (!empty($current_schedule)): ?>
            <div class="schedule-grid">
                <?php foreach ($days as $day => $dayName): ?>
                    <div class="day-card">
                        <div class="day-header">
                            <i class="fas <?= $dayIcons[$day] ?>"></i>
                            <?= $dayName ?>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>№</th>
                                    <th>Предмет</th>
                                    <th>Учитель</th>
                                    <th>Каб.</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php for ($l = 1; $l <= 7; $l++): 
                                    $lesson = $current_schedule[$day][$l] ?? null;
                                    $subject = $lesson['subject_display'] ?? $lesson['subject'] ?? '';
                                    $teacher_name = $lesson['teacher_full_name'] ?? '';
                                    $room = $lesson['room'] ?? '';
                                    
                                    // Если предмет в списке noTeacherSubjects - учитель пустой
                                    if (in_array($subject, $noTeacherSubjects)) {
                                        $teacher_name = '';
                                    }
                                ?>
                                    <tr>
                                        <td class="lesson-num"><?= $l ?></td>
                                        <?php if (!empty($subject)): ?>
                                            <td class="subject-name"><?= htmlspecialchars($subject) ?></td>
                                            <td class="teacher-name">
                                                <?= !empty($teacher_name) ? htmlspecialchars(trim($teacher_name)) : '—' ?>
                                            </td>
                                            <td class="room"><?= htmlspecialchars($room) ?></td>
                                        <?php else: ?>
                                            <td colspan="3" class="empty-subject">—</td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-data">
                <i class="fas fa-calendar-times"></i>
                <h3>Расписание не найдено</h3>
                <p>Для класса <?= $grade_name . $class_name ?> пока нет расписания</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="js/bootstrap.bundle.js"></script>
</body>
</html>