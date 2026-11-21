<?php
session_start();
date_default_timezone_set("Asia/Dushanbe");
require_once("header.php");
require_once("config2.php");

// ============ РАСПИСАНИЕ ЗВОНКОВ ============
// 1 смена — 8–11 классы (утро)
$lessonTimesShift1 = [
    1 => ['08:00', '08:45'],
    2 => ['08:50', '09:35'],
    3 => ['09:40', '10:25'],
    4 => ['10:35', '11:20'],
    5 => ['11:25', '12:10'],
    6 => ['12:15', '13:00'],
];
// 2 смена — 5–7 классы (после обеда)
$lessonTimesShift2 = [
    1 => ['13:05', '13:50'],
    2 => ['13:55', '14:40'],
    3 => ['14:45', '15:30'],
    4 => ['15:45', '16:30'],
    5 => ['16:45', '17:30'],
    6 => ['17:35', '18:20'],
];

// ============ ДНИ НЕДЕЛИ ============
$daysRu = [
    'Monday'    => 'Понедельник',
    'Tuesday'   => 'Вторник',
    'Wednesday' => 'Среда',
    'Thursday'  => 'Четверг',
    'Friday'    => 'Пятница',
    'Saturday'  => 'Суббота',
    'Sunday'    => 'Воскресенье',
];

$currentTime = date('H:i');
$todayEn     = date('l');
$today       = $daysRu[$todayEn];
$dayOfWeek   = (int) date('N');          // 1 = Пн … 7 = Вс
$isWeekend   = ($dayOfWeek == 7);

// ============ ПЕРЕМЕННЫЕ СОСТОЯНИЯ ============
$selectedGrade = '';
$selectedClass = '';
$currentLesson = null;
$isBreak       = false;
$isFinished    = false;
$isNotStarted  = false;
$lessonStart   = '';
$lessonEnd     = '';
$shift         = '';
$lessons       = [];
$currentNum    = 0;
$totalLessons  = 0;

