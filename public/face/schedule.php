<?php
session_start();

if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$lang = $_SESSION['lang'] ?? 'ru';

require_once("1.php");
require_once("config2.php");
require_once("header.php");

if ($conn->connect_error) die("Ошибка подключения: " . $conn->connect_error);

$grade = isset($_GET['grade']) ? intval($_GET['grade']) : 5;
$class = isset($_GET['class']) ? $_GET['class'] : 'А';

$grade_safe = intval($grade);
$class_safe = $conn->real_escape_string($class);

/* ============================================================
   ОСНОВНОЙ ЗАПРОС
   ============================================================ */
$sql = "
    SELECT
        s.day_of_week,
        s.lesson_number,
        sub.name AS subject,
        s.room,
        t.surname AS teacher_surname,
        t.name AS teacher_name
    FROM schedule s
    LEFT JOIN subjects sub ON sub.id = s.subject_id
    LEFT JOIN teachers t ON t.id = s.teacher_id
    WHERE s.grade = $grade_safe AND s.class = '$class_safe'
    ORDER BY FIELD(s.day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), s.lesson_number
";

$result = mysqli_query($conn, $sql);
if (!$result) {
    die("Ошибка запроса: " . mysqli_error($conn));
}

/* ============================================================
   СБОР ДАННЫХ
   ============================================================ */
$schedule = [];
while ($row = mysqli_fetch_assoc($result)) {
    $teacher_name = trim(($row['teacher_surname'] ?? '') . ' ' . ($row['teacher_name'] ?? ''));
    $row['teacher_name'] = $teacher_name;

    $key = $row['lesson_number'];

    if (!isset($schedule[$row['day_of_week']][$key])) {
        $schedule[$row['day_of_week']][$key] = $row;
    }
}

// Сортируем уроки по номеру
foreach ($schedule as $day => &$lessons) {
    ksort($lessons);
}
unset($lessons);

$conn->close();

