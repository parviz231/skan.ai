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

    
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM users WHERE grade=:grade AND class=:class");
    $stmt->execute(['grade'=>$grade, 'class'=>$class]);
    $total = (int)$stmt->fetchColumn();

   
    $sqlClass = "
        SELECT DATE(v.time) as date,
               COUNT(DISTINCT CASE WHEN v.visit='visited' THEN v.user_id END) as present
        FROM visits v
        JOIN users u ON u.id = v.user_id
        WHERE u.grade=:grade AND u.class=:class
          AND v.time BETWEEN :from AND :to
        GROUP BY DATE(v.time)
        ORDER BY DATE(v.time)
    ";
    $stmt = $pdo->prepare($sqlClass);
    $stmt->execute([
        'grade'=>$grade, 'class'=>$class,
        'from'=>$from.' 00:00:00', 'to'=>$to.' 23:59:59'
    ]);
    $rowsClass = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $dates = [];
    $percentsClass = [];
    $presentsArr = [];
    $mapClass = [];
    foreach ($rowsClass as $r) $mapClass[$r['date']] = $r['present'];

   
    $period = new DatePeriod(
        new DateTime($from),
        new DateInterval('P1D'),
        (new DateTime($to))->modify('+1 day')
    );

    foreach ($period as $dt) {
        $d = $dt->format("Y-m-d");
        $present = $mapClass[$d] ?? 0;
        $percent = $total > 0 ? round(100 * $present / $total, 1) : 0;
        $dates[] = $d;
        $percentsClass[] = $percent;
        $presentsArr[] = "$present/$total";
    }

    $avgClass = count($percentsClass) ? round(array_sum($percentsClass)/count($percentsClass),1) : 0;

    
    $sqlAll = "
        SELECT DATE(v.time) as date,
               COUNT(DISTINCT CASE WHEN v.visit='visited' THEN v.user_id END) as present,
               COUNT(DISTINCT u.id) as total_users
        FROM visits v
        JOIN users u ON u.id = v.user_id
        WHERE v.time BETWEEN :from AND :to
        GROUP BY DATE(v.time)
        ORDER BY DATE(v.time)
    ";
    $stmt = $pdo->prepare($sqlAll);
    $stmt->execute([
        'from'=>$from.' 00:00:00', 'to'=>$to.' 23:59:59'
    ]);
    $rowsAll = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $percentsAll = [];
    $presentsAll = [];
    $mapAll = [];
    foreach ($rowsAll as $r) $mapAll[$r['date']] = ['present'=>$r['present'], 'total'=>$r['total_users']];

    foreach ($period as $dt) {
        $d = $dt->format("Y-m-d");
        $present = $mapAll[$d]['present'] ?? 0;
        $totalAll = $mapAll[$d]['total'] ?? 0;
        $percent = $totalAll>0 ? round(100 * $present / $totalAll,1) : 0;
        $percentsAll[] = $percent;
        $presentsAll[] = "$present/$totalAll";
    }
    $avgAll = count($percentsAll) ? round(array_sum($percentsAll)/count($percentsAll),1) : 0;

    
    $classes = $pdo->query("SELECT DISTINCT grade, class FROM users ORDER BY grade,class")->fetchAll(PDO::FETCH_ASSOC);
    $classData = [];
    foreach ($classes as $c) {
        $stmt = $pdo->prepare("
            SELECT DATE(v.time) as date,
                   COUNT(DISTINCT CASE WHEN v.visit='visited' THEN v.user_id END) as present,
                   COUNT(DISTINCT u.id) as total
            FROM visits v
            JOIN users u ON u.id = v.user_id
            WHERE u.grade=:grade AND u.class=:class
              AND v.time BETWEEN :from AND :to
            GROUP BY DATE(v.time)
        ");
        $stmt->execute([
            'grade'=>$c['grade'], 'class'=>$c['class'],
            'from'=>$from.' 00:00:00', 'to'=>$to.' 23:59:59'
        ]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $map = [];
        foreach ($rows as $r) $map[$r['date']] = $r['present'];
        $data = [];
        $totalUsers = $pdo->prepare("SELECT COUNT(*) FROM users WHERE grade=:grade AND class=:class");
        $totalUsers->execute(['grade'=>$c['grade'],'class'=>$c['class']]);
        $totalCount = (int)$totalUsers->fetchColumn();
        foreach ($period as $dt) {
            $d = $dt->format("Y-m-d");
            $present = $map[$d] ?? 0;
            $data[] = $totalCount>0 ? round(100*$present/$totalCount,1):0;
        }
        $classData[] = ['label'=>$c['grade'].$c['class'], 'data'=>$data];
    }

   
    $colors = ['#2196f3','#32cd32','#ff9800','#ff5722','#9c27b0','#00bcd4','#f44336','#ffc107','#8bc34a','#673ab7','#ff4081','#03a9f4'];
    $avgPerClass = [];
    foreach($classData as $i=>$cls) {
        $avg = count($cls['data']) ? round(array_sum($cls['data'])/count($cls['data']),1) : 0;
        $avgPerClass[] = ['label'=>$cls['label'], 'avg'=>$avg, 'color'=>$colors[$i % count($colors)]];
    }
    usort($avgPerClass, function($a,$b){ return $b['avg'] <=> $a['avg']; });
    $top3Classes = array_slice($avgPerClass,0,3);

    
    $host = "mysql-8.4";
    $user = "root";
    $pass = "";
    $db   = "face_recognition"; 
    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) die("Ошибка подключения: " . $conn->connect_error);

    $sql = "
        SELECT 
            u.id,
            u.name,
            u.surname,
            u.grade,
            u.class,
            ROUND(SUM(v.visit='visited') / COUNT(v.visit) * 100, 2) AS attendance
        FROM users u
        JOIN visits v ON u.id = v.user_id
        GROUP BY u.id
        ORDER BY attendance DESC
        LIMIT 10
    ";
    $result = $conn->query($sql);

} catch (PDOException $e) {
    die("Ошибка БД: " . $e->getMessage());
}
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<title>Диаграммы посещаемости</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    .card1 { max-width:1100px; margin:auto; background: linear-gradient(145deg, #ffffff, #e0f7ff);       
        padding:20px; border-radius:15px; box-shadow:0 10px 25px rgba(0,0,0,0.1); margin-bottom:25px; margin-top: 50px;;
      }
h2 { margin-top:0; color:#0366d6; text-align:center; }
canvas { background:#fdfdfd; border-radius:12px; max-width:1200px;}
.controls { text-align:center; margin-bottom:15px; }
select, button { padding:6px 10px; margin:5px; border-radius:8px; border:1px solid #c0e0ff; background:#d9f0ff; color:#0366d6; font-weight:bold; }
button:hover { background:#a8e0ff; cursor:pointer; }
#classLegend div { font-weight:bold; }
.top3Card { background: #ffffff33; border-radius:10px; padding:10px 15px; min-width:80px; border:1px solid; }

 h1 {
      text-align: center;
      color: #333;
    }
    .table {
      margin: 20px auto;
      border-collapse: collapse;
      width: 95%;
      background: white;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .th, .td {
      padding: 12px 15px;
      text-align: center;
    }
    .th {
      background: #2C4361;
      color: white;
      font-size: 16px;
    }
    .tr:nth-child(even) {
      background: #f1f2f6;
    }
    .tr:hover {
      background: #dfe4ea;
    }

    h2 {
      text-align: center;
      font-size: 26px;
      margin-bottom: 15px;
    }
</style>
</head>
<body>
<div style="margin-left:100px; margin-right:100px;">
 <h1 style="margin-top:50px;">🏆 ТОП-10 лучших учеников по посещаемости</h1>
 
<table class="table">
    <tr class="tr">
      <th class="th">Место</th>
      <th class="th">ФИО</th>
      <th class="th">Класс</th>
      <th class="th">Посещаемость (%)</th>
    </tr>
    <?php
    if ($result && $result->num_rows > 0) {
        $rank = 1;
        while($row = $result->fetch_assoc()) {
            $fio = $row["surname"]." ".$row["name"];
            $classRow = $row["grade"].$row["class"];
            $att = $row["attendance"];

            $badge = "";
            if ($rank == 1) $badge = "<span class='badge gold'>🥇</span>";
            elseif ($rank == 2) $badge = "<span class='badge silver'>🥈</span>";
            elseif ($rank == 3) $badge = "<span class='badge bronze'>🥉</span>";
            else $badge = "<span class='badge good'>✔</span>";

            echo "<tr class='tr'>
                    <td class='td'>$rank $badge</td>
                    <td class='td'>$fio</td>
                    <td class='td'>$classRow</td>
                    <td class='td'>{$att}%</td>
                  </tr>";
            $rank++;
        }
    } else {
        echo "<tr class='tr'><td colspan='4' class='td'>Нет данных</td></tr>";
    }
    ?>
</table>

<div class="card1">
<?php if(isset($_GET['grade'])){ ?>
    <h2>Посещаемость класса <?= htmlspecialchars($_GET['grade']) ?><?= htmlspecialchars($_GET['class']) ?> за <?= $months[$month] ?></h2>
<?php } else { ?>
    <h2>Посещаемость класса 10A за <?= $months[$month] ?></h2>
<?php } ?>

<div class="controls">
<form method="get">
    Класс (grade):
    <select name="grade">
        <?php for($g=1;$g<=11;$g++): ?>
        <option value="<?= $g ?>" <?= $g==$grade?'selected':'' ?>><?= $g ?></option>
        <?php endfor; ?>
    </select>
    Группа (class):
    <select name="class">
        <?php foreach (['A','Б','В'] as $c): ?>
        <option value="<?= $c ?>" <?= $c==$class?'selected':'' ?>><?= $c ?></option>
        <?php endforeach; ?>
    </select>

    Месяц:
    <select name="month">
        <?php foreach ($months as $num=>$name): ?>
            <option value="<?= $num ?>" <?= $num==$month?'selected':'' ?>><?= $name ?></option>
        <?php endforeach; ?>
    </select>

    <button type="submit">Показать</button>
</form>
</div>

<canvas id="chartClass" height="600" width="1100"></canvas>
</div>

<div class="card1">
<h2>Средняя посещаемость всех классов за <?= $months[$month] ?></h2>
<canvas id="chartAll" height="600" width="1100"></canvas>
</div>

<div class="card1">
<h2>Сравнение посещаемости всех классов (<?= $months[$month] ?>)</h2>
<canvas id="chartCompare" height="600" width="1100"></canvas>
<div id="classLegend" style="margin-top:10px; text-align:center; display:flex; flex-wrap:wrap; justify-content:center;"></div>
<div style="margin-top:15px; text-align:center;">
  <h3 style="color:#0366d6;">ТОП 3 посещаемых класса</h3>
  <div style="display:flex; justify-content:center; gap:15px; flex-wrap:wrap;">
    <?php foreach($top3Classes as $c){ ?>
      <div class="top3Card" style="border-color:<?= $c['color'] ?>;">
        <strong style="color:<?= $c['color'] ?>; font-size:16px;"><?= $c['label'] ?></strong><br>
        <span style="font-size:14px; color:#0f172a;"><?= $c['avg'] ?>%</span>
      </div>
    <?php } ?>
  </div>
</div>
</div>
</div>

<script>
const labels = <?= json_encode($dates, JSON_UNESCAPED_UNICODE) ?>;
const percentsClass = <?= json_encode($percentsClass, JSON_UNESCAPED_UNICODE) ?>;
const presentsClass = <?= json_encode($presentsArr, JSON_UNESCAPED_UNICODE) ?>;
const avgClass = <?= json_encode($avgClass) ?>;

new Chart(document.getElementById('chartClass').getContext('2d'), {
  type: 'line',
  data: {
    labels: labels,
    datasets: [
      { label:'Посещаемость (%)', data:percentsClass, fill:true, backgroundColor:'rgba(135,206,250,0.35)',
        borderColor:'#2196f3', tension:0.3, pointRadius:5, pointHoverRadius:7 },
      { label:'Средняя', data:labels.map(()=>avgClass), borderColor:'#ff9800', borderDash:[6,6], fill:false, pointRadius:0 }
    ]
  },
  options:{ scales:{ y:{beginAtZero:true,max:100,ticks:{callback:v=>v+'%'}} },
    plugins:{ legend:{ position:'top' }, tooltip:{ callbacks:{ label:function(ctx){ if(ctx.datasetIndex===0) return presentsClass[ctx.dataIndex]+' — '+ctx.formattedValue+'%'; return 'Средняя: '+avgClass+'%'; }}}}
  }
});

const percentsAll = <?= json_encode($percentsAll, JSON_UNESCAPED_UNICODE) ?>;
const presentsAll = <?= json_encode($presentsAll, JSON_UNESCAPED_UNICODE) ?>;
const avgAll = <?= json_encode($avgAll) ?>;

new Chart(document.getElementById('chartAll').getContext('2d'), {
  type: 'line',
  data: {
    labels: labels,
    datasets: [
      { label:'Посещаемость всех классов (%)', data:percentsAll, fill:true, backgroundColor:'rgba(144,238,144,0.35)',
        borderColor:'#32cd32', tension:0.3, pointRadius:5, pointHoverRadius:7 },
      { label:'Средняя', data:labels.map(()=>avgAll), borderColor:'#ff5722', borderDash:[6,6], fill:false, pointRadius:0 }
    ]
  },
  options:{ scales:{ y:{beginAtZero:true,max:100,ticks:{callback:v=>v+'%'}} },
    plugins:{ legend:{ position:'top' }, tooltip:{ callbacks:{ label:function(ctx){ if(ctx.datasetIndex===0) return presentsAll[ctx.dataIndex]+' — '+ctx.formattedValue+'%'; return 'Средняя: '+avgAll+'%'; }}}}
  }
});

const classData = <?= json_encode($classData, JSON_UNESCAPED_UNICODE) ?>;
const colors = <?= json_encode($colors) ?>;

const legendDiv = document.getElementById('classLegend');
classData.forEach((cls,i)=>{
    const color = colors[i % colors.length];
    const item = document.createElement('div');
    item.style.display = 'flex';
    item.style.alignItems = 'center';
    item.style.margin = '5px 10px';
    item.innerHTML = `<div style="width:20px; height:12px; background:${color}; margin-right:5px; border-radius:3px;"></div>${cls.label}`;
    legendDiv.appendChild(item);
});

new Chart(document.getElementById('chartCompare').getContext('2d'), {
  type: 'line',
  data: {
    labels: labels,
    datasets: classData.map((cls,i)=>({
      label: cls.label,
      data: cls.data,
      fill:false,
      borderColor: colors[i % colors.length],
      tension:0.3,
      pointRadius:3
    }))
  },
  options:{
    scales:{ y:{beginAtZero:true,max:100,ticks:{callback:v=>v+'%'}} },
    plugins:{ legend:{ display:false }, tooltip:{ callbacks:{ label:ctx=>ctx.dataset.label + ': ' + ctx.formattedValue+'%' } } }
  }
});
</script>
</body>
</html>

<?php require_once("js.php"); ?>
