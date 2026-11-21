<?php
require_once __DIR__ . '/../config.php';

function gp($name, $def) {
    if (isset($_POST[$name]) && is_scalar($_POST[$name])) return (string)$_POST[$name];
    if (isset($_GET[$name]) && is_scalar($_GET[$name])) return (string)$_GET[$name];
    return $def;
}

$gradeInput = filter_var(gp('grade', '5'), FILTER_VALIDATE_INT);
$quarterInput = filter_var(gp('quarter', '1'), FILTER_VALIDATE_INT);
$yearInput = filter_var(gp('year', (string)date('Y')), FILTER_VALIDATE_INT);
$grade = ($gradeInput !== false && $gradeInput >= 5 && $gradeInput <= 11) ? $gradeInput : 5;
$class = gp('class', 'А');
$class = in_array($class, ['А', 'Б', 'В'], true) ? $class : 'А';
$quarter = ($quarterInput !== false && $quarterInput >= 1 && $quarterInput <= 4) ? $quarterInput : 1;
$year = ($yearInput !== false && $yearInput >= 2000 && $yearInput <= 2100) ? $yearInput : (int)date('Y');

$currentMonth = (int)date('n');
if ($currentMonth >= 9) {
    $studyYearStart = $year;
} else {
    $studyYearStart = $year - 1;
}

$quarters = [
    1 => ["start" => "$studyYearStart-09-01", "end" => "$studyYearStart-10-31"],
    2 => ["start" => "$studyYearStart-11-11", "end" => "$studyYearStart-12-31"],
    3 => ["start" => ($studyYearStart + 1) . "-01-11", "end" => ($studyYearStart + 1) . "-03-21"],
    4 => ["start" => ($studyYearStart + 1) . "-04-01", "end" => ($studyYearStart + 1) . "-05-31"]
];

$startDate = $quarters[$quarter]['start'];
$endDate   = $quarters[$quarter]['end'];

// Получение учеников
$stuStmt = $conn->prepare("SELECT id, surname, name FROM users WHERE grade=? AND class=? ORDER BY surname, name");
$stuStmt->bind_param("is", $grade, $class);
$stuStmt->execute();
$sres = $stuStmt->get_result();
$students = [];
$studentIds = [];
while ($r = $sres->fetch_assoc()) {
    $students[] = $r;
    $studentIds[] = (int)$r['id'];
}
$stuStmt->close();

// Период
$startDT = new DateTime($startDate);
$endDT = new DateTime($endDate);
$endDT->modify('+1 day');
$period = new DatePeriod($startDT, new DateInterval('P1D'), $endDT);

$daysByMonth = [];
$allDates = [];
foreach ($period as $d) {
    if ($d->format('N') == 7) continue;
    $allDates[] = $d;
    $m = (int)$d->format('n');
    $daysByMonth[$m][] = $d;
}

$russianMonths = [
    1=>'Январь', 2=>'Февраль', 3=>'Март', 4=>'Апрель', 
    5=>'Май', 6=>'Июнь', 7=>'Июль', 8=>'Август',
    9=>'Сентябрь', 10=>'Октябрь', 11=>'Ноябрь', 12=>'Декабрь'
];

