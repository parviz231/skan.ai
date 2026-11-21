<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');
date_default_timezone_set('Asia/Dushanbe');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['lang'])) { $_SESSION['lang'] = 'ru'; }
if (empty($_SESSION['id1'])) {
    header("Location: login.php");
    exit;
}

require_once("config2.php");

if (!$conn->set_charset('utf8mb4')) {
    error_log('Profile charset setup failed: ' . $conn->error);
    http_response_code(500);
    exit('Не удалось загрузить профиль. Попробуйте позже.');
}

$student_id = (int)$_SESSION['id1'];

/* ДАННЫЕ УЧЕНИКА */
$sql = "
SELECT u.id, u.surname, u.name, u.patronymic, u.gender, u.birth, u.grade, u.class,
    COALESCE(attendance.attendance, 0) AS attendance,
    COALESCE(grades.avg_grade, 0) AS avg_grade,
    ROUND((COALESCE(attendance.attendance, 0) + COALESCE(grades.avg_grade * 10, 0)) / 2, 2) AS rating
FROM users u
LEFT JOIN (
    SELECT daily.user_id, ROUND(AVG(daily.visit IN ('visited', 'late')) * 100, 2) AS attendance
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
) attendance ON attendance.user_id = u.id
LEFT JOIN (
    SELECT user_id, ROUND(AVG(CAST(grade_value AS DECIMAL(10,2))), 2) AS avg_grade
    FROM grades
    WHERE grade_value REGEXP '^[0-9]+$'
    GROUP BY user_id
) grades ON grades.user_id = u.id
WHERE u.id = ? LIMIT 1
";

$stmt_student = $conn->prepare($sql);
if (!$stmt_student) {
    error_log('Profile student query preparation failed: ' . $conn->error);
    http_response_code(500);
    exit('Не удалось загрузить профиль. Попробуйте позже.');
}
$stmt_student->bind_param("i", $student_id);
if (!$stmt_student->execute()) {
    error_log('Profile student query failed: ' . $stmt_student->error);
    $stmt_student->close();
    http_response_code(500);
    exit('Не удалось загрузить профиль. Попробуйте позже.');
}
$result_student = $stmt_student->get_result();
$student = $result_student->fetch_assoc();
$stmt_student->close();

if (!$student) {
    $stmt = $conn->prepare("SELECT user_id, nickname FROM students WHERE user_id = ? LIMIT 1");
    if (!$stmt) {
        error_log('Profile fallback query preparation failed: ' . $conn->error);
        http_response_code(500);
        exit('Не удалось загрузить профиль. Попробуйте позже.');
    }
    $stmt->bind_param("i", $student_id);
    if (!$stmt->execute()) {
        error_log('Profile fallback query failed: ' . $stmt->error);
        $stmt->close();
        http_response_code(500);
        exit('Не удалось загрузить профиль. Попробуйте позже.');
    }
    $result = $stmt->get_result();
    $student_data = $result->fetch_assoc();
    $stmt->close();
    if ($student_data) {
        $student = ['id' => $student_data['user_id'], 'surname' => 'Новый', 'name' => 'Пользователь', 'patronymic' => '', 'gender' => 'male', 'birth' => date('Y-m-d'), 'grade' => 5, 'class' => 'A', 'attendance' => 0, 'avg_grade' => 0, 'rating' => 0];
    } else {
        http_response_code(404);
        exit('Профиль пользователя не найден.');
    }
}

/* ДАТА И ПЕРИОД */
$selected_date = date('Y-m-d');
$hasDateFilter = false;
if (isset($_GET['date']) && is_string($_GET['date']) && $_GET['date'] !== '') {
    $requested_date = DateTimeImmutable::createFromFormat('!Y-m-d', $_GET['date']);
    $date_errors = DateTimeImmutable::getLastErrors();
    if (
        $requested_date !== false
        && ($date_errors === false || ($date_errors['warning_count'] === 0 && $date_errors['error_count'] === 0))
        && $requested_date->format('Y-m-d') === $_GET['date']
    ) {
        $selected_date = $_GET['date'];
        $hasDateFilter = true;
    }
}

