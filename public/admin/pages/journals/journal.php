<?php
require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['journal_csrf_token']) || !is_string($_SESSION['journal_csrf_token'])) {
    $_SESSION['journal_csrf_token'] = bin2hex(random_bytes(32));
}

function gp($name, $def) {
    if (isset($_POST[$name]) && is_scalar($_POST[$name])) return (string)$_POST[$name];
    if (isset($_GET[$name]) && is_scalar($_GET[$name])) return (string)$_GET[$name];
    return $def;
}

$grade   = (int) gp('grade', 8);
$class   = gp('class', 'Б');
$quarter = (int) gp('quarter', 1);
$year    = (int) gp('year', 2026);

$grade = ($grade >= 5 && $grade <= 11) ? $grade : 8;
$class = in_array($class, ['А', 'Б', 'В'], true) ? $class : 'Б';
$quarter = ($quarter >= 1 && $quarter <= 4) ? $quarter : 1;
$year = ($year >= 2000 && $year <= 2100) ? $year : (int)date('Y');

$currentMonth = (int)date('n');
$studyYearStart = ($currentMonth >= 9) ? $year : $year - 1;
$quarters = [
    1 => ["start" => "$studyYearStart-09-01", "end" => "$studyYearStart-10-31"],
    2 => ["start" => "$studyYearStart-11-11", "end" => "$studyYearStart-12-31"],
    3 => ["start" => ($studyYearStart + 1) . "-01-11", "end" => ($studyYearStart + 1) . "-03-21"],
    4 => ["start" => ($studyYearStart + 1) . "-04-01", "end" => ($studyYearStart + 1) . "-05-31"]
];
$startDate = $quarters[$quarter]['start'];
$endDate   = $quarters[$quarter]['end'];

$subjectOptions = [];
$subjectResult = $conn->query("SELECT id, name FROM subjects WHERE name IS NOT NULL AND name <> '' ORDER BY name");
if ($subjectResult) {
    while ($row = $subjectResult->fetch_assoc()) {
        $subjectOptions[(int)$row['id']] = $row['name'];
    }
}

$requestedSubject = gp('subject', '');
$subjectId = 0;
if (ctype_digit((string)$requestedSubject) && isset($subjectOptions[(int)$requestedSubject])) {
    $subjectId = (int)$requestedSubject;
} elseif ($requestedSubject !== '') {
    $matchingSubjectId = array_search($requestedSubject, $subjectOptions, true);
    if ($matchingSubjectId !== false) $subjectId = (int)$matchingSubjectId;
}
if ($subjectId === 0 && $subjectOptions) {
    $subjectId = (int)array_key_first($subjectOptions);
}
$subjectName = $subjectOptions[$subjectId] ?? '';

