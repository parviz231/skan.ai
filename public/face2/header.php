<?php
date_default_timezone_set("Asia/Dushanbe");
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$lang = $_SESSION['lang'] ?? 'ru';
require_once("1.php");
$year_start = 2026;
$year_end = 2027;
$school_year = "$year_start / $year_end";
$now = date("H:i");
$current = "Вне уроков";


$schedule1 = [
    ["08:00", "08:45", "1 урок"],
    ["08:45", "08:50", "Перемена"],
    ["08:50", "09:35", "2 урок"],
    ["09:35", "09:40", "Перемена"],
    ["09:40", "10:25", "3 урок"],
    ["10:25", "10:35", "Перемена"],
    ["10:35", "11:20", "4 урок"],
    ["11:20", "11:25", "Перемена"],
    ["11:25", "12:10", "5 урок"],
    ["12:10", "12:15", "Перемена"],
    ["12:15", "13:00", "6 урок"]
];
$schedule2 = [
    ["13:05", "13:50", "1 урок"],
    ["13:50", "13:55", "Перемена"],
    ["13:55", "14:40", "2 урок"],
    ["14:40", "14:45", "Перемена"],
    ["14:45", "15:30", "3 урок"],
    ["15:30", "15:45", "Перемена"],
    ["15:45", "16:30", "4 урок"],
    ["16:35", "16:40", "Перемена"],
    ["16:45", "17:30", "5 урок"],
    ["17:30", "17:35", "Перемена"],
    ["17:35", "18:20", "6 урок"]
];

foreach ($schedule1 as $item) {
    list($start, $end, $label) = $item;
    if ($now >= $start && $now <= $end) {
        $current = $label;
        break;
    }
}
if ($current == "Вне уроков") {
    foreach ($schedule2 as $item) {
        list($start, $end, $label) = $item;
        if ($now >= $start && $now <= $end) {
            $current = $label;
            break;
        }
    }
}
$shift = (date("H") < 13) ? "1" : "2";
if ($current == "Вне уроков") {
    $shift = "Уроки закончились";
}
$currentTime = date('H:i:s');
?>
<!doctype html>
<html class="no-js" lang="zxx">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>РИТМ</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/x-icon" href="img/logo-ritm.png">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/owl.carousel.min.css">
    <link rel="stylesheet" href="css/magnific-popup.css">
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link rel="stylesheet" href="css/themify-icons.css">
    <link rel="stylesheet" href="css/nice-select.css">
    <link rel="stylesheet" href="css/flaticon.css">
    <link rel="stylesheet" href="css/gijgo.css">
    <link rel="stylesheet" href="css/animate.css">
    <link rel="stylesheet" href="css/slicknav.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/ritm-polish.css">
    <style>
    .navbar-right img {
      width: 28px;
      height: 20px;
      margin-left: 10px;
      cursor: pointer;
      border: 1px solid #ddd;
   }</style>
</head>
<body>
    <header>
        <div class="header-area ">
            <div class="header-top_area">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-lg-12">
                            <div style = "color: white; "class="header_top_wrap d-flex justify-content-between align-items-center">
                                   <span><?= $texts[$lang]['time'] ?>: <span id="clock"><?=$currentTime?></span></span>
    <span><?= $texts[$lang]['now']." ".$texts[$lang]['text1']?>: <?=$current?></span>
    <span><?=$texts[$lang]['shift']?>: <?=$shift?></span>
    <span style="padding-right:13px;"><?=$texts[$lang]['year']?>: <?=$school_year?></span>                              
                                
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="sticky-header" class="main-header-area" style="z-index:1000000;">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="header_wrap d-flex justify-content-between align-items-center">
                                <div class="header_left">
                                    <div class="logo" style="display:flex;">
                                        <img src="img/logo-ritm.png" alt="" width="40" height="40">
                                        <a href="index.php" style = "font-size: 30px;">
                                            РИТМ
                                        </a>
                                    </div>
                                </div>
                                <div class="header_right d-flex align-items-center">
                                    <div class="main-menu  d-none d-lg-block">
                                        <nav>
                                            <ul id="navigation">
                                                <li><a  style="font-size:15px;" href="index.php"><?= $texts[$lang]['home'] ?></a></li>
                                               <li><a style="font-size:15px;" href="#"><?= $texts[$lang]['students'] ?><i class="ti-angle-down"></i></a>
                                                    <ul class="submenu" >
                                                        <li><a style="font-size:15px;"href="schedule.php"><?= $texts[$lang]['schedule'] ?></a></li>
                                                          <?php
                                                if(isset($_SESSION['id1'])){
                                                ?>
                                                        <li><a href="student_prediction.php" style="font-size:15px;"><?= $texts[$lang]['predict'] ?></a></li>
                                                    <?php } 
                                                    
                                                        ?>
                                                    
                                                        <li><a style="font-size:15px;"href="achievements.php">Достижения учеников</a></li>
                                                    </ul>
                                                </li>
                                                <li><a href="#"style="font-size:15px;"><?= $texts[$lang]['menu_rating'] ?> <i class="ti-angle-down"></i></a>
                                                    <ul class="submenu">
                                                        <li><a href="rating_student.php"style="font-size:15px;"><?= $texts[$lang]['menu_rating'] ?></a></li>
                                                        <li><a href="rating_student2.php"style="font-size:15px;"><?= $texts[$lang]['menu_rating_status'] ?></a></li>
                                                    </ul>
                                                </li>
                                                 <li><a style="font-size:15px;" href="#"><?= $texts[$lang]['stats'] ?><i class="ti-angle-down"></i></a>
                                                    <ul class="submenu" >
                                                        <li><a style="font-size:15px;"href="statistics.php"><?= $texts[$lang]['stats'] ?></a></li>
                                                        <li><a href="statistics1.php" style="font-size:15px;"><?= $texts[$lang]['menu_current_attendance'] ?></a></li>
                                                    </ul>
                                                </li>
                                                <li><a href="lesson.php"style="font-size:15px;"><?= $texts[$lang]['menu_current_lesson'] ?></a></li>
                                            
                                            </ul>
                                        </nav>
                                    </div>
                                    <div class="Appointment">
                                        <div class="book_btn d-none d-lg-block" style="font-size:15px; ">
                                             <?php
                                            
                                                if(isset($_SESSION['id1'])){
                                                ?>
                            <a href="profile.php" style="margin:10px;">👤<?=$_SESSION['username1'] ?></a>
                                                <?php } else{?>
                                                    <a href="login.php"><?= $texts[$lang]['login'] ?></a>
                                                    <?php } ?>
                                                </div>
                                                 <?php
       if(isset($_SESSION['id1'])){
      ?>
     <li> <a href="logout.php" style="color: red;"><i class="icon"></i> <?= $texts[$lang]['logout'] ?></a></li>
      <?php } ?>
                                        </div>
                                        <div class="navbar-right">
    <a href="?lang=ru"><img src="img/ru.png" alt="Русский"></a>
    <a href="?lang=tj"><img src="img/tj.png" alt="Тоҷикӣ"></a>
  </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mobile_menu d-block d-lg-none"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
   
<script>
    function updateClock() {
      let now = new Date();
      let hours = String(now.getHours()).padStart(2, '0');
      let minutes = String(now.getMinutes()).padStart(2, '0');
      let seconds = String(now.getSeconds()).padStart(2, '0');
      document.getElementById('clock').textContent = `${hours}:${minutes}:${seconds}`;
    }
    setInterval(updateClock, 1000);
    updateClock(); 
  </script>
