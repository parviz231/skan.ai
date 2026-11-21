<?php
session_start();
require_once("header.php");
require_once("config2.php");
if ($conn->connect_error) die("Ошибка подключения: " . $conn->connect_error);

if(!isset($_POST['ot'])){
    $sql = "
       WITH ranked AS (
        SELECT 
            CONCAT(u.grade, u.class) AS class_name,
            ROUND(SUM(v.visit = 'visited') / COUNT(v.visit) * 100, 2) AS attendance,
            ROUND(AVG(g.grade_value), 2) AS avg_grade,
            ROUND(((SUM(v.visit = 'visited') / COUNT(v.visit) * 100) + (AVG(g.grade_value) * 10)) / 2, 2) AS rating,
            ROW_NUMBER() OVER (ORDER BY ((SUM(v.visit = 'visited') / COUNT(v.visit) * 100) + (AVG(g.grade_value) * 10)) / 2 DESC) AS rank_num
        FROM users u
        LEFT JOIN visits v ON u.id = v.user_id
        LEFT JOIN grades g ON u.id = g.user_id
        GROUP BY class_name
    )
    SELECT * FROM ranked
    WHERE rank_num BETWEEN 1 AND 10
    ORDER BY rank_num;
    ";
} else {
    $ot = (int)$_POST['ot'];
    $do = (int)$_POST['do'];
    $sql = "
       WITH ranked AS (
        SELECT 
            CONCAT(u.grade, u.class) AS class_name,
            ROUND(SUM(v.visit = 'visited') / COUNT(v.visit) * 100, 2) AS attendance,
            ROUND(AVG(g.grade_value), 2) AS avg_grade,
            ROUND(((SUM(v.visit = 'visited') / COUNT(v.visit) * 100) + (AVG(g.grade_value) * 10)) / 2, 2) AS rating,
            ROW_NUMBER() OVER (ORDER BY ((SUM(v.visit = 'visited') / COUNT(v.visit) * 100) + (AVG(g.grade_value) * 10)) / 2 DESC) AS rank_num
        FROM users u
        LEFT JOIN visits v ON u.id = v.user_id
        LEFT JOIN grades g ON u.id = g.user_id
        GROUP BY class_name
    )
    SELECT * FROM ranked
    WHERE rank_num BETWEEN $ot AND $do
    ORDER BY rank_num;
    ";
}

