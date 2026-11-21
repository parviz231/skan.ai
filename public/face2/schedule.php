<?php
session_start();
require_once("header.php");
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$lang = $_SESSION['lang'] ?? 'ru';
require_once("1.php");
 require_once("config2.php");
if ($conn->connect_error) die("Ошибка подключения: " . $conn->connect_error);
$grade = isset($_GET['grade']) ? intval($_GET['grade']) : 10;
$class = isset($_GET['class']) ? $_GET['class'] : 'A';
$result = mysqli_query($conn,"
    SELECT day_of_week, lesson_number, subject, room
    FROM schedule
    WHERE grade = $grade AND class = '$class'
    ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), lesson_number
");
$schedule = [];
while($row = mysqli_fetch_assoc($result)) {
    $schedule[$row['day_of_week']][] = $row;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Расписание класса</title>
<style>
    
.col { flex:1; min-width:300px; background:#f9fcff; padding:15px; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.05);}
.card-title { font-size:18px; font-weight:bold; margin-bottom:10px; color:#1c3f95; border-bottom:2px solid #4a90e2; padding-bottom:5px;}
table { border-collapse:collapse; width:100%; margin:10px 0;}
th,td { border:1px solid #a2c4e1; padding:8px; text-align:center;}
th { background:#2C4361; color:white; }
tr:nth-child(even){ background:#f0f6ff;}
   .select-label {
    display: inline-block;
    font-weight: 600;           
    font-size: 16px;             
    margin-right: 10px;          
    color: #2c3e50;            
    vertical-align: middle;     
}
input,select[name="grade"] {
    padding: 6px 12px;
    font-size: 15px;
    border: 1px solid #2980b9;
    border-radius: 5px;
    background-color: #f0f4f8;
    color: #2c3e50;
    cursor: pointer;
    transition: all 0.3s ease;
}
button {
    background: #2C4361;
    color: white;
    border: none;
    cursor: pointer;
    transition: 0.3s;
}
button:hover {
    background: #2c436191;
    transform: scale(1.05);
}
input,select[name="grade"]:focus {
    border-color: #3498db;
    outline: none;
    box-shadow: 0 0 5px rgba(41, 128, 185, 0.5);
}
   .select-label {
    display: inline-block;
    font-weight: 600;           
    font-size: 20px;             
    margin-right: 10px;          
    color: #2c3e50;            
    vertical-align: middle;  

}
input,select[name="grade"],select[name="class"] {
    padding: 6px 12px;
    font-size: 15px;
    margin-left: 5px;
     margin-right: 5px;
    border: 1px solid #2980b9;
    border-radius: 5px;
    background-color: #f0f4f8;
    color: #2c3e50;
    cursor: pointer;
    transition: all 0.3s ease;
}

input,select[name="grade"],select[name="class"]:focus {
    border-color: #3498db;
    outline: none;
    box-shadow: 0 0 5px rgba(41, 128, 185, 0.5);
}
 h1 {
    text-align: center;
    margin-bottom: 30px;
  }
</style>
</head>
<body>
  <div style="margin-left:100px; margin-right:100px; " class="animate">
   <br><br><h1>Расписание класса <?= htmlspecialchars($grade . $class) ?></h1>
<form method="get" style="display: flex; justify-content: center; margin: 10px;">
    <label for="" class="select-label">Класс: </label> 
    <select name="grade">
        <?php for($g=5;$g<=11;$g++): ?>
            <option value="<?= $g ?>" <?= $g==$grade?'selected':'' ?>><?= $g ?></option>
        <?php endfor; ?>
    </select>
    <select name="class">
        <?php foreach(['A','B','C','D','G'] as $c): ?>
            <option value="<?= $c ?>" <?= $c==$class?'selected':'' ?>><?= $c ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit">Показать</button>
</form>
<div class="row">
    <?php foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $day): ?>
    <div class="col">
        <?php
        $d = '';
        if($day == 'Monday')$d = 'Понедельник';
        if($day == 'Tuesday')$d = 'Вторник';
        if($day == 'Wednesday')$d = 'Среда';
        if($day == 'Thursday')$d = 'Четверг';
        if($day == 'Friday')$d = 'Пятница';
        if($day == 'Saturday')$d = 'Суббота';
        ?>
    <div class="card-title"><?= $d ?></div>
    <table>
      <tr><th>№</th><th>Предмет</th><th>Каб.</th></tr>
      <?php if(isset($schedule[$day])): ?>
          <?php foreach($schedule[$day] as $lesson): ?>
              <tr>
                  <td><?= $lesson['lesson_number'] ?></td>
                  <td><?= htmlspecialchars($lesson['subject']) ?></td>
                  <td><?= htmlspecialchars($lesson['room']) ?></td>
              </tr>
          <?php endforeach; ?>
      <?php else: ?>
          <tr><td colspan="3">Нет уроков</td></tr>
      <?php endif; ?>
    </table>
  </div>
<?php endforeach; ?>
</div>

</div>
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