<?php
if (isset($_GET["page"]) && !empty(trim($_GET["page"]))) {
    require_once("config.php");
    $page = $_GET["page"];
    if ($page == "1" ) {
    require_once ('pages/pupils/add.php');
    }
    if ($page == "2" ) {
        require_once ('pages/pupils/insert.php');
        }

        if ($page == "3" ) {
            require_once ('pages/pupils/select.php');
            }
        
        if ($page == "4" ) {
            require_once ('pages/pupils/edit.php');
            }
        if ($page == "5" ) {
            require_once ('pages/pupils/update.php');
            }
        if ($page == "6" ) {
            require_once ('pages/pupils/delete.php');
            }
            if ($page == "7" ) {
            require_once ('pages/journals/journal2.php');
            }
        if ($page == "8" ) {
            require_once ('pages/journals/journal.php');
            }
            if ($page == "9" ) {
    require_once ('pages/teacher/add.php');
    }
    if ($page == "10" ) {
        require_once ('pages/teacher/insert.php');
        }

        if ($page == "11" ) {
            require_once ('pages/teacher/select.php');
            }
        
        if ($page == "12" ) {
            require_once ('pages/teacher/edit.php');
            }
        if ($page == "13" ) {
            require_once ('pages/teacher/update.php');
            }
        if ($page == "14" ) {
            require_once ('pages/teacher/delete.php');
            }
            if ($page == "15" ) {
            require_once ('pages/add_schedule.php');
            }
            if ($page == "16" ) {
            require_once ('pages/update_schedule.php');
            }
            if ($page == "17" ) {
            require_once ('pages/select_schedule.php');
            }
            if ($page == "18" ) {
            require_once ('pages/pupils/rating.php');
            }
            if ($page == "19" ) {
            require_once ('pages/achievements/add.php');
            }
            if ($page == "20" ) {
            require_once ('pages/achievements/select.php');
          
          }
          if ($page == "22" ) {
            require_once ('pages/achievements/update.php');
          
          }
          if ($page == "21" ) {
            require_once ('pages/achievements/delete.php');
          
          }
           
        }
else{
    echo "<p class = 'alert alert-light'>Новых сообщений нету!</p>";

?>
<?php
require_once("config.php");
$sql1 = "
SELECT school_year, COUNT(*) AS total
FROM achievements
GROUP BY school_year
ORDER BY school_year;
";
$res1 = $conn->query($sql1);
$years = $ach_count = [];
while ($row = $res1->fetch_assoc()) {
  $years[] = $row['school_year'];
  $ach_count[] = $row['total'];
}

$sql2 = "
SELECT CONCAT(u.grade, u.class) AS class_name,
       ROUND(AVG(g.grade_value), 2) AS avg_grade
FROM users u
LEFT JOIN grades g ON u.id = g.user_id
GROUP BY class_name
ORDER BY avg_grade DESC;
";
$res2 = $conn->query($sql2);
$class_labels = $class_avg = [];
while ($row = $res2->fetch_assoc()) {
  $class_labels[] = $row['class_name'];
  $class_avg[] = $row['avg_grade'];
}

$sql3 = "
SELECT CONCAT(u.surname, ' ', u.name) AS fullname,
       ROUND(SUM(v.visit = 'visited') / COUNT(v.visit) * 100, 2) AS attendance
FROM users u
LEFT JOIN visits v ON u.id = v.user_id
GROUP BY u.id
ORDER BY attendance DESC
LIMIT 10;
";
$res3 = $conn->query($sql3);
$student_labels = $student_att = [];
while ($row = $res3->fetch_assoc()) {
  $student_labels[] = $row['fullname'];
  $student_att[] = $row['attendance'];
}

$sql4 = "
SELECT competition_type, COUNT(*) AS total
FROM achievements
GROUP BY competition_type;
";
$res4 = $conn->query($sql4);
$type_labels = $type_values = [];
while ($row = $res4->fetch_assoc()) {
  $type_labels[] = $row['competition_type'];
  $type_values[] = $row['total'];
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>


h1 {
  text-align: center;
  margin: 40px 0 20px;
  font-size: 32px;
  color: #1a3351;
}

.container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(550px, 1fr));
  gap: 30px;
  justify-content: center;
  padding: 40px;
  max-width: 1400px;
  margin: 0 auto;
}

.chart-block {
  background: #fff;
  border-radius: 25px;
  padding: 25px 35px;
  box-shadow: 0 5px 25px rgba(0,0,0,0.1);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.chart-block:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.chart-block h2 {
  text-align: center;
  font-size: 22px;
  color: #0c2c54;
  margin-bottom: 15px;
}

canvas {
  width: 100% !important;
  height: 340px !important;
}

@media(max-width: 1100px) {
  .container { grid-template-columns: 1fr; }
  canvas { height: 300px !important; }
}
</style>
</head>
<body>

<h1>Статистика школы</h1>

<div class="container">

  <div class="chart-block">
    <h2>Достижения учеников по учебным годам</h2>
    <canvas id="chart1"></canvas>
  </div>

  <div class="chart-block">
    <h2>Успеваемость классов</h2>
    <canvas id="chart2"></canvas>
  </div>


  <div class="chart-block">
    <h2> Посещаемость учеников (топ-10)</h2>
    <canvas id="chart3"></canvas>
  </div>


  <div class="chart-block">
    <h2>Типы соревнований</h2>
    <canvas id="chart4"></canvas>
  </div>

</div>

<script>
<?php
// после формирования массивов $years и $ach_count
array_pop($years);     // убираем последний год
//array_pop($ach_count);  

?>
new Chart(document.getElementById('chart1'), {
  type: 'line',
  data: {
    labels: <?= json_encode($years) ?>,
    datasets: [{
      label: 'Количество достижений',
      data: <?= json_encode($ach_count) ?>,
      borderColor: '#1d4ed8',
      backgroundColor: 'rgba(59,130,246,0.3)',
      fill: true,
      tension: 0.3
    }]
  },
  options: { responsive: true }
});


new Chart(document.getElementById('chart2'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($class_labels) ?>,
    datasets: [{
      label: 'Средняя оценка',
      data: <?= json_encode($class_avg) ?>,
      backgroundColor: 'rgba(34,197,94,0.6)',
      borderColor: '#15803d',
      borderWidth: 2
    }]
  },
  options: { scales: { y: { beginAtZero: true, max: 10 } } }
});

new Chart(document.getElementById('chart3'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($student_labels) ?>,
    datasets: [{
      label: 'Посещаемость (%)',
      data: <?= json_encode($student_att) ?>,
      backgroundColor: 'rgba(250,204,21,0.6)',
      borderColor: '#b45309',
      borderWidth: 2
    }]
  },
  options: { scales: { y: { beginAtZero: true, max: 100 } } }
});

new Chart(document.getElementById('chart4'), {
  type: 'pie',
  data: {
    labels: <?= json_encode($type_labels) ?>,
    datasets: [{
      data: <?= json_encode($type_values) ?>,
      backgroundColor: ['#3b82f6','#22c55e','#f97316','#a855f7'],
      borderColor: '#fff',
      borderWidth: 2
    }]
  },
  options: { plugins: { legend: { position: 'bottom' } } }
});
</script>
</body>
</html>
<?php

}?>