<?php
ob_start();
session_start();

if (!isset($_SESSION['id1']) || empty($_SESSION['id1'])) {
    header("Location: login.php");
    exit;
}

require_once("config2.php");

if ($conn->connect_error) {
    die("Ошибка подключения к базе данных: " . $conn->connect_error);
}

$student_id = (int)$_SESSION['id1'];

/* =========================
   ДАННЫЕ УЧЕНИКА
========================= */

$sql = "
SELECT
    u.id,
    u.surname,
    u.name,
    u.patronymic,
    u.gender,
    u.birth,
    u.grade,
    u.class,

    COALESCE(
        (
            SELECT ROUND(
                SUM(v.visit = 'visited') /
                NULLIF(COUNT(v.visit), 0) * 100,
                2
            )
            FROM visits v
            WHERE v.user_id = u.id
        ),
        0
    ) AS attendance,

    COALESCE(
        (
            SELECT ROUND(
                AVG(g.grade_value),
                2
            )
            FROM grades g
            WHERE g.user_id = u.id
        ),
        0
    ) AS avg_grade,

    ROUND(
        (
            COALESCE(
                (
                    SELECT
                        SUM(v.visit = 'visited') /
                        NULLIF(COUNT(v.visit), 0) * 100
                    FROM visits v
                    WHERE v.user_id = u.id
                ),
                0
            )
            +
            COALESCE(
                (
                    SELECT AVG(g.grade_value) * 10
                    FROM grades g
                    WHERE g.user_id = u.id
                ),
                0
            )
        ) / 2,
        2
    ) AS rating

FROM users u
WHERE u.id = ?
LIMIT 1
";

$stmt_student = $conn->prepare($sql);

if (!$stmt_student) {
    die("Ошибка SQL: " . $conn->error);
}

$stmt_student->bind_param("i", $student_id);
$stmt_student->execute();

$result_student = $stmt_student->get_result();
$student = $result_student->fetch_assoc();

$stmt_student->close();

if (!$student) {
    die("Профиль пользователя не найден. ID: " . $student_id);
}


/* =========================
   ДАТА
========================= */

$selected_date = isset($_GET['date'])
    ? $_GET['date']
    : date("Y-m-d");


/* =========================
   ОЦЕНКИ
========================= */