// Дни недели для выбранного предмета
$scheduleDays = [];
if ($subjectId > 0) {
    $scheduleStmt = $conn->prepare("
        SELECT DISTINCT s.day_of_week
        FROM schedule s
        WHERE s.grade = ? AND s.class = ? AND s.subject_id = ?
          AND s.day_of_week IS NOT NULL
    ");
    if ($scheduleStmt) {
        $scheduleStmt->bind_param("isi", $grade, $class, $subjectId);
        $scheduleStmt->execute();
        $scheduleRes = $scheduleStmt->get_result();
        while ($row = $scheduleRes->fetch_assoc()) {
            $day = trim((string)$row['day_of_week']);
            if ($day !== '') $scheduleDays[] = $day;
        }
        $scheduleStmt->close();
    }
}

$dayOfWeekMap = [
    'Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3, 'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6,
    'Понедельник' => 1, 'Вторник' => 2, 'Среда' => 3, 'Четверг' => 4, 'Пятница' => 5, 'Суббота' => 6,
    'Пн' => 1, 'Вт' => 2, 'Ср' => 3, 'Чт' => 4, 'Пт' => 5, 'Сб' => 6
];
$scheduledWeekdays = [];
foreach ($scheduleDays as $scheduleDay) {
    if (isset($dayOfWeekMap[$scheduleDay])) $scheduledWeekdays[$dayOfWeekMap[$scheduleDay]] = true;
}

// Ученики
$students = [];
$studentIds = [];
$stuStmt = $conn->prepare("SELECT id, surname, name FROM users WHERE grade=? AND class=? ORDER BY surname, name");
if ($stuStmt) {
    $stuStmt->bind_param("is", $grade, $class);
    $stuStmt->execute();
    $sres = $stuStmt->get_result();
    while ($r = $sres->fetch_assoc()) {
        $students[] = $r;
        $studentIds[] = (int)$r['id'];
    }
    $stuStmt->close();
}

// Даты, когда предмет стоит в расписании
$daysByMonth = [];
$allDates = [];
if (!empty($scheduledWeekdays)) {
    $startDT = new DateTime($startDate);
    $endDT = new DateTime($endDate);
    $endDT->modify('+1 day');
    $period = new DatePeriod($startDT, new DateInterval('P1D'), $endDT);
    foreach ($period as $d) {
        $dayOfWeek = (int)$d->format('N');
        if (isset($scheduledWeekdays[$dayOfWeek])) {
            $allDates[] = $d;
            $daysByMonth[(int)$d->format('n')][] = $d;
        }
    }
}
$allowedDates = [];
foreach ($allDates as $dateItem) $allowedDates[$dateItem->format('Y-m-d')] = true;

// ===================== СОХРАНЕНИЕ =====================
$saveError = '';
$saveStats = ['inserted' => 0, 'updated' => 0, 'deleted' => 0];

if (isset($_POST['grades'])) {
    $postedGrades = $_POST['grades'];
    $token = $_POST['csrf_token'] ?? '';
    $allowedStudents = array_fill_keys($studentIds, true);

    if (!is_array($postedGrades)) {
        $saveError = 'Некорректные данные оценок.';
    } elseif ($subjectId < 1) {
        $saveError = 'Выберите предмет.';
    } elseif (!is_string($token) || !hash_equals($_SESSION['journal_csrf_token'], $token)) {
        $saveError = 'Форма устарела. Обновите страницу.';
    } else {
        foreach ($postedGrades as $uidStr => $datesArr) {
            $userId = (int)$uidStr;
            if (!ctype_digit((string)$uidStr) || !isset($allowedStudents[$userId]) || !is_array($datesArr)) {
                $saveError = 'Некорректные данные учеников.';
                break;
            }
            foreach ($datesArr as $date => $markRaw) {
                if (!is_string($date) || !isset($allowedDates[$date]) || !is_string($markRaw)) {
                    $saveError = 'Некорректная дата или оценка.';
                    break 2;
                }
                $mark = trim($markRaw);
                $markLower = mb_strtolower($mark, 'UTF-8');
                if ($mark !== '' && !in_array($markLower, ['1','2','3','4','5','6','7','8','9','10','н/б'], true)) {
                    $saveError = 'Допустимы оценки 1–10 или «н/б».';
                    break 2;
                }
            }
        }
    }

    if ($saveError === '') {
        $transactionStarted = false;
        try {
            $conn->begin_transaction();
            $transactionStarted = true;

            $checkStmt  = $conn->prepare("SELECT id FROM grades WHERE user_id = ? AND subject_id = ? AND grade_date = ? LIMIT 1");
            $deleteStmt = $conn->prepare("DELETE FROM grades WHERE id = ?");
            $updateStmt = $conn->prepare("UPDATE grades SET grade_value = ? WHERE id = ?");
            $insertStmt = $conn->prepare("INSERT INTO grades (user_id, subject_id, grade_value, grade_date) VALUES (?, ?, ?, ?)");

            foreach ($postedGrades as $uidStr => $datesArr) {
                $userId = (int)$uidStr;
                foreach ($datesArr as $date => $markRaw) {
                    $mark = trim($markRaw);
                    $markLower = mb_strtolower($mark, 'UTF-8');

                    $checkStmt->bind_param("iis", $userId, $subjectId, $date);
                    $checkStmt->execute();
                    $existingResult = $checkStmt->get_result();
                    $existingRow = $existingResult->fetch_assoc();
                    $existingResult->free();

                    if ($existingRow) {
                        $gradeId = (int)$existingRow['id'];
                        if ($mark === '') {
                            $deleteStmt->bind_param("i", $gradeId);
                            $deleteStmt->execute();
                            $saveStats['deleted']++;
                        } else {
                            $markToSave = ($markLower === 'н/б') ? 'н/б' : $mark;
                            $updateStmt->bind_param("si", $markToSave, $gradeId);
                            $updateStmt->execute();
                            $saveStats['updated']++;
                        }
                    } elseif ($mark !== '') {
                        $markToSave = ($markLower === 'н/б') ? 'н/б' : $mark;
                        $insertStmt->bind_param("iiss", $userId, $subjectId, $markToSave, $date);
                        $insertStmt->execute();
                        $saveStats['inserted']++;
                    }
                }
            }

            $conn->commit();
            $transactionStarted = false;
            $checkStmt->close();
            $deleteStmt->close();
            $updateStmt->close();
            $insertStmt->close();

            $qs = http_build_query([
                'page' => 8, 'grade' => $grade, 'class' => $class,
                'subject' => $subjectId, 'quarter' => $quarter, 'year' => $year,
                'saved' => 1,
                'ins' => $saveStats['inserted'],
                'upd' => $saveStats['updated'],
                'del' => $saveStats['deleted'],
            ]);
            $redirectUrl = json_encode('index.php?' . $qs, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
            echo '<script>window.location.replace(' . $redirectUrl . ');</script>';
            return;
        } catch (mysqli_sql_exception $e) {
            if ($transactionStarted) {
                try {
                    $conn->rollback();
                } catch (mysqli_sql_exception $rollbackError) {
                    error_log('Journal rollback failed: ' . $rollbackError->getMessage());
                }
            }
            error_log('Journal save failed: ' . $e->getMessage());
            $saveError = 'Не удалось сохранить оценки. Проверьте подключение к базе данных и попробуйте ещё раз.';
        }
    }
}

$russianMonths = [1=>'Январь',2=>'Февраль',3=>'Март',4=>'Апрель',5=>'Май',6=>'Июнь',7=>'Июль',8=>'Август',9=>'Сентябрь',10=>'Октябрь',11=>'Ноябрь',12=>'Декабрь'];
$russianDays = ['Monday'=>'Пн','Tuesday'=>'Вт','Wednesday'=>'Ср','Thursday'=>'Чт','Friday'=>'Пт','Saturday'=>'Сб'];

// Чтение оценок
$existingGrades = [];
if ($studentIds && !empty($allDates) && $subjectId > 0) {
    $in = implode(',', $studentIds);
    $gsql = "SELECT user_id, grade_date AS g_date, grade_value FROM grades WHERE subject_id = ? AND grade_date BETWEEN ? AND ? AND user_id IN ($in)";
    $gstmt = $conn->prepare($gsql);
    if ($gstmt) {
        $gstmt->bind_param("iss", $subjectId, $startDate, $endDate);
        $gstmt->execute();
        $gres = $gstmt->get_result();
        while ($gr = $gres->fetch_assoc()) {
            $existingGrades[(int)$gr['user_id']][$gr['g_date']] = $gr['grade_value'];
        }
        $gstmt->close();
    }
}
?>
<style>
    .journal-container { --primary: #4f46e5; --success: #10b981; --danger: #ef4444; --text-primary: #1f2937; --text-secondary: #6b7280; --text-light: #9ca3af; --bg-card: #ffffff; --border-color: #e5e7eb; --shadow-xl: 0 20px 25px -5px rgba(0,0,0,0.1); --radius-md: 8px; --radius-lg: 12px; --radius-xl: 16px; }
    .journal-container { max-width: 100%; margin: 0 auto; padding: 24px; font-family: 'Inter', sans-serif; }
    .journal-alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-weight: 600; }
    .journal-alert.success { color: #065f46; background: #d1fae5; }
    .journal-alert.error { color: #991b1b; background: #fee2e2; }
    .card { background: var(--bg-card); border-radius: var(--radius-xl); box-shadow: var(--shadow-xl); padding: 24px; border: 1px solid rgba(229, 231, 235, 0.5); }
    .card-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; padding-bottom: 20px; border-bottom: 2px solid #f3f4f6; margin-bottom: 20px; }
    .card-header h2 { font-size: 24px; font-weight: 800; display: flex; align-items: center; gap: 12px; color: var(--text-primary); }
    .icon-wrapper { width: 40px; height: 40px; background: linear-gradient(135deg, var(--primary), #7c3aed); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 18px; }
    .filter-wrap { background: #f9fafb; padding: 16px 20px; border-radius: var(--radius-lg); margin-bottom: 20px; border: 1px solid var(--border-color); }
    .filter-wrap form { display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; }
    .filter-group { display: flex; flex-direction: column; gap: 6px; flex: 1 1 120px; }
    .filter-group label { font-size: 11px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; }
    .filter-group select, .filter-group input { padding: 8px 12px; border: 2px solid var(--border-color); border-radius: var(--radius-md); font-size: 13px; height: 38px; font-family: inherit; width: 100%; background: white; outline: none; }
    .filter-group select:focus, .filter-group input:focus { border-color: var(--primary); }
    .btn { font-size: 13px; padding: 8px 16px; border-radius: var(--radius-md); border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-weight: 600; height: 38px; font-family: inherit; transition: 0.2s; }
    .btn-primary { background: var(--primary); color: #fff; }
    .btn-success { background: var(--success); color: #fff; }
    .btn-success:hover { background: #059669; }
    .table-wrap { overflow-x: auto; border: 1px solid var(--border-color); border-radius: var(--radius-lg); background: white; }
    table { border-collapse: separate; border-spacing: 0; font-size: 13px; width: 100%; min-width: 800px; }
    th, td { border-bottom: 1px solid var(--border-color); border-right: 1px solid var(--border-color); padding: 6px 4px; text-align: center; vertical-align: middle; }
    th { background: #f8fafc; font-weight: 600; color: var(--text-secondary); font-size: 12px; }
    .col-num { width: 40px; min-width: 40px; max-width: 40px; position: sticky; left: 0; background: #fff; z-index: 5; }
    .col-name { width: 180px; min-width: 180px; max-width: 180px; position: sticky; left: 40px; background: #fff; z-index: 5; text-align: left; padding-left: 12px; font-weight: 600; box-shadow: 2px 0 5px -2px rgba(0,0,0,0.1); border-right: 2px solid var(--border-color); white-space: normal; line-height: 1.2; }
    .col-avg { width: 60px; min-width: 60px; max-width: 60px; position: sticky; left: 220px; background: #eef2ff; z-index: 5; font-weight: 700; color: var(--primary); }
    .col-final { width: 60px; min-width: 60px; max-width: 60px; position: sticky; left: 280px; background: #f0fdf4; z-index: 5; font-weight: 700; color: #059669; box-shadow: 2px 0 5px -2px rgba(0,0,0,0.1); border-right: 2px solid var(--border-color); }
    th.col-num, th.col-name, th.col-avg, th.col-final { background: #f8fafc; }
    .month-th { background: #eef2ff !important; font-weight: 700; color: var(--primary); }
    .day-th { font-size: 10px; min-width: 30px; }
    .day-th .day-name { display: block; font-size: 9px; color: var(--text-light); }
    input.grade-input { width: 36px; height: 28px; text-align: center; font-size: 13px; font-weight: 700; border: 1px solid var(--border-color); border-radius: 4px; font-family: inherit; background: #f8fafc; color: var(--text-primary); }
    input.grade-input:focus { border-color: var(--primary); background: #fff; outline: none; }
    input.grade-input.bad { color: #dc2626; background: #fef2f2; border-color: #fecaca; }
    input.grade-input.good { color: #059669; background: #f0fdf4; border-color: #bbf7d0; }
    input.grade-input.nb { color: #92400e; background: #fef3c7; border-color: #f59e0b; font-weight: 700; font-size: 11px; }

    .save-bar {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 12px;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 2px solid #f3f4f6;
    }
    .btn-save {
        position: relative;
        z-index: 9999;
        pointer-events: auto;
        padding: 12px 28px;
        font-size: 15px;
        border-radius: 50px;
        box-shadow: 0 10px 25px rgba(16, 185, 129, 0.35);
        cursor: pointer;
    }
</style>
<div class="journal-container">
    <div class="card">
        <div class="card-header">
            <h2><span class="icon-wrapper"><i class="fas fa-book"></i></span> Оценки: <?= htmlspecialchars($subjectName, ENT_QUOTES, 'UTF-8') ?></h2>
        </div>

        <?php if (isset($_GET['saved']) && $_GET['saved'] === '1'): ?>
            <div class="journal-alert success" role="status">
                Оценки сохранены. Добавлено: <?= (int)($_GET['ins'] ?? 0) ?>,
                обновлено: <?= (int)($_GET['upd'] ?? 0) ?>,
                удалено: <?= (int)($_GET['del'] ?? 0) ?>.
            </div>
        <?php endif; ?>
        <?php if ($saveError !== ''): ?>
            <div class="journal-alert error" role="alert"><?= htmlspecialchars($saveError, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div class="filter-wrap">
            <form method="get" action="index.php">
                <input type="hidden" name="page" value="8">
                <div class="filter-group"><label>Класс</label><select name="grade"><?php for ($g = 5; $g <= 11; $g++): ?><option value="<?= $g ?>" <?= $g == $grade ? 'selected' : '' ?>><?= $g ?></option><?php endfor; ?></select></div>
                <div class="filter-group"><label>Группа</label><select name="class"><?php foreach (['А','Б','В'] as $c): ?><option value="<?= $c ?>" <?= $c == $class ? 'selected' : '' ?>><?= $c ?></option><?php endforeach; ?></select></div>
                <div class="filter-group"><label>Предмет</label><select name="subject"><?php foreach ($subjectOptions as $id => $name): ?><option value="<?= (int)$id ?>" <?= $id === $subjectId ? 'selected' : '' ?>><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
                <div class="filter-group"><label>Четверть</label><select name="quarter"><option value="1" <?= $quarter == 1 ? 'selected' : '' ?>>I</option><option value="2" <?= $quarter == 2 ? 'selected' : '' ?>>II</option><option value="3" <?= $quarter == 3 ? 'selected' : '' ?>>III</option><option value="4" <?= $quarter == 4 ? 'selected' : '' ?>>IV</option></select></div>
                <div class="filter-group"><label>Год</label><input type="number" name="year" value="<?= $year ?>" min="2000" max="2100"></div>
                <div class="filter-group" style="flex: 0 0 auto;"><label>&nbsp;</label><button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Показать</button></div>
            </form>
        </div>

        <form method="post" action="" id="gradesForm">
            <input type="hidden" name="grade" value="<?= $grade ?>">
            <input type="hidden" name="class" value="<?= htmlspecialchars($class, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="subject" value="<?= $subjectId ?>">
            <input type="hidden" name="quarter" value="<?= $quarter ?>">
            <input type="hidden" name="year" value="<?= $year ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['journal_csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th class="col-num" rowspan="2">№</th>
                            <th class="col-name" rowspan="2">Ученик</th>
                            <th class="col-avg" rowspan="2">Ср.</th>
                            <th class="col-final" rowspan="2">Итог</th>
                            <?php foreach ($daysByMonth as $m => $dates): ?>
                                <th class="month-th" colspan="<?= count($dates) ?>"><?= $russianMonths[$m] ?></th>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <?php foreach ($daysByMonth as $m => $dates): ?>
                                <?php foreach ($dates as $d):
                                    $dayOfWeek = $d->format('N');
                                    $dayName = array_search($dayOfWeek, $dayOfWeekMap);
                                ?>
                                    <th class="day-th">
                                        <?= $d->format('d') ?>
                                        <span class="day-name"><?= $russianDays[$dayName] ?? '' ?></span>
                                    </th>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($allDates)): ?>
                            <tr><td colspan="100" style="padding:40px;text-align:center;color:#6b7280;">Нет уроков по этому предмету в выбранном периоде</td></tr>
                        <?php else: ?>
                            <?php $i = 1; foreach ($students as $st):
                                $uid = (int)$st['id'];
                                $sum = 0; $cnt = 0;
                                foreach ($allDates as $d) {
                                    $ds = $d->format('Y-m-d');
                                    $val = $existingGrades[$uid][$ds] ?? null;
                                    if ($val !== null && is_numeric($val)) { $sum += (float)$val; $cnt++; }
                                }
                                $avg = $cnt ? round($sum / $cnt, 1) : '';
                                $final = $avg !== '' ? (string)round($avg) : '';
                            ?>
                            <tr>
                                <td class="col-num"><?= $i++ ?></td>
                                <td class="col-name"><?= htmlspecialchars($st['surname'] . ' ' . $st['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="col-avg"><?= $avg !== '' ? $avg : '—' ?></td>
                                <td class="col-final"><?= $final !== '' ? $final : '—' ?></td>
                                <?php foreach ($allDates as $d):
                                    $ds = $d->format('Y-m-d');
                                    $existing = $existingGrades[$uid][$ds] ?? '';
                                    $valClass = '';
                                    $existingLower = mb_strtolower(trim((string)$existing), 'UTF-8');
                                    if (is_numeric($existing)) {
                                        $valClass = ($existing <= 5) ? 'bad' : (($existing >= 8) ? 'good' : '');
                                    } elseif ($existingLower === 'н/б') {
                                        $valClass = 'nb';
                                    }
                                ?>
                                    <td>
                                        <input class="grade-input <?= $valClass ?>" type="text" name="grades[<?= $uid ?>][<?= $ds ?>]" value="<?= htmlspecialchars((string)$existing, ENT_QUOTES, 'UTF-8') ?>" placeholder="—" maxlength="3">
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($allDates)): ?>
            <div class="save-bar">
                <button type="submit" form="gradesForm" class="btn btn-success btn-save">
                    <i class="fas fa-save"></i> Сохранить оценки
                </button>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>