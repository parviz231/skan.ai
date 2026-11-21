<?php
session_start();
require_once("header.php");
  require_once("config2.php");
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
?>
<style>

h1 {
    text-align: center;
    margin-bottom: 30px;
    color: #2c3e50;
}

form {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    align-items: center;
    gap: 15px;
    margin-bottom: 30px;
}

form span {
    font-weight: 500;
    color: #34495e;
}

select, input[type="date"], button {
    padding: 8px 12px;
    font-size: 14px;
    border-radius: 6px;
    border: 1px solid #ccc;
}

button {
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: #fff;
    font-weight: 500;
    cursor: pointer;
    border: none;
    transition: 0.3s;
}

button:hover {
    background: linear-gradient(135deg, #2980b9, #2471a3);
}

table {
    width: 100%;
    border-collapse: collapse;
    background-color: #fff;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}

table th, table td {
    padding: 12px 10px;
    border: 1px solid #ddd;
    text-align: left;
}

table th {
    background-color: #34495e;
    color: #fff;
    font-weight: 600;
}

table tr:nth-child(even) {
    background-color: #f9f9f9;
}

table tr:hover {
    background-color: #e8f0fe;
}

input[type="time"] {
    width: 120px;
    padding: 4px;
}

.extra-row {
    display: none;
}

.center {
    text-align: center;
    margin: 20px;
}

#result {
    margin-top: 20px;
    padding: 15px;
    background-color: #ecf0f1;
    border-left: 5px solid #3498db;
    white-space: pre-line;
    font-family: monospace;
}


#chartContainer {
    width: 700px;
    max-width: 100%;
    margin: 30px auto;
   
}
</style>
 <style>
    .single_slider::after {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(200, 200, 200, 0.4); 
    z-index: 0;
}
.container{
    z-index: 2;
}

    h1 {
      margin: 40px 0;
      font-size: 42px;
      text-align: center;
    }

    .section {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 80px 10%;
      gap: 60px;
      background: #fff;
      margin-bottom: 40px;
      box-shadow: 0 6px 16px rgba(0,0,0,0.1);
    }

    
    .section:nth-child(even) {
      flex-direction: row-reverse;
   
    }

    .text {
      flex: 1;
    }

    .text h3 {
      font-size: 40px;
      margin-bottom: 20px;
      color: #222;
    }

    .text p {
      font-size: 20px;
      line-height: 1.7;
      color: #444;
    }

 
    .image {
      flex: 1;
      display: flex;
      justify-content: center;
    }

    .image img {
      max-width: 70%;
      border-radius: 20px;
    }

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
<div class="slider_area" style="max-height:600px;">
    <div class="slider_active owl-carousel"style="max-height:600px;">
        <div class="single_slider slider_bg_1 d-flex align-items-center"style="max-height:600px; background-image: url(img/img1.jpg);">
            <div class="container"style="max-height:600px;">
                <div class="row">
                    <div class="col-xl-12">
                        <div class="slider_text">
                             <p style="color:$heading-color; font-size:25px;">РИТМ - Распознавание и Интеллектуальный Технологический Мониторинг</p>
                            
                            <h3 style="color: $heading-color;
             font-family: $font3;
             font-size: 50px;
             font-weight: 300;
             line-height: 1.30;
             margin-bottom: 40px;">Следи за посещаемостью,<br> успеваемостью и статистикой<br> учеников в реальном времени.</h3>
                            <a href="statistics.php" class="boxed-btn3">Начать</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="single_slider  slider_bg_2 d-flex align-items-center slider_bg_2"style="max-height:600px; background-image: url(img/img3.jpg);">
            <div class="container"style="max-height:600px; ">
                <div class="row">
                    <div class="col-xl-12">
                        <div class="slider_text">
                             <p style="color:$heading-color; font-size:25px;">РИТМ - Распознавание и Интеллектуальный Технологический Мониторинг</p>
                            
                            <h3 style="color: $heading-color;
             font-family: $font3;
             font-size: 50px;
             font-weight: 300;
             line-height: 1.30;
             margin-bottom: 40px;">Присутствует интуитивный интерфейс,<br> графики и аналитика<br> для каждого класса.</h3>
                            <a href="statistics1.php" class="boxed-btn3">Начать </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="single_slider slider_bg_3 d-flex align-items-center slider_bg_1"style="max-height:600px;  background-image: url(img/img6.jpg);">
            <div class="container"style="max-height:600px;">
                <div class="row">
                    <div class="col-xl-12">
                        <div class="slider_text" >
                             <p style="color:$heading-color; font-size:25px;">РИТМ - Распознавание и Интеллектуальный Технологический Мониторинг</p>
                            <h3 style="color: $heading-color;
             font-family: $font3;
             font-size: 50px;
             font-weight: 300;
             line-height: 1.30;
             margin-bottom: 40px;">Все данные — аккуратно в одном месте</h3>
                            <a href="profile.php" class="boxed-btn3">Начать</a>
                            
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="single_slider d-flex slider_bg_4 align-items-center slider_bg_1"style="max-height:600px; background-image: url(img/9.png);">
            <div class="container">
                <div class="row">
                    <div class="col-xl-12">
                        <div class="slider_text" >
                             <p style="color:$heading-color; font-size:25px;">РИТМ - Распознавание и Интеллектуальный Технологический Мониторинг</p>
                            <h3 style="color: $heading-color;
             font-family: $font3;
             font-size: 50px;
             font-weight: 300;
             line-height: 1.30;
             margin-bottom: 40px;">Распазнавания лиц <br>и посещаемость с<br> помощью ИИ</h3>
                            <a href="#attendanceTable" class="boxed-btn3">Начать</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="single_slider d-flex slider_bg_5 align-items-center slider_bg_1"style="max-height:600px;background-image: url(img/2.jpg);">
            <div class="container">
                <div class="row">
                    <div class="col-xl-12">
                        <div class="slider_text">
                            <p style="color:$heading-color; font-size:25px;">РИТМ - Распознавание и Интеллектуальный Технологический Мониторинг</p>
                            <h3 style="color: $heading-color;
             font-family: $font3;
             font-size: 50px;
             font-weight: 300;
             line-height: 1.30;
             margin-bottom: 40px;">Анализ успеваемости и <br>посещаемости — прямо<br> на главной странице.</h3>
                            <a href="#attendanceTable" class="boxed-btn3">Начать</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once("js.php"); ?>