// Посещаемость
$visits = [];
if ($studentIds) {
    $in = implode(',', $studentIds);
    $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
    $vstmt = $conn->prepare("SELECT user_id, DATE(`time`) AS day, visit FROM visits WHERE DATE(`time`) BETWEEN ? AND ? AND user_id IN ($placeholders) ORDER BY `time`, visit_id");
    $vstmt->execute(array_merge([$startDate, $endDate], $studentIds));
    $vres = $vstmt->get_result();
    while ($vr = $vres->fetch_assoc()) {
        $visits[(int)$vr['user_id']][$vr['day']] = $vr['visit'];
    }
    $vstmt->close();
}
?>
<style>
        .journal-wrap {
            --primary: #4f46e5; --success: #10b981; --danger: #ef4444;
            --text: #1f2937; --text-sec: #6b7280; --bg: #f9fafb; --card: #ffffff; --border: #e5e7eb;
        }

        .journal-wrap { max-width: 100%; margin: 0 auto; padding: 24px; font-family: 'Inter', sans-serif; color: var(--text); }
        
        .card {
            background: var(--card);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border: 1px solid var(--border);
            overflow: hidden;
        }

        .card-header {
            padding: 24px 30px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .card-header h2 {
            font-size: 24px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .card-header h2 .icon { width: 40px; height: 40px; background: linear-gradient(135deg, var(--primary), #7c3aed); border-radius: 10px; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 18px; }

        .filters { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }
        .filters select, .filters input { padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; font-size: 14px; font-family: inherit; background: var(--bg); outline: none; font-weight: 500; }
        .filters button { padding: 10px 20px; background: var(--primary); color: #fff; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .filters button:hover { background: #4338ca; }

        /* Таблица */
        .table-responsive { 
            overflow-x: auto; 
            overflow-y: hidden;
        }
        
        table { 
            border-collapse: separate; 
            border-spacing: 0;
            width: 100%; 
            font-size: 13px; 
        }
        
        th, td { 
            border-bottom: 1px solid var(--border); 
            border-right: 1px solid var(--border); 
            padding: 10px 4px; 
            text-align: center; 
            vertical-align: middle; 
            background: #fff;
        }
        
        th { 
            background: #f8fafc; 
            font-weight: 600; 
            color: var(--text-sec); 
            font-size: 12px; 
            text-transform: uppercase; 
            letter-spacing: 0.5px; 
        }

        /* Закрепленные колонки СЛЕВА */
        .col-sticky-name { 
            position: sticky; 
            left: 0; 
            z-index: 5; 
            text-align: left; 
            padding-left: 20px; 
            font-weight: 600; 
            min-width: 180px; 
            max-width: 180px;
            box-shadow: 2px 0 5px -2px rgba(0,0,0,0.1); 
            border-right: 2px solid var(--border);
        }

        .col-sticky-stat { 
            position: sticky; 
            left: 180px;
            z-index: 5; 
            font-weight: 700; 
            min-width: 80px;
            box-shadow: 2px 0 5px -2px rgba(0,0,0,0.1);
        }

        .col-sticky-abs { 
            position: sticky; 
            left: 260px;
            z-index: 5; 
            font-weight: 700; 
            min-width: 60px;
            box-shadow: 2px 0 5px -2px rgba(0,0,0,0.1);
        }

        th.col-sticky-name, th.col-sticky-stat, th.col-sticky-abs { background: #f8fafc; }

        .month-th { 
            background: #eef2ff !important; 
            color: var(--primary); 
            font-weight: 700; 
            border-right: 2px solid #c7d2fe;
        }
        
        /* ЯЧЕЙКИ */
        .day-cell { width: 34px; min-width: 34px; }
        
        .present-icon { 
            color: var(--success); 
            background: #d1fae5; 
            width: 24px; 
            height: 24px; 
            border-radius: 50%; 
            display: inline-flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 10px; 
            flex-shrink: 0; 
        }
        
        /* Стиль для Н/Б */
        .nb-icon { 
            color: #92400e; 
            background: #fef3c7; 
            width: 24px; 
            height: 24px; 
            border-radius: 50%; 
            display: inline-flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 9px; 
            font-weight: 700;
            flex-shrink: 0;
            border: 1px solid #f59e0b;
        }

        .percent-badge { padding: 4px 8px; border-radius: 6px; font-size: 12px; display: inline-block; }
        .p-g { background: #d1fae5; color: #065f46; }
        .p-w { background: #fef3c7; color: #92400e; }
        .p-b { background: #fee2e2; color: #991b1b; }

        tbody tr:hover td { background: #f8fafc; }
        tbody tr:hover td.col-sticky-name, tbody tr:hover td.col-sticky-stat, tbody tr:hover td.col-sticky-abs { background: #f8fafc; }

        @media (max-width: 768px) {
            .card-header { flex-direction: column; align-items: flex-start; }
            .filters { width: 100%; flex-direction: column; align-items: stretch; }
            .filters select, .filters input, .filters button { width: 100%; }
            
            .col-sticky-name { min-width: 120px; max-width: 120px; padding-left: 10px; font-size: 12px; }
            .col-sticky-stat { left: 120px; min-width: 60px; font-size: 11px;}
            .col-sticky-abs { left: 180px; min-width: 50px; font-size: 11px;}
        }
    </style>

<div class="journal-wrap">
    <div class="card">
        <div class="card-header">
            <h2>
                <span class="icon"><i class="fas fa-clipboard-check"></i></span>
                Журнал посещаемости
            </h2>
            <form method="get" action="index.php" class="filters">
                <input type="hidden" name="page" value="7">
                <select name="grade"><?php for ($g = 5; $g <= 11; $g++): ?><option value="<?= $g ?>" <?= $g == $grade ? 'selected' : '' ?>><?= $g ?> класс</option><?php endfor; ?></select>
                <select name="class"><?php foreach (['А', 'Б', 'В'] as $c): ?><option value="<?= $c ?>" <?= $c == $class ? 'selected' : '' ?>><?= $c ?></option><?php endforeach; ?></select>
                <select name="quarter"><option value="1" <?= $quarter == 1 ? 'selected' : '' ?>>I четв.</option><option value="2" <?= $quarter == 2 ? 'selected' : '' ?>>II четв.</option><option value="3" <?= $quarter == 3 ? 'selected' : '' ?>>III четв.</option><option value="4" <?= $quarter == 4 ? 'selected' : '' ?>>IV четв.</option></select>
                <input type="number" name="year" value="<?= $year ?>" min="2000" max="2100" style="width: 80px;">
                <button type="submit">Показать</button>
            </form>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th class="col-sticky-name" rowspan="2">Ученик</th>
                        <th class="col-sticky-stat" rowspan="2">Усп.</th>
                        <th class="col-sticky-abs" rowspan="2">Н/Б</th>
                        <?php foreach ($daysByMonth as $m => $dates): ?><th class="month-th" colspan="<?= count($dates) ?>"><?= $russianMonths[$m] ?></th><?php endforeach; ?>
                    </tr>
                    <tr>
                        <?php foreach ($daysByMonth as $m => $dates): ?><?php foreach ($dates as $d): ?><th class="day-cell"><?= $d->format('d') ?></th><?php endforeach; ?><?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php $today = date('Y-m-d'); foreach ($students as $st): $uid = (int)$st['id']; $abs = 0; $vis = 0; $tot = 0;
                        foreach ($allDates as $d) {
                            $ds = $d->format('Y-m-d'); if ($ds > $today) continue; $tot++;
                            if (isset($visits[$uid][$ds])) { if (mb_strtolower($visits[$uid][$ds]) == 'visited') $vis++; else $abs++; }
                            elseif ($ds < $today) $abs++;
                        }
                        $pct = $tot > 0 ? round(($vis / $tot) * 100, 1) : 0;
                        $pc = ($pct >= 85) ? 'p-g' : (($pct >= 60) ? 'p-w' : 'p-b');
                    ?>
                    <tr>
                        <td class="col-sticky-name"><?= htmlspecialchars($st['surname'] . ' ' . $st['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="col-sticky-stat"><span class="percent-badge <?= $pc ?>"><?= $pct ?>%</span></td>
                        <td class="col-sticky-abs" style="color:var(--danger);"><?= $abs ?></td>
                        
                        <?php foreach ($allDates as $d): $ds = $d->format('Y-m-d');
                            if ($ds > $today) { 
                                echo '<td class="day-cell"></td>'; 
                            } else {
                                if (isset($visits[$uid][$ds]) && mb_strtolower((string)$visits[$uid][$ds], 'UTF-8') === 'visited') {
                                    echo '<td class="day-cell"><span class="present-icon"><i class="fas fa-check"></i></span></td>';
                                } else {
                                    // Вместо крестика — Н/Б
                                    echo '<td class="day-cell"><span class="nb-icon">н/б</span></td>';
                                }
                            }
                        endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>