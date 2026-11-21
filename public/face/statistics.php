<?php
session_start();
require_once("header.php");

date_default_timezone_set("Asia/Dushanbe");

$months = [
    1=>'Январь',2=>'Февраль',3=>'Март',4=>'Апрель',5=>'Май',6=>'Июнь',
    7=>'Июль',8=>'Август',9=>'Сентябрь',10=>'Октябрь',11=>'Ноябрь',12=>'Декабрь'
];

$grade = isset($_GET['grade']) ? (int)$_GET['grade'] : 10;
$class = isset($_GET['class']) ? $_GET['class'] : "A";
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$year = date('Y'); 

$from = date("Y-m-01", strtotime("$year-$month-01"));
$to = date("Y-m-t", strtotime("$year-$month-01"));

try {
    require_once("config2.php"); 
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // --- БЛОК 1: ТОП-3 УЧЕНИКА ДЛЯ ПЬЕДЕСТАЛА ---
    $top3_sql = "
        SELECT u.id, u.name, u.surname, u.grade, u.class,
               COALESCE(ROUND(SUM(v.visit IN ('visited', 'late')) / NULLIF(COUNT(v.visit), 0) * 100, 2), 0) AS attendance
        FROM users u
        LEFT JOIN visits v ON u.id = v.user_id
        GROUP BY u.id, u.name, u.surname, u.grade, u.class
        ORDER BY attendance DESC
        LIMIT 3
    ";
    $top3_result = $pdo->query($top3_sql)->fetchAll(PDO::FETCH_ASSOC);

    // --- БЛОК 2: ОСТАЛЬНЫЕ УЧЕНИКИ (места с 4 по 10) ---
    $top10_sql = "
        SELECT u.id, u.name, u.surname, u.grade, u.class,
               COALESCE(ROUND(SUM(v.visit IN ('visited', 'late')) / NULLIF(COUNT(v.visit), 0) * 100, 2), 0) AS attendance
        FROM users u
        LEFT JOIN visits v ON u.id = v.user_id
        GROUP BY u.id, u.name, u.surname, u.grade, u.class
        ORDER BY attendance DESC
        LIMIT 10
    ";
    $top10_result = $pdo->query($top10_sql)->fetchAll(PDO::FETCH_ASSOC);

    // --- БЛОК 3: ДАННЫЕ ДЛЯ ГРАФИКОВ ---
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM users WHERE grade=:grade AND class=:class");
    $stmt->execute(['grade'=>$grade, 'class'=>$class]);
    $total = (int)$stmt->fetchColumn();

    $sqlClass = "
        SELECT DATE(v.time) as date, COUNT(DISTINCT CASE WHEN v.visit IN ('visited', 'late') THEN v.user_id END) as present
        FROM visits v
        JOIN users u ON u.id = v.user_id
        WHERE u.grade=:grade AND u.class=:class AND v.time BETWEEN :from AND :to
        GROUP BY DATE(v.time) ORDER BY DATE(v.time)
    ";
    $stmt = $pdo->prepare($sqlClass);
    $stmt->execute(['grade'=>$grade, 'class'=>$class, 'from'=>$from.' 00:00:00', 'to'=>$to.' 23:59:59']);
    $rowsClass = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $dates = [];
    $percentsClass = [];
    $presentsArr = [];
    $weekendsArr = []; 
    $mapClass = [];
    foreach ($rowsClass as $r) $mapClass[$r['date']] = $r['present'];

    $period = new DatePeriod(new DateTime($from), new DateInterval('P1D'), (new DateTime($to))->modify('+1 day'));

    foreach ($period as $dt) {
        $d = $dt->format("Y-m-d");
        $dayOfWeek = $dt->format('N'); 
        
        if ($dayOfWeek == 7) {
            $dates[] = $d;
            $percentsClass[] = null; 
            $presentsArr[] = "Выходной";
            $weekendsArr[] = true;
        } else {
            $present = $mapClass[$d] ?? 0;
            $percent = $total > 0 ? round(100 * $present / $total, 1) : 0;
            $dates[] = $d;
            $percentsClass[] = $percent;
            $presentsArr[] = "$present/$total";
            $weekendsArr[] = false;
        }
    }

    $avgClass = 0;
    $validClass = array_filter($percentsClass, function($v) { return $v !== null; });
    if (count($validClass)) $avgClass = round(array_sum($validClass)/count($validClass),1);

    $sqlAll = "
        SELECT DATE(v.time) as date,
               COUNT(DISTINCT CASE WHEN v.visit IN ('visited', 'late') THEN v.user_id END) as present,
               (SELECT COUNT(*) FROM users) as total_users
        FROM visits v
        WHERE v.time BETWEEN :from AND :to GROUP BY DATE(v.time) ORDER BY DATE(v.time)
    ";
    $stmt = $pdo->prepare($sqlAll);
    $stmt->execute(['from'=>$from.' 00:00:00', 'to'=>$to.' 23:59:59']);
    $rowsAll = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalAllUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

    $percentsAll = [];
    $presentsAll = [];
    $mapAll = [];
    foreach ($rowsAll as $r) $mapAll[$r['date']] = ['present'=>$r['present'], 'total'=>$r['total_users']];

    foreach ($period as $dt) {
        $d = $dt->format("Y-m-d");
        $dayOfWeek = $dt->format('N');
        if ($dayOfWeek == 7) {
            $percentsAll[] = null;
            $presentsAll[] = "Выходной";
        } else {
            $present = $mapAll[$d]['present'] ?? 0;
            $totalAll = $mapAll[$d]['total'] ?? $totalAllUsers;
            $percent = $totalAll>0 ? round(100 * $present / $totalAll,1) : 0;
            $percentsAll[] = $percent;
            $presentsAll[] = "$present/$totalAll";
        }
    }
    
    $avgAll = 0;
    $validAll = array_filter($percentsAll, function($v) { return $v !== null; });
    if (count($validAll)) $avgAll = round(array_sum($validAll)/count($validAll),1);

    $classes = $pdo->query("SELECT DISTINCT grade, class FROM users ORDER BY grade,class")->fetchAll(PDO::FETCH_ASSOC);
    $classData = [];
    foreach ($classes as $c) {
        $stmt = $pdo->prepare("
            SELECT DATE(v.time) as date, COUNT(DISTINCT CASE WHEN v.visit IN ('visited', 'late') THEN v.user_id END) as present, COUNT(DISTINCT u.id) as total
            FROM visits v JOIN users u ON u.id = v.user_id
            WHERE u.grade=:grade AND u.class=:class AND v.time BETWEEN :from AND :to
            GROUP BY DATE(v.time)
        ");
        $stmt->execute(['grade'=>$c['grade'], 'class'=>$c['class'], 'from'=>$from.' 00:00:00', 'to'=>$to.' 23:59:59']);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $map = [];
        foreach ($rows as $r) $map[$r['date']] = $r['present'];
        
        $data = [];
        $totalUsers = $pdo->prepare("SELECT COUNT(*) FROM users WHERE grade=:grade AND class=:class");
        $totalUsers->execute(['grade'=>$c['grade'],'class'=>$c['class']]);
        $totalCount = (int)$totalUsers->fetchColumn();
        
        foreach ($period as $dt) {
            $d = $dt->format("Y-m-d");
            $dayOfWeek = $dt->format('N');
            if ($dayOfWeek == 7) {
                $data[] = null;
            } else {
                $present = $map[$d] ?? 0;
                $data[] = $totalCount>0 ? round(100*$present/$totalCount,1):0;
            }
        }
        $classData[] = ['label'=>$c['grade'].$c['class'], 'data'=>$data];
    }

    $colors = ['#0071e3','#34c759','#ff9500','#ff3b30','#af52de','#5ac8fa','#ffcc00','#ff2d55','#5856d6','#8e8e93'];
    $avgPerClass = [];
    foreach($classData as $i=>$cls) {
        $valid = array_filter($cls['data'], function($v) { return $v !== null; });
        $avg = count($valid) ? round(array_sum($valid)/count($valid),1) : 0;
        $avgPerClass[] = ['label'=>$cls['label'], 'avg'=>$avg, 'color'=>$colors[$i % count($colors)]];
    }
    usort($avgPerClass, function($a,$b){ return $b['avg'] <=> $a['avg']; });
    $top3Classes = array_slice($avgPerClass,0,3);

} catch (PDOException $e) {
    die("Ошибка БД: " . $e->getMessage());
}
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<title>СКАН — ТОП-10 лучших учеников</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    body {
        background: #f5f5f7;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        color: #1d1d1f;
        margin: 0;
        padding-top: 0 !important;
    }

    .stats-page-wrap {
        max-width: 1000px;
        margin: 40px auto;
        padding: 0 24px;
        box-sizing: border-box;
    }

    /* Header */
    .stats-header {
        text-align: center;
        margin-bottom: 32px;
    }
    .stats-header-icon {
        width: 64px;
        height: 64px;
        border-radius: 18px;
        background: #1d1d1f;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 16px;
        font-size: 28px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    }
    .stats-header h1 {
        font-size: 32px;
        font-weight: 800;
        letter-spacing: -0.03em;
        margin: 0 0 6px;
    }
    .stats-header p {
        font-size: 16px;
        color: #6e6e73;
        margin: 0;
    }

    /* --- ПЬЕДЕСТАЛ ТОП-3 --- */
    .top3-grid {
        display: grid;
        grid-template-columns: 1fr 1.2fr 1fr;
        gap: 20px;
        margin-bottom: 32px;
        align-items: end;
    }

    .top3-card {
        background: #ffffff;
        border: 1px solid #e5e5ea;
        border-radius: 16px;
        padding: 24px 20px;
        text-align: center;
        box-shadow: 0 4px 16px rgba(0,0,0,0.04);
        transition: all 0.3s ease;
        position: relative;
    }

    .top3-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 32px rgba(0,0,0,0.1);
    }

    .top3-card.first {
        border-color: #fbbf24;
        padding: 40px 20px 32px;
        box-shadow: 0 8px 32px rgba(251,191,36,0.2);
    }

    .top3-medal {
        font-size: 48px;
        margin-bottom: 16px;
        display: inline-block;
        line-height: 1;
    }
    
    .top3-card.first .top3-medal {
        font-size: 64px;
        margin-bottom: 20px;
    }

    .top3-name {
        font-size: 16px;
        font-weight: 700;
        margin-bottom: 6px;
        color: #1d1d1f;
    }

    .top3-class {
        font-size: 13px;
        color: #6e6e73;
        margin-bottom: 12px;
    }

    .top3-rating {
        font-size: 28px;
        font-weight: 800;
        color: #0071e3;
        letter-spacing: -0.02em;
    }

    .top3-card.first .top3-rating {
        font-size: 36px;
    }

    .top3-rating-label {
        font-size: 12px;
        color: #6e6e73;
        font-weight: 500;
        margin-top: 4px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    /* --- ТАБЛИЦА --- */
    .rating-card {
        background: #fff;
        border: 1px solid #e5e5ea;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0,0,0,0.04);
        margin-bottom: 32px;
    }
    .rating-table { width: 100%; border-collapse: collapse; }
    .rating-table thead th {
        padding: 16px 20px; background: #1d1d1f; color: #fff;
        font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; text-align: left;
    }
    .rating-table thead th:first-child { width: 80px; }
    .rating-table thead th:last-child { width: 140px; }
    .rating-table tbody tr { border-bottom: 1px solid #f0f0f2; transition: background 0.15s; }
    .rating-table tbody tr:hover { background: #f8f8fa; }
    .rating-table tbody tr:last-child { border-bottom: none; }
    .rating-table tbody td { padding: 16px 20px; font-size: 15px; }
    .rank-cell { display: flex; align-items: center; gap: 8px; }
    .rank-num { font-weight: 700; font-size: 16px; width: 24px; text-align: center; }
    
    .rating-badge {
        padding: 6px 16px; border-radius: 100px; font-size: 13px; font-weight: 700; letter-spacing: -0.01em;
    }
    .rating-high { background: #f0fdf4; color: #059669; }
    .rating-medium { background: #fffbeb; color: #d97706; }
    .rating-low { background: #fef2f2; color: #dc2626; }

    /* --- ФИЛЬТРЫ --- */
    .filter-card {
        background: #fff; border: 1px solid #e5e5ea; border-radius: 16px;
        padding: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.04); margin-bottom: 24px;
        display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-end; justify-content: center;
    }
    .filter-group { display: flex; flex-direction: column; gap: 8px; min-width: 120px; }
    .filter-group label { font-size: 12px; font-weight: 600; color: #6e6e73; text-transform: uppercase; letter-spacing: 0.05em; }
    .filter-group select {
        padding: 10px 14px; border-radius: 8px; border: 1px solid #d2d2d7;
        font-size: 14px; font-family: inherit; background: #fff; color: #1d1d1f;
        outline: none; cursor: pointer; transition: all 0.2s;
    }
    .filter-group select:focus { border-color: #0071e3; box-shadow: 0 0 0 3px rgba(0,113,227,0.1); }
    .filter-btn {
        padding: 10px 24px; background: #0071e3; color: #fff; border: none;
        border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer;
        transition: all 0.2s; height: 42px;
    }
    .filter-btn:hover { background: #0077ed; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,113,227,0.3); }

    /* --- ГРАФИКИ --- */
    .chart-card {
        background: #fff; border: 1px solid #e5e5ea; border-radius: 16px;
        padding: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.04); margin-bottom: 24px;
    }
    .chart-title { font-size: 18px; font-weight: 700; color: #1d1d1f; margin-bottom: 20px; text-align: center; }
    
    .custom-legend { display: flex; flex-wrap: wrap; justify-content: center; gap: 12px; margin-top: 16px; }
    .legend-item { display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; color: #1d1d1f; }
    .legend-dot { width: 12px; height: 12px; border-radius: 3px; }

    .top3-row { display: flex; justify-content: center; gap: 16px; margin-top: 24px; flex-wrap: wrap; }
    .top3-badge {
        background: #f8f8fa; border: 1px solid #e5e5ea; border-radius: 12px;
        padding: 16px 24px; text-align: center; min-width: 140px;
    }
    .top3-badge strong { display: block; font-size: 18px; margin-bottom: 4px; }
    .top3-badge span { font-size: 14px; color: #6e6e73; font-weight: 600; }

    @media (max-width: 768px) {
        .stats-page-wrap { margin: 20px auto; padding: 0 12px; }
        .stats-header h1 { font-size: 24px; }
        .top3-grid { grid-template-columns: 1fr; gap: 12px; }
        .top3-card.first { order: -1; padding: 32px 20px 24px; }
        .filter-card { flex-direction: column; align-items: stretch; }
        .filter-btn { width: 100%; }
        .rating-table thead th, .rating-table tbody td { padding: 10px 12px; font-size: 13px; }
    }
</style>
</head>
<body>

<div class="stats-page-wrap">

    <!-- Header -->
    <div class="stats-header">
        <div class="stats-header-icon">
            <i class="fas fa-trophy"></i>
        </div>
        <h1>ТОП-10 лучших учеников</h1>
        
    </div>

    <!-- Пьедестал ТОП-3 -->
    <?php if (!empty($top3_result)): ?>
    <div class="top3-grid">
        <?php
        $order = [1, 0, 2];
        $medals = ['🥇', '🥈', '🥉'];
        foreach ($order as $idx):
            if (!isset($top3_result[$idx])) continue;
            $student = $top3_result[$idx];
            $isFirst = $idx === 0;
        ?>
        <div class="top3-card <?= $isFirst ? 'first' : '' ?>">
            <div class="top3-medal"><?= $medals[$idx] ?></div>
            <div class="top3-name"><?= htmlspecialchars($student['surname'] . ' ' . $student['name']) ?></div>
            <div class="top3-class"><?= $student['grade'] . $student['class'] ?> класс</div>
            <div class="top3-rating"><?= number_format($student['attendance'], 1) ?></div>
            <div class="top3-rating-label">Посещаемость</div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Таблица (места с 4 по 10) -->
    <div class="rating-card">
        <table class="rating-table">
            <thead>
                <tr>
                    <th>Место</th>
                    <th>ФИО</th>
                    <th>Класс</th>
                    <th>Посещаемость</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($top10_result && count($top10_result) > 0) {
                    $rank = 1;
                    foreach($top10_result as $row) {
                        if ($rank <= 3) { $rank++; continue; }
                        
                        $fio = $row["surname"] . " " . $row["name"];
                        $classRow = $row["grade"] . $row["class"];
                        $att = $row["attendance"];

                        $ratingClass = 'rating-high';
                        if ($att < 40) $ratingClass = 'rating-low';
                        elseif ($att < 70) $ratingClass = 'rating-medium';
                        
                        echo "<tr>
                                <td>
                                    <span class='rank-cell'>
                                        <span class='rank-num'>$rank</span>
                                    </span>
                                </td>
                                <td><strong>" . htmlspecialchars($fio) . "</strong></td>
                                <td>" . htmlspecialchars($classRow) . "</td>
                                <td><span class='rating-badge $ratingClass'>" . number_format($att, 1) . "%</span></td>
                              </tr>";
                        $rank++;
                    }
                } else {
                    echo "<tr><td colspan='4' style='text-align:center;padding:32px;color:#86868b;font-size:15px;'>Нет данных</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Filter -->
    <div class="filter-card">
        <form method="get" style="display: flex; flex-wrap: wrap; gap: 16px; width: 100%; justify-content: center;">
            <div class="filter-group">
                <label>Класс</label>
                <select name="grade">
                    <?php for($g=5;$g<=11;$g++): ?>
                    <option value="<?= $g ?>" <?= $g==$grade?'selected':'' ?>><?= $g ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Группа</label>
                <select name="class">
                    <?php foreach (['А','Б','В','A','B','C'] as $c): ?>
                    <option value="<?= $c ?>" <?= $c==$class?'selected':'' ?>><?= $c ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Месяц</label>
                <select name="month">
                    <?php foreach ($months as $num=>$name): ?>
                        <option value="<?= $num ?>" <?= $num==$month?'selected':'' ?>><?= $name ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="filter-btn"><i class="fas fa-search"></i> Показать</button>
        </form>
    </div>

    <!-- Class Chart -->
    <div class="chart-card">
        <div class="chart-title">Посещаемость класса <?= htmlspecialchars($grade . $class) ?> за <?= $months[$month] ?></div>
        <canvas id="chartClass" height="120"></canvas>
    </div>

    <!-- All Chart -->
    <div class="chart-card">
        <div class="chart-title">Средняя посещаемость всех классов за <?= $months[$month] ?></div>
        <canvas id="chartAll" height="120"></canvas>
    </div>

    <!-- Compare Chart -->
    <div class="chart-card">
        <div class="chart-title">Сравнение посещаемости всех классов</div>
        <canvas id="chartCompare" height="120"></canvas>
        <div class="custom-legend" id="classLegend"></div>
        
        <div class="top3-row">
            <?php foreach($top3Classes as $c){ ?>
                <div class="top3-badge" style="border-color: <?= $c['color'] ?>;">
                    <strong style="color: <?= $c['color'] ?>;"><?= $c['label'] ?></strong>
                    <span><?= $c['avg'] ?>%</span>
                </div>
            <?php } ?>
        </div>
    </div>

</div>

<script>
const labels = <?= json_encode($dates, JSON_UNESCAPED_UNICODE) ?>;
const percentsClass = <?= json_encode($percentsClass, JSON_UNESCAPED_UNICODE) ?>;
const presentsClass = <?= json_encode($presentsArr, JSON_UNESCAPED_UNICODE) ?>;
const avgClass = <?= json_encode($avgClass) ?>;
const weekends = <?= json_encode($weekendsArr) ?>;

const weekendBackgroundPlugin = {
    id: 'weekendBackground',
    beforeDraw: (chart) => {
        const ctx = chart.ctx;
        const xAxis = chart.scales.x;
        const yAxis = chart.scales.y;
        const meta = chart.getDatasetMeta(0);
        if (!meta || !meta.data) return;
        meta.data.forEach((bar, index) => {
            if (weekends[index]) {
                const barWidth = (meta.data.length > 1) ? (meta.data[1].x - meta.data[0].x) : 30;
                const x = bar.x;
                ctx.save();
                ctx.fillStyle = 'rgba(255, 59, 48, 0.08)'; 
                ctx.fillRect(x - barWidth/2, yAxis.top, barWidth, yAxis.bottom - yAxis.top);
                ctx.restore();
            }
        });
    }
};

const commonOptions = {
    responsive: true,
    maintainAspectRatio: true,
    interaction: { mode: 'index', intersect: false },
    scales: { 
        y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%', color: '#6e6e73' }, grid: { color: '#f0f0f2' } },
        x: { ticks: { color: '#6e6e73' }, grid: { display: false } }
    },
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: '#1d1d1f',
            titleFont: { family: 'Inter', size: 13 },
            bodyFont: { family: 'Inter', size: 13 },
            padding: 12,
            cornerRadius: 8
        }
    }
};

new Chart(document.getElementById('chartClass').getContext('2d'), {
  type: 'line',
  plugins: [weekendBackgroundPlugin],
  data: {
    labels: labels,
    datasets: [
      { label:'Посещаемость', data:percentsClass, fill:true, backgroundColor:'rgba(0, 113, 227, 0.1)', borderColor:'#0071e3', tension:0.3, pointRadius:4, pointHoverRadius:6, spanGaps: true },
      { label:'Средняя', data:labels.map(()=>avgClass), borderColor:'#ff9500', borderDash:[5,5], fill:false, pointRadius:0 }
    ]
  },
  options: { ...commonOptions, plugins: { ...commonOptions.plugins, tooltip: { ...commonOptions.plugins.tooltip, callbacks: { label:function(ctx){ if(ctx.datasetIndex===0) { if(ctx.raw === null) return 'В этот день выходной'; return presentsClass[ctx.dataIndex]+' — '+ctx.formattedValue+'%'; } return 'Средняя: '+avgClass+'%'; } } } } }
});

const percentsAll = <?= json_encode($percentsAll, JSON_UNESCAPED_UNICODE) ?>;
const presentsAll = <?= json_encode($presentsAll, JSON_UNESCAPED_UNICODE) ?>;
const avgAll = <?= json_encode($avgAll) ?>;

new Chart(document.getElementById('chartAll').getContext('2d'), {
  type: 'line',
  plugins: [weekendBackgroundPlugin],
  data: {
    labels: labels,
    datasets: [
      { label:'Все классы', data:percentsAll, fill:true, backgroundColor:'rgba(52, 199, 89, 0.1)', borderColor:'#34c759', tension:0.3, pointRadius:4, pointHoverRadius:6, spanGaps: true },
      { label:'Средняя', data:labels.map(()=>avgAll), borderColor:'#ff3b30', borderDash:[5,5], fill:false, pointRadius:0 }
    ]
  },
  options: { ...commonOptions, plugins: { ...commonOptions.plugins, tooltip: { ...commonOptions.plugins.tooltip, callbacks: { label:function(ctx){ if(ctx.datasetIndex===0) { if(ctx.raw === null) return 'В этот день выходной'; return presentsAll[ctx.dataIndex]+' — '+ctx.formattedValue+'%'; } return 'Средняя: '+avgAll+'%'; } } } } }
});

const classData = <?= json_encode($classData, JSON_UNESCAPED_UNICODE) ?>;
const colors = <?= json_encode($colors) ?>;

const legendDiv = document.getElementById('classLegend');
classData.forEach((cls,i)=>{
    const color = colors[i % colors.length];
    const item = document.createElement('div');
    item.className = 'legend-item';
    item.innerHTML = `<div class="legend-dot" style="background:${color}"></div>${cls.label}`;
    legendDiv.appendChild(item);
});

new Chart(document.getElementById('chartCompare').getContext('2d'), {
  type: 'line',
  plugins: [weekendBackgroundPlugin],
  data: {
    labels: labels,
    datasets: classData.map((cls,i)=>({
      label: cls.label, data: cls.data, fill:false, borderColor: colors[i % colors.length], tension:0.3, pointRadius:3, spanGaps: true
    }))
  },
  options: { ...commonOptions, plugins: { ...commonOptions.plugins, tooltip: { ...commonOptions.plugins.tooltip, callbacks: { label:ctx=>{ if(ctx.raw === null) return ctx.dataset.label + ': Выходной'; return ctx.dataset.label + ': ' + ctx.formattedValue+'%'; } } } } }
});
</script>
</body>
</html>

<?php require_once("js.php"); ?>