<script>
    $(document).ready(function(){
        $(".slider_active").owlCarousel({
            items: 1,
            loop: true,
            autoplay: true,
            autoplayTimeout: 3000,
            autoplayHoverPause: true,
            smartSpeed: 1000
        });
    });
</script>
  
 <div style="margin: 50px;">


  <h1 class="animate">Каковы преимущества распознавания лиц?</h1>

  <div class="section animate">
    <div class="text">
     
      <h3>Повышенная безопасность</h3>
      <p>
        Технология распознавания лиц использует специальные алгоритмы и математические шаблоны 
        для обработки биометрических данных. Это один из самых безопасных методов идентификации 
        для снижения риска несанкционированного доступа.
      </p>
    </div>
    <div class="image">
      <img src="img/antivirus.png" alt="Повышенная безопасность">
    </div>
  </div>

  <div class="section animate">
    <div class="text ">
      
      <h3>Повышенная точность</h3>
      <p>
        Распознавание лица — это более точный способ идентификации человека, чем использование 
        номера телефона или PIN-кода. Алгоритмы обеспечивают практически идеальную степень точности.
      </p>
    </div>
    <div class="image">
      <img src="img/gears.png" alt="Повышенная точность">
    </div>
  </div>

  <div class="section" class="animate">
    <div class="text ">
     
      <h3>Простота использования</h3>
      <p>
        Обнаружение и распознавание лица занимает меньше секунды и является бесконтактным, 
        в отличие от паролей или отпечатков пальцев. Технология легко разворачивается на различных устройствах.
      </p>
    </div>
    <div class="image">
      <img src="img/veracity.png" alt="Простота использования">
    </div>
  </div>


 <?php 

   $in_time = 0;
   $late = 0;
    if(isset($_POST['grade'])){
      $grade = $_POST['grade'];
      $date = $_POST['date'];
      $class = $grade[strlen($grade) - 1];
    }
    else{
      $grade = "Все";
      $date = date('Y-m-d');
      
    }
    if($grade == 'Все'){
     $res = $conn->query("
       SELECT 
    o.visit_id AS id,
    o.user_id,
    o.visit,
    o.time,
    u.id AS user_id_actual,
    u.surname,
    u.name AS user_name,
    u.patronymic,
    u.gender,
    u.birth,
    u.grade,
    u.class
FROM visits o
LEFT JOIN users u ON o.user_id = u.id
WHERE CAST(o.time AS DATE) = '$date' ORDER BY o.time DESC;
    ");
    }
    else{
   $res = $conn->query("
       SELECT 
    o.visit_id AS id,
    o.user_id,
    o.visit,
    o.time,
    u.id AS user_id_actual,
    u.surname,
    u.name AS user_name,
    u.patronymic,
    u.gender,
    u.birth,
    u.grade,
    u.class
FROM visits o
LEFT JOIN users u ON o.user_id = u.id
WHERE CAST(o.time AS DATE) = '$date'
  AND u.grade = '$grade'
  AND u.class = '$class'
ORDER BY o.time DESC;

    ");
}
  ?>  
<h1 class="animate">Учёт посещаемости учеников</h1>
  
   <form action="index.php" method = "POST" class="animate">
    <span>Выбрать класс:</span>
   <select name = "grade">
   <?php
   if(!empty($_POST['grade'])){
    ?>
    <option value="Все" selected>Все</option>
    <option value="<?=$_POST['grade']?>" selected><?=$_POST['grade']?></option>
    <?php
   }
   else{
   ?>
   <option value="Все" selected>Все</option>
   <?php }
   $a = ['А','Б','В'];
   for($i = 5;$i <= 11;$i++){
    for($j = 0; $j < 3;$j++){
    ?>
    <option value="<?=$i.$a[$j]?>"><?=$i.$a[$j]?></option>
    <?php
    }
   }
   ?>

   </select>  
   <span>Выбрать дату:</span>
   <?php
   if(!empty($_POST['date'])){
    ?>
    <input type="date" name = "date" value="<?=$_POST['date']?>">
    <?php
   }
   else{
    $d = date('Y-m-d');
   ?>
   <input type="date" name = "date" value="<?=$d?>">
   <?php }
   ?>

   </select> 
   <input type = "submit"style="
    
      padding: 10px 25px;
      font-size: 15px;
      font-weight: 100;
      color: white;
      background: linear-gradient(135deg, #3498db, #2980b9);
      border: none;
      border-radius: 40px;
      cursor: pointer;
      box-shadow: 0 6px 15px rgba(41, 128, 185, 0.4);
      transition: background 0.3s ease, transform 0.2s ease;"></form>
   
  <table id="attendanceTable" aria-label="Таблица учёта посещаемости" class="animate">
    <thead>
      <tr>
        <th>Имя ученика</th>
        <th>Класс</th>
        <th>Время прихода</th>
        <th>Присутствует</th>
        <th>Отсутствует</th>
        <th>Опоздал</th>
      </tr>
    </thead>
    <tbody>
      <?php
    $limit = 30;
     for ($i = 1; $i <= $res->num_rows; $i++) {
        $row = $res->fetch_assoc();
          $time = date('H:i:s', strtotime($row['time']));

        $is_late = 0;
        if ($time > '08:00:00') {
          $late++;
          $is_late = 1;
        }else {
          $in_time++;
        }
        $check_1 = 'checked';
        $check_2 = '';
        if ($is_late == 1) {
          $check_1 = '';
          $check_2 = 'checked';
        }
        $user_name = $row['surname'].' '.$row['user_name'].' '.$row['patronymic'];
        if ($row['user_id'] == 0) {
          $user_name = 'Unknown';
        }
        $extraClass = ($i > $limit) ? "class='extra-row' style=\"display:none;\"" : "";
        echo '<tr '.$extraClass.'>
        <td>'.$user_name.'</td>
        <td>'.$row['grade'].$row['class'].'</td>
        <td><input type="time" name="arrival_'.$i.'" value="'.$time.'" /></td>
        <td><input type="radio" name="status_'.$i.'" value="Присутствует"'.$check_1.'></td>
        <td><input type="radio" name="status_'.$i.'" value="Отсутствует"></td>
        <td><input type="radio" name="status_'.$i.'" value="Опоздал"'.$check_2.'></td>
        </tr>';
        }
      ?>
    </tbody>
  </table>
  <div style="text-align:center; margin-top:20px;" class="animate">
    <button type="button" id="showAllBtn">Показать всех</button>
    <button type="button" id="collapseBtn" style="display:none;">Свернуть обратно</button>
  </div>
  
  <button onclick="submitAttendance()" class="animate">Сохранить посещаемость</button>

  <div id="result" role="region" aria-live="polite" class="animate"></div>

  <div id="chartContainer" class="animate">
    <canvas id="attendanceChart"></canvas>
  </div><br>
  <h1 class="animate">🏆 ТОП-10 лучших учеников по успеваемости</h1>
  <table class="table" class="animate">
    <tr class="tr">
      <th class="th">Место</th>
      <th class="th">ФИО</th>
      <th class="th">Класс</th>
      <th class="th">Успеваемост (%)</th>
    </tr>
    <?php
    if ($result && $result->num_rows > 0) {
        $rank = 1;
        while($row = $result->fetch_assoc()) {
            $fio = $row["surname"]." ".$row["name"];
            $class = $row["grade"].$row["class"];
            $att = $row["attendance"];
            $badge = "";
            if ($rank == 1) $badge = "<span class='badge gold'>🥇</span>";
            elseif ($rank == 2) $badge = "<span class='badge silver'>🥈</span>";
            elseif ($rank == 3) $badge = "<span class='badge bronze'>🥉</span>";
            else $badge = "<span class='badge good'>✔</span>";

            echo "<tr class='tr'>
                    <td class='td'>$rank $badge</td>
                    <td class='td'>$fio</td>
                    <td class='td'>$class</td>
                    <td class='td'>{$att}%</td>
                  </tr>";
            $rank++;
        }
    } else {
        echo "<tr class='tr'><td colspan='4' class='td'>Нет данных</td></tr>";
    }
    ?>
  </table>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
$(document).ready(function () {
    let $rows = $("#attendanceTable tbody tr");
    $rows.slice(30).hide();

    $("#showAllBtn").click(function () {
        if ($(this).text() === "Показать всех") {
            $rows.show();
            $(this).text("Скрыть лишние");
        } else {
            $rows.slice(30).hide();
            $(this).text("Показать всех");
        }
    });
});
</script>

  <script>
    const ctx = document.getElementById('attendanceChart').getContext('2d');
    let attendanceChart;

    const limit = 30;
    const showAllBtn = document.getElementById("showAllBtn");
    const collapseBtn = document.getElementById("collapseBtn");

    showAllBtn.addEventListener("click", function() {
      document.querySelectorAll(".extra-row").forEach(tr => tr.style.display = "");
      showAllBtn.style.display = "none";
      collapseBtn.style.display = "inline-block";
    });

    collapseBtn.addEventListener("click", function() {
      let i = 0;
      document.querySelectorAll("#attendanceTable tbody tr").forEach(tr => {
        i++;
        if (i > limit) tr.style.display = "none";
      });
      collapseBtn.style.display = "none";
      showAllBtn.style.display = "inline-block";
    });

    function submitAttendance() {
      const table = document.getElementById('attendanceTable');
      const rows = table.tBodies[0].rows;
      let report = 'Отчёт по посещаемости:\n\n';
      let counts = {
        'Присутствует': 0,
        'Отсутствует': 0,
        'Опоздал': 0
      };

      for (let i = 0; i < rows.length; i++) {
        const studentName = rows[i].cells[0].innerText;
        const studentClass = rows[i].cells[1].innerText;
        const arrivalTime = rows[i].querySelector('input[type="time"]').value;
        const radios = rows[i].querySelectorAll('input[type="radio"]');
        let status = '';
        radios.forEach(radio => {
          if (radio.checked) status = radio.value;
        });
        report += `${studentName} (класс ${studentClass}), время прихода: ${arrivalTime}, статус: ${status}\n`;
        if (counts[status] !== undefined) {
          counts[status]++;
        }
      }

      document.getElementById('result').innerText = report;

      updateChart(counts);
    }

    function updateChart(counts) {
      const data = {
        labels: ['Присутствует', 'Отсутствует', 'Опоздал'],
        datasets: [{
          label: 'Количество учеников',
          data: [counts['Присутствует'], counts['Отсутствует'], counts['Опоздал']],
          backgroundColor: [
            'rgba(46, 204, 113, 0.7)', 
            'rgba(231, 76, 60, 0.7)', 
            'rgba(241, 196, 15, 0.7)'  
          ],
          borderColor: [
            'rgba(46, 204, 113, 1)',
            'rgba(231, 76, 60, 1)',
            'rgba(241, 196, 15, 1)'
          ],
          borderWidth: 1,
          hoverOffset: 20
        }]
      };

      const options = {
        responsive: true,
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              font: {
                size: 14,
                weight: '600'
              }
            }
          },
          tooltip: {
            enabled: true
          }
        }
      };

      if (attendanceChart) {
        attendanceChart.data = data;
        attendanceChart.options = options;
        attendanceChart.update();
      } else {
        attendanceChart = new Chart(ctx, {
          type: 'doughnut',
          data: data,
          options: options
        });
      }
    }
    updateChart({ 'Присутствует': <?php echo $in_time; ?>, 'Отсутствует': 0, 'Опоздал': <?php echo $late; ?> });
  </script>
 <div class="popular_program_area animate" style="margin-bottom:20px;">
  <div class="container">
    <div class="row">
      <div class="col-lg-12">
        <div class="section_title text-center">
          <h3>Достижения учеников</h3>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-lg-12">
        <nav class="custom_tabs text-center">
          <div class="nav" id="nav-tab" role="tablist">
            <a class="nav-item nav-link active" id="nav-home-tab" data-toggle="tab" href="#nav-home"
              role="tab" aria-controls="nav-home" aria-selected="true">Школьные олимпиады</a>
            <a class="nav-item nav-link" id="nav-profile-tab" data-toggle="tab" href="#nav-profile"
              role="tab" aria-controls="nav-profile" aria-selected="false">Международные олимпиады</a>
            <a class="nav-item nav-link" id="nav-contact-tab" data-toggle="tab" href="#nav-contact"
              role="tab" aria-controls="nav-contact" aria-selected="false">Спортивные соревнования</a>
          </div>
        </nav>
      </div>
    </div>

    <div class="tab-content" id="nav-tabContent">
      
      <div class="tab-pane fade show active" id="nav-home" role="tabpanel" aria-labelledby="nav-home-tab">
        <div class="row">
          <div class="col-lg-4 col-md-6">
            <div class="single__program">
              <div class="program_thumb">
                <img src="img/d3.jpg" alt="" height="280">
              </div>
              <div class="program__content">
                <span>Школьные олимпиады</span>
                <h4>Наши победы</h4>
                <p>В школьных олимпиадах активно приняли участие учащиеся разных классов. Многие показали отличные
                  знания по математике, физике, литературе и информатике.</p>
              </div>
            </div>
          </div>
          <div class="col-lg-4 col-md-6">
            <div class="single__program">
              <div class="program_thumb">
                <img src="img/d1.jpg" alt="" height="280">
              </div>
              <div class="program__content">
                <span>Школьные олимпиады</span>
                <h4>Призовые места</h4>
                <p>Благодаря усердию и настойчивости наши ученики заняли призовые места и доказали высокий уровень
                  подготовки.</p>
              </div>
            </div>
          </div>
          <div class="col-lg-4 col-md-6">
            <div class="single__program">
              <div class="program_thumb">
                <img src="img/d2.jpg" alt="" height="280">
              </div>
              <div class="program__content">
                <span>Школьные олимпиады</span>
                <h4>Гордость школы</h4>
                <p>Результаты олимпиад вдохновляют других учеников и становятся примером стремления к знаниям.</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      
      <div class="tab-pane fade" id="nav-profile" role="tabpanel" aria-labelledby="nav-profile-tab">
        <div class="row">
          <div class="col-lg-4 col-md-6">
            <div class="single__program">
              <div class="program_thumb">
                <img src="img/d4.jpg" alt="" height="280">
              </div>
              <div class="program__content">
                <span>Международные олимпиады</span>
                <h4>Успехи за рубежом</h4>
                <p>Наши школьники достойно представили страну на олимпиадах по естественным и гуманитарным наукам.</p>
              </div>
            </div>
          </div>
          <div class="col-lg-4 col-md-6">
            <div class="single__program">
              <div class="program_thumb">
                <img src="img/d3.jpg" alt="" height="280">
              </div>
              <div class="program__content">
                <span>Международные олимпиады</span>
                <h4>Победители</h4>
                <p>Их упорный труд и стремление к знаниям позволили завоевать медали и грамоты на мировом уровне.</p>
              </div>
            </div>
          </div>
          <div class="col-lg-4 col-md-6">
            <div class="single__program">
              <div class="program_thumb">
                <img src="img/d2.jpg" alt="" height="280">
              </div>
              <div class="program__content">
                <span>Международные олимпиады</span>
                <h4>Признание</h4>
                <p>Эти достижения стали не только гордостью школы, но и примером для будущих поколений учеников.</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      
      <div class="tab-pane fade" id="nav-contact" role="tabpanel" aria-labelledby="nav-contact-tab">
        <div class="row">
          <div class="col-lg-4 col-md-6">
            <div class="single__program">
              <div class="program_thumb">
                <img src="img/d8.jpg" alt=""  height="280"> 
              </div>
              <div class="program__content">
                <span>Спортивные соревнования</span>
                <h4>Командные игры</h4>
                <p>Наши школьные команды показали мастерство в футболе и баскетболе, заслужив уважение соперников.</p>
              </div>
            </div>
          </div>
          <div class="col-lg-4 col-md-6">
            <div class="single__program">
              <div class="program_thumb">
                <img src="img/d7.jpg" alt=""  height="280">
              </div>
              <div class="program__content">
                <span>Спортивные соревнования</span>
                <h4>Лёгкая атлетика</h4>
                <p>Ученики завоевали медали в беге, прыжках и эстафетах, проявив силу духа и выносливость.</p>
              </div>
            </div>
          </div>
          <div class="col-lg-4 col-md-6">
            <div class="single__program">
              <div class="program_thumb">
                <img src="img/d5.jpg" alt="" height="280">
              </div>
              <div class="program__content">
                <span>Спортивные соревнования</span>
                <h4>Гордость школы</h4>
                <p>Эти победы укрепили командный дух и стали символом активной школьной жизни.</p>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

     <div class="recent_event_area animate">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-md-10">
                    <div class="section_title text-center mb-70">
                        <h3 class="mb-45">Наши кружки</h3>
                        <p>В наших кружках всегда интересно и увлекательно!</p>
                    </div>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="single_event d-flex align-items-center">
                        <div class="date text-center">
                            <p>Среда и пятница<br> <br> 13:30-15:30</p>
                        </div>
                        <div class="event_info">
                            <a href="event_details.html">
                                <h4>Кружок по шахмату</h4>
                             </a>
                            <p><span> Учитель: Норматов М</span> </p>
                            <button class="btn btn-primary"><a href="chess.php" style="color: white;">Узнать больше</a></button>
                        </div>
                    </div>
                    <div class="single_event d-flex align-items-center">
                        <div class="date text-center">
                            <p>Среда и пятница<br> <br> 13:30-15:30</p>
                        </div>
                        <div class="event_info">
                            <a href="event_details.html">
                                <h4>Кружок по программированию</h4>
                             </a>
                            <p><span> Учитель: Ачилов Х</span> </p>
                            <button class="btn btn-primary"><a href="prog.php" style="color: white;">Узнать больше</a></button>
                        </div>
                    </div>
                    <div class="single_event d-flex align-items-center">
                        <div class="date text-center">
                            <p>Среда и пятница<br> <br> 13:30-15:30</p>
                        </div>
                        <div class="event_info">
                            <a href="event_details.html">
                                <h4>Кружок по химии</h4>
                             </a>
                            <p><span> Учитель: Шафиев Н</span> </p>
                            <button class="btn btn-primary"><a href="chemistry.php" style="color: white;">Узнать больше</a></button>
                        </div>
                    </div>
                    <div class="single_event d-flex align-items-center">
                        <div class="date text-center">
                            <p>Среда и пятница<br> <br> 13:30-15:30</p>
                        </div>
                        <div class="event_info">
                            <a href="event_details.html">
                                <h4>Кружок по экономике</h4>
                             </a>
                            <p><span> Учитель: Юнусова Ш</span> </p>
                            <button class="btn btn-primary"><a href="economy.php" style="color: white;">Узнать больше</a></button>
                        </div>
                    </div>
                    </div>
                    
                </div>
            </div>
        </div>
        
    </div>
  </div>
  
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