$days_ru = [
    'Monday'    => 'Понедельник',
    'Tuesday'   => 'Вторник',
    'Wednesday' => 'Среда',
    'Thursday'  => 'Четверг',
    'Friday'    => 'Пятница',
    'Saturday'  => 'Суббота'
];
?>
<!doctype html>
<html class="no-js" lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>СКАН — Расписание занятий</title>

    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        /* --- ВАШИ СТИЛИ ОСТАЮТСЯ БЕЗ ИЗМЕНЕНИЙ --- */
        body {
            font-family: 'Inter', -apple-system, sans-serif !important;
            background: #f9fafb !important;
            color: #1f2937 !important;
            padding-top: 0 !important;
            margin-top: 0 !important;
            -webkit-font-smoothing: antialiased;
        }
        .custom-container { max-width: 1280px; margin: 30px auto 0 auto; padding: 0 24px; width: 100%; }
        .page-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 28px; }
        .page-header h1 { font-size: 28px; font-weight: 800; color: #1d1d1f; letter-spacing: -0.02em; display: flex; align-items: center; gap: 12px; margin: 0; }
        .page-header h1 .icon-wrap { width: 48px; height: 48px; background: linear-gradient(135deg, #4f46e5, #7c3aed); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; color: #fff; box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3); }
        .page-header h1 span { background: linear-gradient(135deg, #4f46e5, #7c3aed); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .page-header .class-badge { background: linear-gradient(135deg, #4f46e5, #7c3aed); color: #fff; padding: 10px 24px; border-radius: 100px; font-size: 16px; font-weight: 700; box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3); }
        .controls { display: flex; flex-wrap: wrap; gap: 16px; align-items: center; background: #ffffff; padding: 20px 24px; border-radius: 16px; border: 1px solid #e5e7eb; margin-bottom: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .controls label { font-size: 12px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin: 0; }
        .controls select { padding: 10px 16px; border-radius: 8px; border: 1px solid #e5e7eb; font-size: 14px; font-family: 'Inter', sans-serif; background: #f9fafb; color: #1f2937; outline: none; transition: all 0.3s; min-width: 100px; cursor: pointer; display: inline-block; width: auto; }
        .controls select:focus { border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); background: #fff; }
        .controls .btn-submit { padding: 10px 28px; background: linear-gradient(135deg, #4f46e5, #7c3aed); color: #fff; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.3s; font-family: 'Inter', sans-serif; box-shadow: 0 4px 15px rgba(79, 70, 229, 0.25); display: inline-block; }
        .controls .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(79, 70, 229, 0.35); }
        .schedule-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
        .schedule-card { background: #ffffff; border-radius: 16px; border: 1px solid #e5e7eb; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .schedule-card:hover { box-shadow: 0 10px 30px rgba(79, 70, 229, 0.1); transform: translateY(-4px); border-color: rgba(79, 70, 229, 0.2); }
        .schedule-card .day-head { padding: 16px 20px; background: #f9fafb; font-weight: 700; font-size: 16px; color: #1f2937; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; gap: 12px; }
        .schedule-card .day-head .day-icon { width: 36px; height: 36px; background: rgba(79, 70, 229, 0.1); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #4f46e5; font-size: 16px; }
        .schedule-card .day-body { padding: 8px 0; }
        .schedule-card .lesson { display: flex; align-items: center; padding: 14px 20px; border-bottom: 1px solid #f3f4f6; gap: 12px; transition: background 0.2s; }
        .schedule-card .lesson:hover { background: #f9fafb; }
        .schedule-card .lesson:last-child { border-bottom: none; }
        .schedule-card .lesson .num { font-weight: 700; font-size: 13px; color: #4f46e5; width: 28px; height: 28px; background: rgba(79, 70, 229, 0.1); border-radius: 6px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .schedule-card .lesson .info { flex: 1; min-width: 0; }
        .schedule-card .lesson .info .subject { font-weight: 600; font-size: 14px; color: #1f2937; }
        .schedule-card .lesson .info .teacher { font-size: 12px; color: #6b7280; margin-top: 4px; }
        .schedule-card .lesson .info .teacher i { margin-right: 4px; font-size: 11px; color: #4f46e5; }
        .schedule-card .lesson .room { font-size: 11px; color: #4f46e5; background: rgba(79, 70, 229, 0.08); padding: 4px 12px; border-radius: 100px; font-weight: 600; flex-shrink: 0; border: 1px solid rgba(79, 70, 229, 0.15); }
        .schedule-card .empty { padding: 40px 20px; text-align: center; color: #9ca3af; font-size: 13px; }
        .schedule-card .empty i { display: block; font-size: 32px; margin-bottom: 8px; color: #e5e7eb; }
        .fade-up { opacity: 0; transform: translateY(30px); transition: all 0.7s cubic-bezier(0.23, 1, 0.32, 1); }
        .fade-up.visible { opacity: 1; transform: translateY(0); }
        .delay-1 { transition-delay: 0.05s; } .delay-2 { transition-delay: 0.10s; } .delay-3 { transition-delay: 0.15s; }
        .delay-4 { transition-delay: 0.20s; } .delay-5 { transition-delay: 0.25s; } .delay-6 { transition-delay: 0.30s; }
        @media (max-width: 1024px) { .schedule-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 768px) { .custom-container { margin-top: 20px; padding: 0 16px; } .page-header { flex-direction: column; align-items: flex-start; } .controls { flex-direction: column; align-items: stretch; padding: 16px; } .controls select, .controls .btn-submit { width: 100%; } .schedule-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<div class="custom-container">

    <!-- HEADER -->
    <div class="page-header fade-up visible">
        <h1>
            <span class="icon-wrap"><i class="fas fa-calendar-alt"></i></span>
            Расписание <span><?= htmlspecialchars($grade . $class) ?></span> класса
        </h1>
        <span class="class-badge"><?= htmlspecialchars($grade . $class) ?></span>
    </div>

    <!-- CONTROLS -->
    <form method="get" class="controls fade-up visible">
        <label for="gradeSelect">Класс</label>
        <select id="gradeSelect" name="grade">
            <?php for($g = 5; $g <= 11; $g++): ?>
                <option value="<?= $g ?>" <?= $g == $grade ? 'selected' : '' ?>><?= $g ?></option>
            <?php endfor; ?>
        </select>

        <select name="class">
            <?php foreach(['А', 'Б', 'В'] as $c): ?>
                <option value="<?= $c ?>" <?= $c == $class ? 'selected' : '' ?>><?= $c ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn-submit">
            <i class="fas fa-search" style="margin-right:6px;"></i> Показать
        </button>
    </form>

    <!-- SCHEDULE -->
    <div class="schedule-grid">
        <?php
        $day_index = 0;
        foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $day):
            $day_index++;
            $delay = 'delay-' . ($day_index % 6 + 1);
            $icons = [
                'Monday'    => 'fa-sun',
                'Tuesday'   => 'fa-cloud',
                'Wednesday' => 'fa-cloud-sun',
                'Thursday'  => 'fa-smog',
                'Friday'    => 'fa-star',
                'Saturday'  => 'fa-moon'
            ];
        ?>
        <div class="schedule-card fade-up <?= $delay ?>">
            <div class="day-head">
                <span class="day-icon"><i class="fas <?= $icons[$day] ?>"></i></span>
                <?= $days_ru[$day] ?>
            </div>
            <div class="day-body">
                <?php if(isset($schedule[$day]) && !empty($schedule[$day])): ?>
                    <?php foreach($schedule[$day] as $lesson): ?>
                        <div class="lesson">
                            <span class="num"><?= $lesson['lesson_number'] ?></span>
                            <div class="info">
                                <div class="subject"><?= htmlspecialchars($lesson['subject']) ?></div>
                                <?php if(!empty($lesson['teacher_name'])): ?>
                                    <div class="teacher">
                                        <i class="fas fa-user-graduate"></i>
                                        <?= htmlspecialchars($lesson['teacher_name']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <span class="room"><?= htmlspecialchars($lesson['room']) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty">
                        <i class="fas fa-book-open"></i>
                        Нет уроков
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -20px 0px' });

        document.querySelectorAll('.schedule-card.fade-up').forEach(el => observer.observe(el));
    });
</script>

</body>
</html>