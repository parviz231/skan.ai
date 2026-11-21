<?php
session_start();
require_once("header.php");
 require_once("config2.php");
 if(!isset($_POST['ot'])){
$sql = "
   WITH ranked AS (
    SELECT 
        u.id,
        u.surname,
        u.name,
        u.grade,
        u.class,
        ROUND(SUM(v.visit = 'visited') / COUNT(v.visit) * 100, 2) AS attendance,
        ROUND(AVG(g.grade_value), 2) AS avg_grade,
        ROUND( ( (SUM(v.visit = 'visited') / COUNT(v.visit) * 100) + (AVG(g.grade_value) * 10) ) / 2, 2) AS rating,
        ROW_NUMBER() OVER (ORDER BY ((SUM(v.visit = 'visited') / COUNT(v.visit) * 100) + (AVG(g.grade_value) * 10)) / 2 DESC) AS rank_num
    FROM users u
    LEFT JOIN visits v ON u.id = v.user_id
    LEFT JOIN grades g ON u.id = g.user_id
    GROUP BY u.id
)
SELECT *
FROM ranked
WHERE rank_num BETWEEN 1 AND 10
ORDER BY rank_num;
";
}
else{
  $ot = $_POST['ot'];
  $do = $_POST['do'];
  $sql = "
 WITH ranked AS (
    SELECT 
        u.id,
        u.surname,
        u.name,
        u.grade,
        u.class,
        ROUND(SUM(v.visit = 'visited') / COUNT(v.visit) * 100, 2) AS attendance,
        ROUND(AVG(g.grade_value), 2) AS avg_grade,
        ROUND( ( (SUM(v.visit = 'visited') / COUNT(v.visit) * 100) + (AVG(g.grade_value) * 10) ) / 2, 2) AS rating,
        ROW_NUMBER() OVER (ORDER BY ((SUM(v.visit = 'visited') / COUNT(v.visit) * 100) + (AVG(g.grade_value) * 10)) / 2 DESC) AS rank_num
    FROM users u
    LEFT JOIN visits v ON u.id = v.user_id
    LEFT JOIN grades g ON u.id = g.user_id
    GROUP BY u.id
)
SELECT *
FROM ranked
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
  <title>Автоматизация школы - Учёт посещаемости</title>
 <link rel="stylesheet" href="css/style_rating.css">
</head>
<body>

   <div class="content animate" style="margin-left:100px; margin-right: 100px;margin-top:30px;">
    <h1 style="margin-top:0px;">🏆 Рейтинги учеников по успеваемости</h1>
     <form action="rating_student.php" method = "POST">
    <span>От</span>
   <?php
   if(!empty($_POST['ot'])){
    ?>
    <input type="number" value="<?=$_POST['ot']?>" name="ot">
    <?php
   }
   else{
   ?>
    <input type="number" name="ot" value="1">
   <?php }
   ?>
   <span>До</span>
   <?php
   if(!empty($_POST['do'])){
    ?>
    <input type="number" value="<?=$_POST['do']?>" name="do">
    <?php
   }
   else{
   ?>
    <input type="number" name="do" value="10">
   <?php }
   ?>

   </select>  
   <input type="submit">
   </form>
  <table class="table">
    <tr class="tr" >
      <th class="th" style="background-color: #2C4361;">Место</th>
      <th class="th" style="background-color: #2C4361;">ФИО</th>
      <th class="th" style="background-color: #2C4361;">Класс</th>
      <th class="th" style="background-color: #2C4361;">Успеваемост</th>
    </tr>
    <?php
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
          $rank = $row['rank_num'];
            $fio = $row["surname"]." ".$row["name"];
            $class = $row["grade"].$row["class"];
            $att = $row["rating"];
            $badge = "";
            if ($rank == 1) $badge = "<span class='badge gold'>🥇</span>";
            elseif ($rank == 2) $badge = "<span class='badge silver'>🥈</span>";
            elseif ($rank == 3) $badge = "<span class='badge bronze'>🥉</span>";
            else $badge = "<span class='badge good'>✔</span>";

            echo "<tr class='tr'>
                    <td class='td'>$rank $badge</td>
                    <td class='td'>$fio</td>
                    <td class='td'>$class</td>";
            if(!isset($att)) echo "  <td class='td'>0</td></tr>";
            else echo "<td class='td'>{$att}</td></tr>";
        }
      }
       else {
        echo "<tr class='tr'><td colspan='4' class='td'>Нет данных</td></tr>";
    }
    ?>
  </table>
  </div>
 
</body>
</html>
<style>
  .animate {
  opacity: 0;
  transform: translateY(50px);
  transition: all 1s ease;
}


.animate.show {
  opacity: 1;
  transform: translateY(0);
}

</style>
 <script>
  document.addEventListener("DOMContentLoaded", () => {
    const elements = document.querySelectorAll('.animate');

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('show');  
        }
      });
    }, { threshold: 0.2 }); 
    elements.forEach(el => observer.observe(el));
  });
</script>
<?php require_once("js.php"); ?>