$stmt = $conn->prepare("
    SELECT
        g.subject,
        g.grade_value,
        g.grade_date,
        t.name AS teacher
    FROM grades g
    LEFT JOIN teachers t
        ON g.teacher_id = t.id
    WHERE g.user_id = ?
      AND g.grade_date = ?
    ORDER BY g.subject
");

if (!$stmt) {
    die("Ошибка SQL: " . $conn->error);
}

$stmt->bind_param(
    "is",
    $student_id,
    $selected_date
);

$stmt->execute();

$grades_result = $stmt->get_result();


/* =========================
   СИЛЬНЫЕ И СЛАБЫЕ ПРЕДМЕТЫ
========================= */

$stmt_subjects = $conn->prepare("
    SELECT
        subject,
        ROUND(AVG(grade_value), 2) AS avg_subj
    FROM grades
    WHERE user_id = ?
    GROUP BY subject
    ORDER BY subject
");

if (!$stmt_subjects) {
    die("Ошибка SQL: " . $conn->error);
}

$stmt_subjects->bind_param(
    "i",
    $student_id
);

$stmt_subjects->execute();

$subj_res = $stmt_subjects->get_result();

$strong = [];
$weak = [];

while ($row = $subj_res->fetch_assoc()) {

    if ($row['avg_subj'] >= 8) {

        $strong[] =
            $row['subject'] .
            " (" .
            $row['avg_subj'] .
            ")";

    } elseif ($row['avg_subj'] <= 5) {

        $weak[] =
            $row['subject'] .
            " (" .
            $row['avg_subj'] .
            ")";
    }
}

$stmt_subjects->close();


/* =========================
   РАСПИСАНИЕ
========================= */

$stmt2 = $conn->prepare("
    SELECT
        day_of_week,
        lesson_number,
        subject,
        room
    FROM schedule
    WHERE grade = ?
      AND class = ?
    ORDER BY
        FIELD(
            day_of_week,
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday',
            'Saturday'
        ),
        lesson_number
");

if (!$stmt2) {
    die("Ошибка SQL: " . $conn->error);
}

$stmt2->bind_param(
    "is",
    $student['grade'],
    $student['class']
);

$stmt2->execute();

$schedule_result = $stmt2->get_result();

$schedule = [];

while ($row = $schedule_result->fetch_assoc()) {

    $schedule[$row['day_of_week']][] = $row;
}

$stmt2->close();

$stmt->close();

$conn->close();


/* =========================
   СТАТУС
========================= */

$status_class = "status-good";

$status_text = "Хонанда хуб меомӯзад ва ҳузур дорад";

$rating = (float)$student['rating'];
$attendance = (float)$student['attendance'];

if (
    $rating < 20 ||
    $attendance < 40
) {

    $status_class = "status-bad";

    $status_text = "Ученик подлежит исключению";

} elseif (
    $rating < 40 ||
    $attendance < 60
) {

    $status_class = "status-warning";

    $status_text = "Хонанда ба хориҷ наздик мешавад";
}


/* =========================
   HEADER
========================= */

require_once("header.php");

?>

<!DOCTYPE html>

<html lang="ru">

<head>

<meta charset="UTF-8">

<title>Профили хонанда</title>

<style>

.container1 {
    max-width:1100px;
    margin:auto;
    background:#fff;
    padding:20px;
    border-radius:12px;
    box-shadow:0 4px 15px rgba(0,0,0,0.1);
}

h1,
h2 {
    text-align:center;
    color:#022b5b;
}

.row {
    display:flex;
    flex-wrap:wrap;
    gap:20px;
    margin-bottom:20px;
}

.col {
    flex:1;
    min-width:300px;
    background:#f9fcff;
    padding:15px;
    border-radius:10px;
    box-shadow:0 2px 8px rgba(0,0,0,0.05);
}

.card-title {
    font-size:18px;
    font-weight:bold;
    margin-bottom:10px;
    color:#1c3f95;
    border-bottom:2px solid #2C4361;
    padding-bottom:5px;
}

table {
    border-collapse:collapse;
    width:100%;
    margin:10px 0;
}

th,
td {
    border:1px solid #a2c4e1;
    padding:8px;
    text-align:center;
}

th {
    background:#2C4361;
    color:white;
}

tr:nth-child(even) {
    background:#f0f6ff;
}

form {
    text-align:center;
    margin:15px 0;
}

input[type="date"] {
    padding:6px;
    font-size:15px;
    border:1px solid #ccc;
    border-radius:6px;
}

button {
    padding:7px 15px;
    font-size:15px;
    background:#2C4361;
    color:white;
    border:none;
    border-radius:6px;
    cursor:pointer;
}

button:hover {
    background:#4a90e2;
}

.badge {
    display:inline-block;
    padding:3px 7px;
    border-radius:5px;
    font-size:13px;
    margin:2px;
}

.badge-strong {
    background:#4CAF50;
    color:white;
}

.badge-weak {
    background:#E74C3C;
    color:white;
}

.status-good,
.status-warning,
.status-bad {
    padding:10px;
    border-radius:6px;
    text-align:center;
    font-weight:bold;
    margin-top:10px;
}

.status-good {
    background:#2ecc71;
    color:white;
}

.status-warning {
    background:#e67e22;
    color:white;
}

.status-bad {
    background:#e74c3c;
    color:white;
}

.animate {
    opacity:0;
    transform:translateY(50px);
    transition:all 1s ease;
}

.animate.show {
    opacity:1;
    transform:translateY(0);
}

</style>

</head>

<body>

<div
    class="container1 animate"
    style="margin-top:20px;"
>

<h1>Профили хонанда</h1>


<div class="row">


<!-- =========================
     ЛИЧНАЯ ИНФОРМАЦИЯ
========================= -->

<div class="col">

<div class="card-title">
    Маълумоти шахсӣ
</div>

<p>

<b>ННН:</b>

<?= htmlspecialchars(
    ($student['surname'] ?? '') . " " .
    ($student['name'] ?? '') . " " .
    ($student['patronymic'] ?? '')
) ?>

<br>

<b>Синф:</b>

<?= htmlspecialchars(
    ($student['grade'] ?? '') .
    ($student['class'] ?? '')
) ?>

<br>

<b>Соли таваллуд:</b>

<?= htmlspecialchars(
    $student['birth'] ?? ''
) ?>

<br>

<b>Ҷинс:</b>

<?= ($student['gender'] ?? '') == "female"
    ? "Женский"
    : "Мужской"
?>

<br>

<b>Ҳузур:</b>

<?= htmlspecialchars(
    $student['attendance'] ?? 0
) ?>

%

<br>

<b>Баҳои миёна:</b>

<?= htmlspecialchars(
    $student['avg_grade'] ?? 0
) ?>

<br>

<b>Рейтинг:</b>

<?= htmlspecialchars(
    $student['rating'] ?? 0
) ?>

</p>


<div class="<?= htmlspecialchars($status_class) ?>">

<?= htmlspecialchars($status_text) ?>

</div>

</div>


<!-- =========================
     АНАЛИЗ ПРЕДМЕТОВ
========================= -->

<div class="col">

<div class="card-title">
    Анализи фанҳо
</div>


<p>

<b>Фанҳои қавӣ:</b>

<br>

<?php if (!empty($strong)): ?>

    <?php foreach ($strong as $s): ?>

        <span class="badge badge-strong">

            <?= htmlspecialchars($s) ?>

        </span>

    <?php endforeach; ?>

<?php else: ?>

    Нет сильных предметов

<?php endif; ?>

</p>


<p>

<b>Фанҳои заиф:</b>

<br>

<?php if (!empty($weak)): ?>

    <?php foreach ($weak as $w): ?>

        <span class="badge badge-weak">

            <?= htmlspecialchars($w) ?>

        </span>

    <?php endforeach; ?>

<?php else: ?>

    Фанҳои заиф нест

<?php endif; ?>

</p>

</div>

</div>


<!-- =========================
     ОЦЕНКИ
========================= -->

<h2>Баҳоҳо</h2>


<form method="get">

<input
    type="date"
    name="date"
    value="<?= htmlspecialchars($selected_date) ?>"
>

<button type="submit">
    Нишон додан
</button>

</form>


<table>

<tr>

<th>№</th>
<th>Фан</th>
<th>Баҳо</th>

</tr>


<?php if ($grades_result->num_rows > 0): ?>

    <?php $i = 1; ?>

    <?php while ($g = $grades_result->fetch_assoc()): ?>

        <tr>

            <td>
                <?= $i++ ?>
            </td>

            <td>
                <?= htmlspecialchars(
                    $g['subject'] ?? ''
                ) ?>
            </td>

            <td>
                <?= htmlspecialchars(
                    $g['grade_value'] ?? ''
                ) ?>
            </td>

        </tr>

    <?php endwhile; ?>

<?php else: ?>

    <tr>

        <td colspan="3">
            Дарсҳо нест
        </td>

    </tr>

<?php endif; ?>

</table>


<!-- =========================
     РАСПИСАНИЕ
========================= -->

<h2>

Чадвали дарсии синф

<?= htmlspecialchars(
    ($student['grade'] ?? '') .
    ($student['class'] ?? '')
) ?>

</h2>


<div class="row">


<?php

$days = [

    'Monday' =>
        'Понедельник',

    'Tuesday' =>
        'Вторник',

    'Wednesday' =>
        'Среда',

    'Thursday' =>
        'Четверг',

    'Friday' =>
        'Пятница',

    'Saturday' =>
        'Суббота'

];

foreach ($days as $day => $day_name):

?>

<div class="col">


<div class="card-title">

<?= htmlspecialchars($day_name) ?>

</div>


<table>

<tr>

<th>№</th>
<th>Фан</th>
<th>Каб.</th>

</tr>


<?php if (isset($schedule[$day])): ?>

    <?php foreach ($schedule[$day] as $lesson): ?>

        <tr>

            <td>

                <?= htmlspecialchars(
                    $lesson['lesson_number'] ?? ''
                ) ?>

            </td>

            <td>

                <?= htmlspecialchars(
                    $lesson['subject'] ?? ''
                ) ?>

            </td>

            <td>

                <?= htmlspecialchars(
                    $lesson['room'] ?? ''
                ) ?>

            </td>

        </tr>

    <?php endforeach; ?>

<?php else: ?>

    <tr>

        <td colspan="3">
            Дарс надорад
        </td>

    </tr>

<?php endif; ?>

</table>

</div>

<?php endforeach; ?>

</div>


</div>


<script>

document.addEventListener(
    "DOMContentLoaded",
    () => {

        const elements =
            document.querySelectorAll('.animate');

        const observer =
            new IntersectionObserver(
                (entries) => {

                    entries.forEach(entry => {

                        if (entry.isIntersecting) {

                            entry.target.classList.add(
                                'show'
                            );

                        }

                    });

                },
                {
                    threshold:0.2
                }
            );

        elements.forEach(
            el => observer.observe(el)
        );

    }
);

</script>


<?php require_once("js.php"); ?>

</body>

</html>