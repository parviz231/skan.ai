<?php
require_once('pages/journals/config.php');
set_time_limit(300);

$minGrade = 4;
$maxGrade = 10;
$minMarks = 4;
$maxMarks = 7;

$startDate = '2026-09-01';
$endDate   = '2026-10-19';

$dayMap = [
    'Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3,
    'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6
];

// 1. Получаем всех учеников
$usersRes = $conn->query("SELECT id, grade, class FROM users WHERE grade BETWEEN 5 AND 11");
$users = [];
while ($row = $usersRes->fetch_assoc()) {
    $users[] = $row;
}

// 2. Получаем всё расписание ОДНИМ запросом
$scheduleRes = $conn->query("
    SELECT s.grade, s.class, s.subject_id, sub.name AS subject, s.day_of_week
    FROM schedule s
    JOIN subjects sub ON sub.id = s.subject_id
    WHERE s.subject_id IS NOT NULL AND s.day_of_week IS NOT NULL
");
$scheduleMap = [];
while ($s = $scheduleRes->fetch_assoc()) {
    $key = $s['grade'] . '|' . $s['class'];
    if (isset($dayMap[$s['day_of_week']])) {
        $scheduleMap[$key][(int)$s['subject_id']][] = $dayMap[$s['day_of_week']];
    }
}

// 3. Получаем существующие оценки ОДНИМ запросом (чтобы не дублировать)
$existing = [];
$existRes = $conn->query("SELECT user_id, subject_id, grade_date FROM grades WHERE grade_date BETWEEN '$startDate' AND '$endDate'");
while ($e = $existRes->fetch_assoc()) {
    $existing[$e['user_id']][$e['subject_id']][$e['grade_date']] = true;
}

// 4. Генерируем данные
$insertValues = [];
foreach ($users as $user) {
    $userId = (int)$user['id'];
    $key = $user['grade'] . '|' . $user['class'];
    
    if (!isset($scheduleMap[$key])) continue;

    foreach ($scheduleMap[$key] as $subjectId => $daysOfWeek) {
        // Собираем даты в 1 четверти, совпадающие с днями недели
        $validDates = [];
        $period = new DatePeriod(
            new DateTime($startDate),
            new DateInterval('P1D'),
            (new DateTime($endDate))->modify('+1 day')
        );
        foreach ($period as $d) {
            if (in_array((int)$d->format('N'), $daysOfWeek)) {
                $validDates[] = $d->format('Y-m-d');
            }
        }
        if (empty($validDates)) continue;

        $markCount = min(rand($minMarks, $maxMarks), count($validDates));
        shuffle($validDates);
        $selected = array_slice($validDates, 0, $markCount);

        foreach ($selected as $date) {
            if (isset($existing[$userId][$subjectId][$date])) continue;
            $val = rand($minGrade, $maxGrade);
            $insertValues[] = "($userId, $subjectId, $val, '$date')";
        }
    }
}

// 5. Один большой INSERT
$total = 0;
if (!empty($insertValues)) {
    // Разбиваем на пачки по 500, чтобы не превысить лимит MySQL
    foreach (array_chunk($insertValues, 500) as $chunk) {
        $sql = "INSERT INTO grades (user_id, subject_id, grade_value, grade_date) VALUES " . implode(',', $chunk);
        if ($conn->query($sql)) {
            $total += count($chunk);
        }
    }
}

echo "Готово! Вставлено оценок: $total";
echo '<br><br><a href="index.php?page=8">← Вернуться в журнал</a>';