/* ПЕРИОД ДЛЯ ОЦЕНОК */
if ($hasDateFilter) {
    $periodStart = $selected_date;
    $periodEnd   = $selected_date;
} else {
    $now = new DateTimeImmutable('now');
    $m = (int)$now->format('n');
    $y = (int)$now->format('Y');
    $studyYearStart = ($m >= 9) ? $y : $y - 1;
    if ($m >= 9 && $m <= 10) {
        $qStart = "$studyYearStart-09-01"; $qEnd = "$studyYearStart-10-31";
    } elseif ($m >= 11 && $m <= 12) {
        $qStart = "$studyYearStart-11-01"; $qEnd = "$studyYearStart-12-31";
    } elseif ($m >= 1 && $m <= 3) {
        $qStart = ($studyYearStart + 1) . "-01-01"; $qEnd = ($studyYearStart + 1) . "-03-31";
    } else {
        $qStart = ($studyYearStart + 1) . "-04-01"; $qEnd = ($studyYearStart + 1) . "-05-31";
    }
    $periodStart = $qStart;
    $periodEnd   = $qEnd;
}

/* Текущая четверть для анализа предметов — те же даты, что и в student_prediction.php */
$currentQuarter = 1;
$currentQuarterYear = (int)date('Y');
$currentMonth = (int)date('n');
if ($currentMonth >= 9 && $currentMonth <= 10) {
    $currentQuarter = 1;
} elseif ($currentMonth >= 11 && $currentMonth <= 12) {
    $currentQuarter = 2;
} elseif ($currentMonth >= 1 && $currentMonth <= 3) {
    $currentQuarter = 3;
} elseif ($currentMonth >= 4 && $currentMonth <= 5) {
    $currentQuarter = 4;
} else {
    $currentQuarter = 4;
}
$quarterPeriods = [
    1 => ["start" => "$currentQuarterYear-09-01", "end" => "$currentQuarterYear-10-31"],
    2 => ["start" => "$currentQuarterYear-11-01", "end" => "$currentQuarterYear-12-31"],
    3 => ["start" => "$currentQuarterYear-01-01", "end" => "$currentQuarterYear-03-31"],
    4 => ["start" => "$currentQuarterYear-04-01", "end" => "$currentQuarterYear-05-31"]
];
$analysisPeriodStart = $quarterPeriods[$currentQuarter]['start'];
$analysisPeriodEnd = $quarterPeriods[$currentQuarter]['end'];

/* ПОСЕЩАЕМОСТЬ */
$stmt_visit = $conn->prepare("SELECT visit FROM visits WHERE user_id = ? AND DATE(time) = ? ORDER BY time DESC LIMIT 1");
if ($stmt_visit) {
    $stmt_visit->bind_param("is", $student_id, $selected_date);
    $stmt_visit->execute();
    $visit_result = $stmt_visit->get_result();
    $visit_row = $visit_result->fetch_assoc();
    $stmt_visit->close();
    $is_absent_today = $visit_row && (mb_strtolower((string)$visit_row['visit'], 'UTF-8') === 'did not visit');
} else {
    $is_absent_today = false;
}