$result = $conn->query($sql);
date_default_timezone_set("Asia/Dushanbe");
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8" />
  <title>🏫 Рейтинг классов по успеваемости</title>
  <style>


    h1 {
      text-align: center;
      color: #2C4361;
      margin: 40px 0 25px;
      font-size: 40px;
    }

    form {
      text-align: center;
      margin-bottom: 25px;
    }

    input[type=number] {
      padding: 5px;
      margin: 5px;
      width: 80px;
    }
    input[type=submit] {
      padding: 7px 20px;
      background: #2C4361;
      color: #fff;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }

    .content-row {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      flex-wrap: wrap;
      gap: 30px;
      margin: 40px;
    }

    .table-block {
      flex: 0 0 70%;
    }

    table {
      border-collapse: collapse;
      width: 100%;
      background: #fff;
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
      font-size: 18px;
    }
    th, td {
      border: 1px solid #ddd;
      padding: 15px;
      text-align: center;
    }
    th {
      background: #2C4361;
      color: white;
    }

    .stats {
      flex: 0 0 27%;
   
      border: 2px solid #2C4361;
      border-radius: 15px;
      padding: 25px;
      box-shadow: 0 0 15px rgba(44,67,97,0.2);
      text-align: center;
      font-size: 18px;
      
    }
    .stats h2 {
      margin-bottom: 20px;
      font-size: 22px;
    }
    .stats p {
      font-size: 18px;
      margin: 8px 0;
      color: #2C4361;
    }


    #chartContainer {
      width: 92%;
      background: #fff;
      border-radius: 15px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
      padding: 30px;
      margin: 50px 10px 10px 50px;
    }

    .good { background-color: #c8f7c5; }
    .warn { background-color: #ffe7a0; }
    .bad  { background-color: #ffb3b3; }

    .animate { opacity: 0; transform: translateY(50px); transition: all 1s ease; }
    .animate.show { opacity: 1; transform: translateY(0); }


    @media(max-width: 1100px) {
      .content-row { flex-direction: column; align-items: center; }
      .table-block, .stats { width: 90%; }
    }
  </style>
</head>
<body>
  <h1>Рейтинг успеваемости классов</h1>

  <form action="" method="POST">
    <span>От:</span>
    <input type="number" name="ot" value="<?= !empty($_POST['ot']) ? $_POST['ot'] : 1 ?>">
    <span>До:</span>
    <input type="number" name="do" value="<?= !empty($_POST['do']) ? $_POST['do'] : 10 ?>">
    <input type="submit" value="Показать">
  </form>

  <div class="content-row">
    <div class="table-block animate">
      <table>
        <tr>
          <th>Место</th>
          <th>Класс</th>
          <th>Посещаемость (%)</th>
          <th>Средняя оценка</th>
          <th>Рейтинг</th>
          <th>Статус</th>
        </tr>
        <?php
        $chartLabels = [];
        $chartRatings = [];
        $totalAttendance = $totalGrade = $totalRating = $count = 0;

        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $rank = $row['rank_num'];
                $class_name = $row["class_name"];
                $attendance = $row["attendance"] ?? 0;
                $avg_grade = $row["avg_grade"] ?? 0;
                $rating = $row["rating"] ?? 0;

                if ($rating < 20 || $attendance < 50) {
                    $status = "<span style='color:red;font-weight:bold'>Слабый класс</span>";
                    $row_class = "bad";
                } elseif ($rating < 40 || $attendance < 70) {
                    $status = "<span style='color:orange;font-weight:bold'>Нуждается в улучшении</span>";
                    $row_class = "warn";
                } else {
                    $status = "<span style='color:green;font-weight:bold'>Отличный класс</span>";
                    $row_class = "good";
                }

                echo "<tr class='$row_class'>
                        <td>$rank</td>
                        <td>$class_name</td>
                        <td>$attendance%</td>
                        <td>$avg_grade</td>
                        <td>$rating</td>
                        <td>$status</td>
                      </tr>";

                $chartLabels[] = $class_name;
                $chartRatings[] = $rating;
                $totalAttendance += $attendance;
                $totalGrade += $avg_grade;
                $totalRating += $rating;
                $count++;
            }
        } else {
            echo "<tr><td colspan='6'>Нет данных</td></tr>";
        }

        if ($count > 0) {
            $avgAttendance = round($totalAttendance / $count, 2);
            $avgGrade = round($totalGrade / $count, 2);
            $avgRating = round($totalRating / $count, 2);
        } else {
            $avgAttendance = $avgGrade = $avgRating = 0;
        }
        ?>
      </table>
    </div>


    <div class="stats animate">
      <h2>📊 Общая статистика</h2>
      <p><b>Классов:</b> <?= $count ?></p>
      <p><b>Средняя посещаемость:</b> <?= $avgAttendance ?>%</p>
      <p><b>Средняя оценка:</b> <?= $avgGrade ?></p>
      <p><b>Средний рейтинг:</b> <?= $avgRating ?></p>
    </div>
  </div>


  <div id="chartContainer" class="animate">
    <canvas id="classChart" height="120"></canvas>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    const ctx = document.getElementById('classChart').getContext('2d');
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [{
          label: 'Рейтинг классов',
          data: <?= json_encode($chartRatings) ?>,
          backgroundColor: 'rgba(52, 152, 219, 0.6)',
          borderColor: '#2C4361',
          borderWidth: 2
        }]
      },
      options: {
        scales: {
          y: { beginAtZero: true, max: 100 }
        },
        plugins: {
          legend: { display: true, position: 'top' }
        }
      }
    });


 
    document.addEventListener("DOMContentLoaded", () => {
      const elements = document.querySelectorAll('.animate');
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) entry.target.classList.add('show');
        });
      }, { threshold: 0.2 });
      elements.forEach(el => observer.observe(el));
    });
  </script>
</body>
</html>
<?php require_once("js.php"); ?>