// ============ ОСНОВНАЯ ЛОГИКА ============
if (!$isWeekend && isset($_POST['grade_class']) && $_POST['grade_class'] !== '') {

    $gradeClass    = trim($_POST['grade_class']);
    $selectedGrade = intval($gradeClass);
    $selectedClass = substr($gradeClass, strlen((string)$selectedGrade));

    // 8–11 → 1 смена, 5–7 → 2 смена
    $isFirstShift = ($selectedGrade >= 8);
    $times = $isFirstShift ? $lessonTimesShift1 : $lessonTimesShift2;
    $shift = $isFirstShift ? '1 смена' : '2 смена';

    // Поддерживаем и английские названия дней, которые сохраняет админка,
    // и числовые значения из старых записей расписания.
    $stmt = $conn->prepare(
        "SELECT s.*,
                sub.name AS subject_name,
                CONCAT_WS(' ', t.surname, t.name) AS teacher_name
         FROM schedule s
         LEFT JOIN subjects sub ON sub.id = s.subject_id
         LEFT JOIN teachers t ON t.id = s.teacher_id
         WHERE s.grade = ? AND s.class = ? AND s.day_of_week = ?
         ORDER BY lesson_number"
    );
    $stmt->bind_param("iss", $selectedGrade, $selectedClass, $todayEn);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $lessons[(int)$row['lesson_number']] = $row;
    }
    $stmt->close();

    // --- Определяем текущий урок ---
    foreach ($lessons as $lessonNum => $row) {
        if (!isset($times[$lessonNum])) continue;
        [$start, $end] = $times[$lessonNum];
        if ($currentTime >= $start && $currentTime < $end) {
            $currentLesson = $row;
            $lessonStart   = $start;
            $lessonEnd     = $end;
            break;
        }
    }

    // --- Если урок не идёт — определяем перемену / конец / начало ---
    if (!$currentLesson) {

        if (empty($lessons)) {
            // Расписания на сегодня нет вообще
            $isNotStarted = false;
            $isFinished   = false;
        } else {
            // Строим таймлайн ТОЛЬКО из реальных уроков класса
            $realSlots = [];
            foreach ($lessons as $num => $row) {
                if (isset($times[$num])) {
                    $realSlots[$num] = $times[$num];
                }
            }

            if (empty($realSlots)) {
                $isNotStarted = false;
                $isFinished   = false;
            } else {
                $firstStart = $realSlots[array_key_first($realSlots)][0];
                $lastEnd    = $realSlots[array_key_last($realSlots)][1];

                if ($currentTime < $firstStart) {
                    $isNotStarted = true;
                } elseif ($currentTime >= $lastEnd) {
                    // После последнего РЕАЛЬНОГО урока класса
                    $isFinished = true;
                } else {
                    // Между первым и последним уроком, но не на уроке → перемена
                    $isBreak = true;
                }
            }
        }
    }

    // --- Мета для карточки ---
    $currentNum   = $currentLesson['lesson_number'] ?? 0;
    $totalLessons = !empty($lessons) ? max(array_keys($lessons)) : 0;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>СКАН — Текущий урок</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        :root {
            --primary: #4f46e5;
            --primary-light: #818cf8;
            --secondary: #7c3aed;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --bg-main: #f9fafb;
            --bg-card: #ffffff;
            --border-color: #e5e7eb;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --radius: 12px;
            --radius-lg: 16px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: var(--bg-main);
            color: var(--text-primary);
            -webkit-font-smoothing: antialiased;
            padding-top: 0 !important;
        }

        .lesson-section {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 24px;
        }

        /* ============ АНИМАЦИИ ============ */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .anim-up {
            animation: fadeUp 0.6s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
            opacity: 0;
        }
        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }
        .delay-4 { animation-delay: 0.4s; }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
            100% { transform: translateY(0px); }
        }
        .float-icon {
            animation: float 3s ease-in-out infinite;
            display: inline-block;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(79, 70, 229, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(79, 70, 229, 0); }
            100% { box-shadow: 0 0 0 0 rgba(79, 70, 229, 0); }
        }
        .pulse-badge { animation: pulse 2s infinite; }

        /* ============ HEADER ============ */
        .section-header { text-align: center; margin-bottom: 40px; }
        .section-header .tag {
            display: inline-block;
            padding: 4px 16px;
            background: rgba(79, 70, 229, 0.08);
            border-radius: 100px;
            font-size: 11px;
            font-weight: 600;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 12px;
        }
        .section-header h1 {
            font-size: 34px;
            font-weight: 800;
            color: var(--text-primary);
            letter-spacing: -0.02em;
        }
        .section-header p {
            font-size: 16px;
            color: var(--text-secondary);
            margin-top: 8px;
        }

        /* ============ CARD BASE ============ */
        .lesson-card {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            padding: 40px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 24px;
            transition: var(--transition);
        }
        .lesson-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-4px);
        }

        /* ============ FORM ============ */
        .lesson-form {
            display: flex;
            gap: 16px;
            justify-content: center;
            margin-bottom: 32px;
            background: var(--bg-card);
            padding: 24px;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }
        .lesson-form:hover { box-shadow: var(--shadow-lg); }
        .lesson-form select {
            padding: 12px 20px;
            border-radius: var(--radius);
            border: 1px solid var(--border-color);
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            background: var(--bg-main);
            color: var(--text-primary);
            outline: none;
            transition: var(--transition);
            width: 100%;
            max-width: 300px;
            cursor: pointer;
            font-weight: 500;
        }
        .lesson-form select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            background: #fff;
        }
        .lesson-form button {
            padding: 12px 32px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #fff;
            border: none;
            border-radius: var(--radius);
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Inter', sans-serif;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.25);
        }
        .lesson-form button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(79, 70, 229, 0.35);
        }

        /* ============ INFO BAR ============ */
        .lesson-info-bar {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 32px;
        }
        .lesson-info-item {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 20px;
            text-align: center;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }
        .lesson-info-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        .lesson-info-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .lesson-info-value {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary);
        }

        /* ============ ACTIVE LESSON ============ */
        .lesson-card-active {
            background: linear-gradient(135deg, #ffffff, #f9fafb);
            border: 2px solid var(--primary);
            position: relative;
            overflow: hidden;
        }
        .lesson-card-active::before {
            content: '';
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }
        .lesson-card-badge {
            position: absolute;
            top: 20px; right: 20px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #fff;
            padding: 6px 16px;
            border-radius: 100px;
            font-size: 13px;
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }
        .lesson-card-title {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 32px;
            margin-top: 10px;
        }
        .lesson-card-title .lesson-card-icon { font-size: 32px; }
        .lesson-card-title h3 {
            font-size: 28px;
            font-weight: 800;
            color: var(--text-primary);
            letter-spacing: -0.02em;
        }

        /* ============ META GRID ============ */
        .lesson-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 32px;
        }
        .lesson-meta-item {
            display: flex;
            align-items: center;
            gap: 16px;
            background: var(--bg-main);
            padding: 16px 20px;
            border-radius: var(--radius);
            border: 1px solid var(--border-color);
            transition: var(--transition);
        }
        .lesson-meta-item:hover {
            background: #fff;
            border-color: rgba(79, 70, 229, 0.2);
            transform: scale(1.02);
        }
        .lesson-meta-icon { font-size: 24px; }
        .lesson-meta-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .lesson-meta-value {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-primary);
        }

        /* ============ PROGRESS ============ */
        .lesson-progress {
            height: 12px;
            background: var(--bg-main);
            border-radius: 100px;
            overflow: hidden;
            margin-bottom: 12px;
            border: 1px solid var(--border-color);
        }
        .lesson-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            border-radius: 100px;
            transition: width 1s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .lesson-progress-caption {
            text-align: center;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-secondary);
        }

        /* ============ BREAK / WEEKEND / FINISHED ============ */
        .lesson-card-break {
            text-align: center;
            padding: 60px 40px;
            background: var(--bg-card);
        }
        .lesson-big-icon {
            font-size: 64px;
            margin-bottom: 20px;
            display: inline-block;
        }
        .lesson-card-break h3 {
            font-size: 26px;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 10px;
        }
        .lesson-card-break p {
            font-size: 16px;
            color: var(--text-secondary);
        }

        .weekend-card {
            border-color: rgba(239, 68, 68, 0.2);
            background: linear-gradient(135deg, #fff, #fef2f2);
        }
        .weekend-card h3 { color: var(--danger); }

        /* Empty State */
        .lesson-empty {
            text-align: center;
            padding: 60px 20px;
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
        }
        .lesson-empty-icon {
            font-size: 64px;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        .lesson-empty p {
            font-size: 16px;
            color: var(--text-secondary);
        }

        @media (max-width: 768px) {
            .lesson-info-bar { grid-template-columns: 1fr 1fr; }
            .lesson-meta { grid-template-columns: 1fr; }
            .lesson-form { flex-direction: column; align-items: stretch; }
            .lesson-form select { max-width: none; }
        }
        @media (max-width: 480px) {
            .lesson-info-bar { grid-template-columns: 1fr; }
            .lesson-card { padding: 24px 16px; }
            .lesson-card-title h3 { font-size: 22px; }
        }
    </style>
</head>
<body>

<div class="lesson-section">

    <!-- HEADER -->
    <div class="section-header anim-up">
        <div class="tag">Электронный журнал</div>
        <h1>Текущий урок в классе</h1>
        <p>Платформа мониторинга образования · СКАН</p>
    </div>

    <!-- ФОРМА -->
    <?php if (!$isWeekend): ?>
    <form method="POST" class="lesson-form anim-up delay-1">
        <select name="grade_class" required>
            <option value="">— Выберите класс —</option>
            <?php
            $resGrades = $conn->query("SELECT DISTINCT grade, class FROM schedule ORDER BY grade, class");
            while ($row = $resGrades->fetch_assoc()) {
                $val = $row['grade'] . $row['class'];
                $selected = ((string)$row['grade'] === (string)$selectedGrade
                             && (string)$row['class'] === (string)$selectedClass) ? 'selected' : '';
                echo "<option value='" . htmlspecialchars($val) . "' {$selected}>" . htmlspecialchars($val) . "</option>";
            }
            ?>
        </select>
        <button type="submit">Показать</button>
    </form>
    <?php endif; ?>

    <!-- ЛОГИКА ОТОБРАЖЕНИЯ -->
    <?php if ($isWeekend): ?>

        <!-- ВЫХОДНОЙ -->
        <div class="lesson-card lesson-card-break weekend-card anim-up delay-2">
            <div class="lesson-big-icon float-icon">🎉</div>
            <h3>Сегодня Выходной!</h3>
            <p>Уроков нет. Отдыхайте, набирайтесь сил и хорошо проводите время!</p>
        </div>

    <?php elseif ($selectedGrade && $selectedClass): ?>

        <!-- ИНФО-СТРОКА -->
        <div class="lesson-info-bar anim-up delay-2">
            <div class="lesson-info-item">
                <span class="lesson-info-label">Класс</span>
                <span class="lesson-info-value"><?= htmlspecialchars($selectedGrade . $selectedClass) ?></span>
            </div>
            <div class="lesson-info-item">
                <span class="lesson-info-label">Смена</span>
                <span class="lesson-info-value"><?= htmlspecialchars($shift) ?></span>
            </div>
            <div class="lesson-info-item">
                <span class="lesson-info-label">Время</span>
                <span class="lesson-info-value"><?= htmlspecialchars($currentTime) ?></span>
            </div>
            <div class="lesson-info-item">
                <span class="lesson-info-label">День</span>
                <span class="lesson-info-value"><?= htmlspecialchars($today) ?></span>
            </div>
        </div>

        <?php if ($currentLesson): ?>

            <!-- КАРТОЧКА УРОКА -->
            <div class="lesson-card lesson-card-active anim-up delay-3">
                <div class="lesson-card-badge pulse-badge">Урок <?= htmlspecialchars($currentNum) ?></div>

                <div class="lesson-card-title">
                    <span class="lesson-card-icon">📖</span>
                    <h3><?= htmlspecialchars($currentLesson['subject_name'] ?? $currentLesson['subject'] ?? '') ?></h3>
                </div>

                <div class="lesson-meta">
                    <div class="lesson-meta-item">
                        <span class="lesson-meta-icon">👨‍🏫</span>
                        <div>
                            <div class="lesson-meta-label">Учитель</div>
                            <div class="lesson-meta-value"><?= htmlspecialchars($currentLesson['teacher_name'] ?? '') ?></div>
                        </div>
                    </div>
                    <div class="lesson-meta-item">
                        <span class="lesson-meta-icon">🚪</span>
                        <div>
                            <div class="lesson-meta-label">Кабинет</div>
                            <div class="lesson-meta-value"><?= htmlspecialchars($currentLesson['room']) ?></div>
                        </div>
                    </div>
                    <div class="lesson-meta-item">
                        <span class="lesson-meta-icon">⏰</span>
                        <div>
                            <div class="lesson-meta-label">Время урока</div>
                            <div class="lesson-meta-value"><?= htmlspecialchars($lessonStart) ?> – <?= htmlspecialchars($lessonEnd) ?></div>
                        </div>
                    </div>
                    <div class="lesson-meta-item">
                        <span class="lesson-meta-icon">🔢</span>
                        <div>
                            <div class="lesson-meta-label">Номер урока</div>
                            <div class="lesson-meta-value"><?= htmlspecialchars($currentNum) ?> из <?= htmlspecialchars($totalLessons) ?></div>
                        </div>
                    </div>
                </div>

                <?php if ($totalLessons > 0): ?>
                    <div class="lesson-progress">
                        <div class="lesson-progress-bar" style="width: <?= round($currentNum / $totalLessons * 100) ?>%;"></div>
                    </div>
                    <div class="lesson-progress-caption">
                        Прогресс дня: <?= $currentNum ?> / <?= $totalLessons ?> уроков
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif ($isBreak): ?>
            <div class="lesson-card lesson-card-break anim-up delay-3">
                <div class="lesson-big-icon float-icon">☕</div>
                <h3>Перемена!</h3>
                <p>У <?= htmlspecialchars($selectedGrade . $selectedClass) ?> класса сейчас перемена.</p>
            </div>

        <?php elseif ($isFinished): ?>
            <div class="lesson-card lesson-card-break anim-up delay-3">
                <div class="lesson-big-icon float-icon">🎉</div>
                <h3>Уроки окончены!</h3>
                <p>У <?= htmlspecialchars($selectedGrade . $selectedClass) ?> класса уроки подошли к концу.</p>
            </div>

        <?php elseif ($isNotStarted): ?>
            <div class="lesson-card lesson-card-break anim-up delay-3">
                <div class="lesson-big-icon float-icon">🌅</div>
                <h3>Уроки ещё не начались</h3>
                <p>У <?= htmlspecialchars($selectedGrade . $selectedClass) ?> класса сегодня ещё нет уроков.</p>
            </div>

        <?php else: ?>
            <div class="lesson-empty anim-up delay-3">
                <div class="lesson-empty-icon float-icon">📭</div>
                <p>На сегодня расписание для <?= htmlspecialchars($selectedGrade . $selectedClass) ?> класса не найдено.</p>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="lesson-empty anim-up delay-2">
            <div class="lesson-empty-icon float-icon">🎓</div>
            <p>Выберите класс в меню выше, чтобы увидеть текущий урок</p>
        </div>
    <?php endif; ?>

</div>

<?php require_once("js.php"); ?>

</body>
</html> 