/* ОЦЕНКИ — за выбранный день или за весь период */
$stmt = $conn->prepare("
    SELECT
        sub.name AS subject,
        g.grade_value,
        g.grade_date AS grade_day
    FROM grades g
    JOIN subjects sub ON sub.id = g.subject_id
    WHERE g.user_id = ?
      AND g.grade_date BETWEEN ? AND ?
    ORDER BY g.grade_date DESC, sub.name, g.id
");
if (!$stmt) {
    error_log('Profile grades query preparation failed: ' . $conn->error);
    http_response_code(500);
    exit('Не удалось загрузить оценки. Попробуйте позже.');
}
$stmt->bind_param("iss", $student_id, $periodStart, $periodEnd);
if (!$stmt->execute()) {
    error_log('Profile grades query failed: ' . $stmt->error);
    $stmt->close();
    http_response_code(500);
    exit('Не удалось загрузить оценки. Попробуйте позже.');
}
$grades_result = $stmt->get_result();
$stmt->close();

/* Оценки по предметам строго за выбранную дату для расписания */
$dailyGradesBySubject = [];
$dailyGradeStmt = $conn->prepare('SELECT subject_id, grade_value FROM grades WHERE user_id = ? AND grade_date = ? ORDER BY id');
if (!$dailyGradeStmt) {
    error_log('Profile daily grades query preparation failed: ' . $conn->error);
    http_response_code(500);
    exit('Не удалось загрузить оценки. Попробуйте позже.');
}
$dailyGradeStmt->bind_param('is', $student_id, $selected_date);
if (!$dailyGradeStmt->execute()) {
    error_log('Profile daily grades query failed: ' . $dailyGradeStmt->error);
    $dailyGradeStmt->close();
    http_response_code(500);
    exit('Не удалось загрузить оценки. Попробуйте позже.');
}
$dailyGradeResult = $dailyGradeStmt->get_result();
while ($row = $dailyGradeResult->fetch_assoc()) {
    $dailyGradesBySubject[(int)$row['subject_id']][] = (string)($row['grade_value'] ?? '');
}
$dailyGradeStmt->close();

/* СИЛЬНЫЕ, СЛАБЫЕ И ДИНАМИКА ПРЕДМЕТОВ ЗА ТЕКУЩУЮ ЧЕТВЕРТЬ */
$stmt_subjects = $conn->prepare("
    SELECT sub.id AS subject_id, sub.name AS subject, g.grade_value
    FROM grades g
    JOIN subjects sub ON sub.id = g.subject_id
    WHERE g.user_id = ? AND g.grade_date BETWEEN ? AND ?
    ORDER BY g.grade_date ASC, g.id ASC
");
$strong = [];
$weak = [];
$subjectProgress = [];
$periodGradeSum = 0;
$periodGradeCount = 0;
$strongSubjectThreshold = 7;

if (!$stmt_subjects) {
    error_log('Profile subject stats query preparation failed: ' . $conn->error);
    http_response_code(500);
    exit('Не удалось загрузить анализ успеваемости. Попробуйте позже.');
}
$stmt_subjects->bind_param("iss", $student_id, $analysisPeriodStart, $analysisPeriodEnd);
if (!$stmt_subjects->execute()) {
    error_log('Profile subject stats query failed: ' . $stmt_subjects->error);
    $stmt_subjects->close();
    http_response_code(500);
    exit('Не удалось загрузить анализ успеваемости. Попробуйте позже.');
}
$subjectGradesById = [];
$subjectNamesById = [];
$subj_res = $stmt_subjects->get_result();
while ($row = $subj_res->fetch_assoc()) {
    $gradeValue = str_replace(',', '.', (string)($row['grade_value'] ?? ''));
    if (!is_numeric($gradeValue) || (float)$gradeValue <= 0 || (float)$gradeValue > 10) {
        continue;
    }
    $subjectId = (int)$row['subject_id'];
    $subjectGradesById[$subjectId][] = (float)$gradeValue;
    $subjectNamesById[$subjectId] = (string)$row['subject'];
    $periodGradeSum += (float)$gradeValue;
    $periodGradeCount++;
}
$stmt_subjects->close();

foreach ($subjectGradesById as $subjectId => $grades) {
    $subjectName = $subjectNamesById[$subjectId];
    $average = round(array_sum($grades) / count($grades), 2);
    if ($average >= $strongSubjectThreshold) {
        $strong[] = $subjectName . " (" . $average . ")";
    } else {
        $weak[] = $subjectName . " (" . $average . ")";
    }

    if (count($grades) >= 2) {
        $splitAt = (int)floor(count($grades) / 2);
        $earlierGrades = array_slice($grades, 0, $splitAt);
        $recentGrades = array_slice($grades, $splitAt);
        $earlierAverage = array_sum($earlierGrades) / count($earlierGrades);
        $recentAverage = array_sum($recentGrades) / count($recentGrades);
        $subjectProgress[] = [
            'subject' => $subjectName,
            'earlier' => round($earlierAverage, 2),
            'recent' => round($recentAverage, 2),
            'change' => round($recentAverage - $earlierAverage, 2),
        ];
    }
}
$periodAvgGrade = $periodGradeCount > 0 ? round($periodGradeSum / $periodGradeCount, 2) : 0;

/* РАСПИСАНИЕ */
$stmt2 = $conn->prepare("
    SELECT s.day_of_week, s.lesson_number, s.subject_id,
           sub.name AS subject, s.room,
           CONCAT(t.surname, ' ', LEFT(t.name, 1), '.') AS teacher_name
    FROM schedule s
    LEFT JOIN subjects sub ON sub.id = s.subject_id
    LEFT JOIN teachers t ON t.id = s.teacher_id
    WHERE s.grade = ? AND s.class = ? 
    ORDER BY FIELD(s.day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), s.lesson_number
");
if ($stmt2) {
    $stmt2->bind_param("is", $student['grade'], $student['class']);
    $stmt2->execute();
    $schedule_result = $stmt2->get_result();
    $schedule = [];
    while ($row = $schedule_result->fetch_assoc()) { $schedule[$row['day_of_week']][] = $row; }
    $stmt2->close();
} else {
    $schedule = [];
}

/* СТАТУС */
$status_class = "status-good";
$status_text = "Ученик учится хорошо и регулярно посещает занятия";
$rating = (float)$student['rating'];
$attendance = (float)$student['attendance'];

if ($rating < 20 || $attendance < 40) { $status_class = "status-bad"; $status_text = "Ученик подлежит исключению"; } 
elseif ($rating < 40 || $attendance < 60) { $status_class = "status-warning"; $status_text = "Ученик близок к отчислению"; }

require_once("header.php");
?>

<style>
    :root {
        --bg-primary: #f0f2f5; --bg-card: #ffffff; --accent: #4361ee; --accent-light: #7b8ff7;
        --accent-gradient: linear-gradient(135deg, #4361ee, #7b8ff7); --accent-glow: rgba(67, 97, 238, 0.25);
        --text-primary: #0a0e1a; --text-secondary: #5a6a7e; --text-light: #8a9bb0;
        --border: #e4e8f0; --shadow: 0 8px 40px rgba(67, 97, 238, 0.08); --shadow-hover: 0 16px 60px rgba(67, 97, 238, 0.16);
        --radius: 10px; --radius-sm: 8px; --radius-lg: 16px; --transition: 0.5s cubic-bezier(0.23, 1, 0.32, 1);
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Inter', -apple-system, sans-serif; background: #f0f2f5; color: #0a0e1a; -webkit-font-smoothing: antialiased; }
    ::-webkit-scrollbar { width: 6px; } ::-webkit-scrollbar-track { background: #f0f2f5; } ::-webkit-scrollbar-thumb { background: #4361ee; border-radius: 10px; }
    .profile-container { max-width: 1280px; margin: 24px auto; padding: 0 24px; }
    .profile-header { background: var(--bg-card); border-radius: var(--radius); padding: 24px 32px; margin-bottom: 24px; border: 1px solid var(--border); box-shadow: var(--shadow); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; }
    .profile-header .left { display: flex; align-items: center; gap: 16px; }
    .profile-header .left .avatar { width: 56px; height: 56px; border-radius: 50%; background: var(--accent-gradient); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 24px; font-weight: 700; }
    .profile-header .left .info h1 { font-size: 22px; font-weight: 700; color: var(--text-primary); }
    .profile-header .left .info .sub { font-size: 14px; color: var(--text-secondary); }
    .profile-header .left .info .sub span { background: var(--bg-primary); padding: 2px 12px; border-radius: 100px; font-weight: 600; color: var(--accent); margin: 0 4px; }
    .profile-header .right { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
    .profile-header .right .pill { background: var(--bg-primary); padding: 4px 14px; border-radius: 100px; font-size: 12px; font-weight: 600; color: var(--text-secondary); display: inline-flex; align-items: center; gap: 6px; }
    .profile-header .right .pill i { color: var(--accent); }
    .status-badge { padding: 8px 20px; border-radius: var(--radius-sm); font-weight: 600; font-size: 14px; text-align: center; margin-bottom: 24px; }
    .status-good { background: #d1fae5; color: #065f46; } .status-warning { background: #fef3c7; color: #92400e; } .status-bad { background: #fee2e2; color: #991b1b; }
    .profile-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; }
    .profile-card { background: var(--bg-card); border-radius: var(--radius); padding: 24px 28px; border: 1px solid var(--border); box-shadow: var(--shadow); transition: all var(--transition); }
    .profile-card:hover { box-shadow: var(--shadow-hover); }
    .profile-card .card-title { font-size: 16px; font-weight: 700; color: var(--text-primary); margin-bottom: 14px; display: flex; align-items: center; gap: 10px; }
    .profile-card .card-title i { color: var(--accent); font-size: 18px; }
    .profile-card .card-title .line { flex: 1; height: 1px; background: var(--border); }
    .info-list { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 16px; }
    .info-list .item { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid var(--border); font-size: 14px; }
    .info-list .item .label { color: var(--text-secondary); font-weight: 500; } .info-list .item .value { font-weight: 600; color: var(--text-primary); } .info-list .item .value.highlight { color: var(--accent); }
    .badge-group { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 4px; }
    .badge-strong { background: #d1fae5; color: #065f46; padding: 4px 14px; border-radius: 100px; font-size: 13px; font-weight: 600; }
    .badge-weak { background: #fee2e2; color: #991b1b; padding: 4px 14px; border-radius: 100px; font-size: 13px; font-weight: 600; }
    .badge-neutral { background: var(--bg-primary); color: var(--text-secondary); padding: 4px 14px; border-radius: 100px; font-size: 13px; font-weight: 500; }
    .table-wrap { background: var(--bg-card); border-radius: var(--radius); border: 1px solid var(--border); overflow: hidden; box-shadow: var(--shadow); margin-bottom: 24px; }
    .table-wrap:hover { box-shadow: var(--shadow-hover); }
    .table-wrap table { width: 100%; border-collapse: collapse; font-size: 14px; }
    .table-wrap thead th { padding: 12px 18px; text-align: left; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-light); border-bottom: 2px solid var(--border); background: var(--bg-primary); }
    .table-wrap tbody td { padding: 10px 18px; border-bottom: 1px solid var(--border); color: var(--text-primary); }
    .table-wrap tbody tr:last-child td { border-bottom: none; }
    .table-wrap tbody tr:hover td { background: rgba(67,97,238,0.02); }
    .table-wrap .empty-row td { text-align: center; color: var(--text-light); padding: 24px; }
    .absent-alert { background: #fee2e2; border: 2px solid #ef4444; border-radius: var(--radius); padding: 16px 24px; text-align: center; margin-bottom: 20px; font-size: 16px; font-weight: 600; color: #991b1b; display: flex; align-items: center; justify-content: center; gap: 12px; }
    .absent-alert i { font-size: 24px; } .absent-alert .sub-text { font-size: 14px; font-weight: 400; }
    .schedule-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 16px; }
    .schedule-day { background: var(--bg-card); border-radius: var(--radius); border: 1px solid var(--border); overflow: hidden; box-shadow: var(--shadow); transition: all var(--transition); }
    .schedule-day:hover { box-shadow: var(--shadow-hover); transform: translateY(-2px); }
    .schedule-day .day-header { padding: 10px 14px; background: var(--accent-gradient); color: #fff; font-weight: 700; font-size: 14px; display: flex; align-items: center; gap: 8px; }
    .schedule-day .day-header i { font-size: 14px; }
    .schedule-day table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .schedule-day table th { padding: 6px 8px; text-align: center; font-size: 10px; font-weight: 600; text-transform: uppercase; color: var(--text-light); background: var(--bg-primary); border-bottom: 1px solid var(--border); }
    .schedule-day table td { padding: 6px 8px; text-align: center; border-bottom: 1px solid var(--border); font-size: 13px; }
    .schedule-day table tr:last-child td { border-bottom: none; }
    .schedule-day .empty-lesson td { color: var(--text-light); text-align: center; padding: 12px; }
    .fade-up { opacity: 0; transform: translateY(24px); transition: all 0.7s cubic-bezier(0.23, 1, 0.32, 1); }
    .fade-up.visible { opacity: 1; transform: translateY(0); }
    .fade-delay-1 { transition-delay: 0.05s; } .fade-delay-2 { transition-delay: 0.10s; }
    .btn-edit { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; background: var(--accent-gradient); color: #fff; border-radius: var(--radius-sm); font-weight: 600; font-size: 14px; transition: var(--transition); box-shadow: 0 4px 14px var(--accent-glow); }
    .btn-edit:hover { transform: translateY(-2px); box-shadow: 0 8px 24px var(--accent-glow); }
    .grades-filter-bar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 16px; }
    .grades-filter-bar h2 { font-size: 22px; font-weight: 700; color: var(--text-primary); }
    .grades-filter-bar form { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
    .grades-filter-bar input[type="date"] { padding: 8px 14px; border: 1px solid var(--border); border-radius: var(--radius-sm); font-size: 14px; font-family: 'Inter', sans-serif; background: var(--bg-primary); outline: none; }
    .grades-filter-bar button, .grades-filter-bar a.btn-reset { padding: 8px 24px; border: none; border-radius: var(--radius-sm); font-size: 14px; font-weight: 600; cursor: pointer; font-family: 'Inter', sans-serif; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
    .grades-filter-bar button { background: var(--accent-gradient); color: #fff; }
    .grades-filter-bar a.btn-reset { background: var(--bg-primary); color: var(--text-secondary); border: 1px solid var(--border); }
    @media (max-width: 1024px) { .profile-grid { grid-template-columns: 1fr; } .schedule-grid { grid-template-columns: repeat(2, 1fr); } .profile-container { padding: 0 16px; } }
    @media (max-width: 768px) { .profile-header { padding: 16px 20px; flex-direction: column; text-align: center; } .profile-header .left { flex-direction: column; } .profile-header .right { justify-content: center; } .info-list { grid-template-columns: 1fr; } .schedule-grid { grid-template-columns: 1fr; } .profile-container { margin: 16px auto; } .profile-card { padding: 16px 18px; } }
    @media (max-width: 480px) { .profile-header .left .info h1 { font-size: 18px; } .profile-header .left .avatar { width: 44px; height: 44px; font-size: 18px; } .table-wrap table { font-size: 12px; } .table-wrap thead th, .table-wrap tbody td { padding: 6px 10px; } }
</style>

<div class="profile-container">
    <div class="profile-header fade-up">
        <div class="left">
            <div class="avatar"><?= htmlspecialchars(mb_substr($student['surname'] ?? '', 0, 1), ENT_QUOTES, 'UTF-8') ?></div>
            <div class="info">
                <h1><?= htmlspecialchars(($student['surname'] ?? '') . ' ' . ($student['name'] ?? '') . ' ' . ($student['patronymic'] ?? '')) ?></h1>
                <div class="sub">
                    <i class="fas fa-graduation-cap"></i> Класс: <span><?= htmlspecialchars(($student['grade'] ?? '') . ($student['class'] ?? '')) ?></span>
                    <i class="fas fa-calendar-alt" style="margin-left:12px;"></i> Дата рождения: <span><?= htmlspecialchars($student['birth'] ?? '') ?></span>
                </div>
            </div>
        </div>
        <div class="right">
            <span class="pill"><i class="fas fa-star"></i> Рейтинг: <?= htmlspecialchars($student['rating'] ?? 0) ?></span>
            <span class="pill"><i class="fas fa-check-circle"></i> Посещаемость: <?= htmlspecialchars($student['attendance'] ?? 0) ?>%</span>
            <span class="pill"><i class="fas fa-chart-line"></i> Средний балл за всё время: <?= htmlspecialchars($student['avg_grade'] ?? 0) ?></span>
        </div>
    </div>

    <div class="status-badge <?= htmlspecialchars($status_class) ?> fade-up">
        <i class="fas fa-info-circle"></i> <?= htmlspecialchars($status_text) ?>
    </div>

    <div class="profile-grid">
        <div class="profile-card fade-up fade-delay-1">
            <div class="card-title"><i class="fas fa-user"></i> Личная информация <span class="line"></span></div>
            <div class="info-list">
                <div class="item"><span class="label">ФИО</span><span class="value"><?= htmlspecialchars(($student['surname'] ?? '') . ' ' . ($student['name'] ?? '') . ' ' . ($student['patronymic'] ?? '')) ?></span></div>
                <div class="item"><span class="label">Класс</span><span class="value highlight"><?= htmlspecialchars(($student['grade'] ?? '') . ($student['class'] ?? '')) ?></span></div>
                <div class="item"><span class="label">Дата рождения</span><span class="value"><?= htmlspecialchars($student['birth'] ?? '') ?></span></div>
                <div class="item"><span class="label">Пол</span><span class="value"><?= ($student['gender'] ?? '') == "female" ? "Женский" : "Мужской" ?></span></div>
                <div class="item"><span class="label">Посещаемость</span><span class="value highlight"><?= htmlspecialchars($student['attendance'] ?? 0) ?>%</span></div>
                <div class="item"><span class="label">Средний балл за всё время</span><span class="value highlight"><?= htmlspecialchars($student['avg_grade'] ?? 0) ?></span></div>
                <div class="item"><span class="label">Рейтинг</span><span class="value highlight"><?= htmlspecialchars($student['rating'] ?? 0) ?></span></div>
            </div>
        </div>
        <div class="profile-card fade-up fade-delay-2">
            <div class="card-title"><i class="fas fa-chart-pie"></i> Анализ предметов за <?= $currentQuarter ?>-ю четверть <span class="line"></span></div>
            <p style="color:var(--text-secondary); font-size:13px; margin:-6px 0 14px;">
                <?= date('d.m.Y', strtotime($analysisPeriodStart)) ?> — <?= date('d.m.Y', strtotime($analysisPeriodEnd)) ?>.
                Средний балл за четверть: <strong><?= $periodAvgGrade ?></strong>
                <?php if ($periodGradeCount === 0): ?> — оценок пока нет.<?php endif; ?>
            </p>
            <div style="margin-bottom:12px;">
                <div style="font-weight:600; color:var(--text-secondary); font-size:13px; margin-bottom:6px;"><i class="fas fa-thumbs-up" style="color:#10b981;"></i> Сильные предметы (≥<?= $strongSubjectThreshold ?>)</div>
                <div class="badge-group">
                    <?php if (!empty($strong)): ?><?php foreach ($strong as $s): ?><span class="badge-strong"><?= htmlspecialchars($s) ?></span><?php endforeach; ?>
                    <?php else: ?><span class="badge-neutral"><?= $periodGradeCount > 0 ? 'Пока нет предметов со средним баллом от ' . $strongSubjectThreshold : 'Нет оценок для анализа' ?></span><?php endif; ?>
                </div>
            </div>
            <div style="margin-bottom:14px;">
                <div style="font-weight:600; color:var(--text-secondary); font-size:13px; margin-bottom:6px;"><i class="fas fa-thumbs-down" style="color:#ef4444;"></i> Предметы, которые нужно подтянуть (&lt;<?= $strongSubjectThreshold ?>)</div>
                <div class="badge-group">
                    <?php if (!empty($weak)): ?><?php foreach ($weak as $w): ?><span class="badge-weak"><?= htmlspecialchars($w) ?></span><?php endforeach; ?>
                    <?php else: ?><span class="badge-neutral"><?= $periodGradeCount > 0 ? 'Нет предметов ниже ' . $strongSubjectThreshold : 'Нет оценок для анализа' ?></span><?php endif; ?>
                </div>
                <?php if (!empty($weak)): ?>
                    <p style="color:var(--text-secondary); font-size:13px; margin-top:8px;">Повтори последние темы и разбери ошибки в работах по этим предметам.</p>
                <?php endif; ?>
            </div>
            <div>
                <div style="font-weight:600; color:var(--text-secondary); font-size:13px; margin-bottom:6px;"><i class="fas fa-chart-line" style="color:var(--accent);"></i> Динамика оценок</div>
                <p style="color:var(--text-secondary); font-size:13px; margin:0 0 8px;">Ранние оценки → последние оценки за четверть.</p>
                <div class="badge-group">
                    <?php if (!empty($subjectProgress)): ?>
                        <?php foreach ($subjectProgress as $progress): ?>
                            <?php
                            $changeLabel = $progress['change'] > 0
                                ? '+' . $progress['change']
                                : ($progress['change'] < 0 ? (string)$progress['change'] : 'без изменений');
                            $changeClass = $progress['change'] > 0 ? 'badge-strong' : ($progress['change'] < 0 ? 'badge-weak' : 'badge-neutral');
                            ?>
                            <span class="<?= $changeClass ?>"><?= htmlspecialchars($progress['subject']) ?>: <?= $progress['earlier'] ?> → <?= $progress['recent'] ?> (<?= htmlspecialchars($changeLabel) ?>)</span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="badge-neutral">Для сравнения нужны минимум две оценки по предмету</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <a href="edit_profile.php" class="btn-edit" style="text-decoration: none; justify-self: start;"><i class="fas fa-pen"></i> Изменить данные</a>
    </div>

    <!-- ОЦЕНКИ -->
    <div class="grades-filter-bar">
        <h2>
            <i class="fas fa-graduation-cap" style="color:var(--accent);"></i> Оценки
            <?php if ($hasDateFilter): ?>
                <span style="font-size:14px; font-weight:500; color:var(--text-secondary);">
                    за <?= date('d.m.Y', strtotime($selected_date)) ?>
                </span>
            <?php else: ?>
                <span style="font-size:14px; font-weight:500; color:var(--text-secondary);">
                    за период <?= date('d.m.Y', strtotime($periodStart)) ?> — <?= date('d.m.Y', strtotime($periodEnd)) ?>
                </span>
            <?php endif; ?>
        </h2>
        <form method="get" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <input type="date" name="date" value="<?= htmlspecialchars($selected_date) ?>">
            <button type="submit"><i class="fas fa-search"></i> Показать</button>
            <?php if ($hasDateFilter): ?>
                <a href="?" class="btn-reset"><i class="fas fa-times"></i> Сбросить</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($is_absent_today && $hasDateFilter): ?>
        <div class="absent-alert"><i class="fas fa-user-times"></i> В этот день вы не пришли в школу! <span class="sub-text">(<?= date('d.m.Y', strtotime($selected_date)) ?>)</span></div>
    <?php endif; ?>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width:60px;">№</th>
                    <th style="width:130px;">Дата</th>
                    <th>Предмет</th>
                    <th style="width:100px;">Оценка</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($grades_result && $grades_result->num_rows > 0): ?>
                    <?php $i = 1; ?>
                    <?php while ($g = $grades_result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= date('d.m.Y', strtotime($g['grade_day'])) ?></td>
                            <td><?= htmlspecialchars($g['subject'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><strong style="color:var(--accent);"><?= htmlspecialchars($g['grade_value'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr class="empty-row"><td colspan="4"><i class="fas fa-info-circle"></i> Оценок за выбранный период нет</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <h2 style="font-size:22px; font-weight:700; color:var(--text-primary); margin:24px 0 8px;"><i class="fas fa-calendar-alt" style="color:var(--accent);"></i> Расписание класса <span style="color:var(--accent);"><?= htmlspecialchars(($student['grade'] ?? '') . ($student['class'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></h2>
    <p style="color:var(--text-secondary); margin-bottom:16px;">Оценки в расписании показаны только за <?= date('d.m.Y', strtotime($selected_date)) ?>.</p>

    <div class="schedule-grid">
        <?php
        $selectedWeekday = (new DateTimeImmutable($selected_date))->format('l');
        $days = [
            'Monday' => ['ru' => 'Понедельник', 'icon' => 'fa-sun'], 'Tuesday' => ['ru' => 'Вторник', 'icon' => 'fa-cloud'],
            'Wednesday' => ['ru' => 'Среда', 'icon' => 'fa-cloud-sun'], 'Thursday' => ['ru' => 'Четверг', 'icon' => 'fa-smog'],
            'Friday' => ['ru' => 'Пятница', 'icon' => 'fa-star'], 'Saturday' => ['ru' => 'Суббота', 'icon' => 'fa-moon']
        ];
        foreach ($days as $day => $day_info):
        ?>
        <div class="schedule-day fade-up">
            <div class="day-header"><i class="fas <?= $day_info['icon'] ?>"></i> <?= $day_info['ru'] ?></div>
            <table>
                <thead><tr><th style="width:40px;">№</th><th>Предмет</th><th>Оценка</th><th style="width:50px;">Каб.</th></tr></thead>
                <tbody>
                    <?php if (isset($schedule[$day])): ?>
                        <?php $displayedGradeSubjects = []; ?>
                        <?php foreach ($schedule[$day] as $lesson):
                            $lessonSubjectId = (int)$lesson['subject_id'];
                            $lessonGrades = [];
                            if ($day === $selectedWeekday && !isset($displayedGradeSubjects[$lessonSubjectId])) {
                                $lessonGrades = $dailyGradesBySubject[$lessonSubjectId] ?? [];
                                $displayedGradeSubjects[$lessonSubjectId] = true;
                            }
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($lesson['lesson_number'] ?? '') ?></td>
                                <td>
                                    <?= htmlspecialchars($lesson['subject'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                    <?php if (!empty($lesson['teacher_name'])): ?>
                                        <br><small style="color: #8a9bb0; font-size: 11px;"><?= htmlspecialchars($lesson['teacher_name'], ENT_QUOTES, 'UTF-8') ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= $lessonGrades ? htmlspecialchars(implode(', ', $lessonGrades), ENT_QUOTES, 'UTF-8') : '—' ?></td>
                                <td><?= htmlspecialchars($lesson['room'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr class="empty-lesson"><td colspan="4">Уроков нет</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.fade-up').forEach(el => {
            new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) entry.target.classList.add('visible');
                });
            }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' }).observe(el);
        });
    });
</script>
<?php require_once("js.php"); ?>