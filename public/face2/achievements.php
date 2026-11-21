<?php
require_once("header.php");
require_once("config2.php");


$selected_class = $_GET['class'] ?? 'all';
$selected_year  = $_GET['year'] ?? 'all';
$selected_place = $_GET['place'] ?? 'all';


$sql = "SELECT 
            a.id,
            a.competition_name,
            a.competition_type,
            a.place,
            a.school_year,
            u.name AS student_name,
            u.surname AS student_surname,
            u.class AS student_class
        FROM achievements a
        JOIN users u ON a.student_id = u.id
        WHERE 1=1";

if ($selected_class != 'all') $sql .= " AND u.class = '$selected_class'";
if ($selected_year  != 'all') $sql .= " AND a.school_year = '$selected_year'";
if ($selected_place != 'all') $sql .= " AND a.place = '$selected_place'";

$sql .= " ORDER BY a.competition_type, a.place;";
$result = $conn->query($sql);


$groups = [
    'школьные' => [],
    'республиканские' => [],
    'международные' => [],
    'спортивные' => []
];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if (isset($groups[$row['competition_type']])) {
            $groups[$row['competition_type']][] = $row;
        }
    }
}

$classes = [];
$years = [];
$places = [];

$classRes = $conn->query("SELECT DISTINCT class FROM users ORDER BY class");
while ($r = $classRes->fetch_assoc()) $classes[] = $r['class'];

$yearRes = $conn->query("SELECT DISTINCT school_year FROM achievements ORDER BY school_year DESC");
while ($r = $yearRes->fetch_assoc()) $years[] = $r['school_year'];

$placeRes = $conn->query("SELECT DISTINCT place FROM achievements ORDER BY place ASC");
while ($r = $placeRes->fetch_assoc()) $places[] = $r['place'];


$stats = [
    'Школьные' => count($groups['школьные']),
    'Республиканские' => count($groups['республиканские']),
    'Международные' => count($groups['международные']),
    'Спортивные' => count($groups['спортивные']),
];


$yearStats = [];
$yearData = $conn->query("SELECT school_year, COUNT(*) AS c FROM achievements GROUP BY school_year ORDER BY school_year ASC");
while ($r = $yearData->fetch_assoc()) {
    $yearStats[$r['school_year']] = $r['c'];
}
?>

<style>
  
    h1 {
        font-size: 42px;
        font-weight: 700;
        text-align: center;
        margin-bottom: 40px;
        color: #2C3E50;
    }
    h2 {
        margin: 30px 0 20px;
        font-weight: 600;
        color: #34495E;
    }
    .filter-form {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        margin-bottom: 40px;
    }
    .card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        transition: transform .3s ease, box-shadow .3s ease;
        width: 270px;
        margin-right: 100px;
        margin-top: 10px;
    }
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.2);
    }
    .card img {
        height: 180px;
        object-fit: cover;
        border-radius: 15px 15px 0 0;
    }
    .card-body {
        background: white;
        border-radius: 0 0 15px 15px;
    }
    .card-title {
        font-size: 18px;
        font-weight: 600;
    }
    .show-more {
        display: block;
        margin: 20px auto;
        border-radius: 25px;
        padding: 8px 20px;
    }
    .hidden-card {
        display: none;
    }
    .stats-container {
        display: flex;
        justify-content: space-around;
        flex-wrap: wrap;
        margin-bottom: 40px;
        gap: 20px;
    }
    .stat-box {
        background: white;
        border-radius: 15px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        width: 48%;
        padding: 20px;
        text-align: center;
        height: 500px;
    }
    canvas {
        width: 100% !important;
        height: 400px !important;
    }
</style>
</head>
<body>

<div class="container py-5">
    <h1>Достижения учеников</h1>

    
    <form class="filter-form" method="GET" action="">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label fw-bold">Класс</label>
                <select name="class" class="form-control">
                    <option value="all">Все классы</option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?= $class ?>" <?= ($selected_class == $class) ? 'selected' : '' ?>><?= $class ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Учебный год</label>
                <select name="year" class="form-control">
                    <option value="all">Все годы</option>
                    <?php foreach ($years as $y): ?>
                        <option value="<?= $y ?>" <?= ($selected_year == $y) ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Место</label>
                <select name="place" class="form-control">
                    <option value="all">Все места</option>
                    <?php foreach ($places as $p): ?>
                        <option value="<?= $p ?>" <?= ($selected_place == $p) ? 'selected' : '' ?>><?= $p ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="text-center mt-4">
            <button class="btn btn-primary px-5">Применить фильтры</button>
        </div>
    </form>

   
    <div class="stats-container">
        <div class="stat-box">
            
            <canvas id="chartTypes"></canvas>
        </div>
        <div class="stat-box">
            <div style="font-size:18px; color:#2C3E50; font-weight:bold;">Соотношение достижения по годам</div>
            <canvas id="chartYears"></canvas>
        </div>
    </div>

    <?php
    function render_cards($title, $data, $class, $blockId, $imagePath) {
        echo "<div class='block $class mb-5'>";
        echo "<h2>$title (" . count($data) . ")</h2>";
        if (count($data) > 0) {
            echo "<div class='row g-4 mt-2' id='block-$blockId'>";
            $i = 0;
            foreach ($data as $row) {
                $hidden = ($i >= 4) ? "hidden-card" : "";
                echo "
                <div class='col-lg-3 col-md-4 col-sm-6 $hidden'>
                    <div class='card h-100'>
                        <img src='$imagePath' alt=''>
                        <div class='card-body text-center'>
                            <h5 class='card-title'>{$row['competition_name']}</h5>
                            <p class='text-muted mb-1'><strong>Ученик:</strong> {$row['student_name']} {$row['student_surname']}</p>
                            <p class='mb-1'><strong>Класс:</strong> {$row['student_class']}</p>
                            <p class='mb-1'><strong>Место:</strong> {$row['place']}</p>
                            <p class='mb-0'><strong>Год:</strong> {$row['school_year']}</p>
                        </div>
                    </div>
                </div>";
                $i++;
            }
            echo "</div>";
            if (count($data) > 4) {
                echo "<button class='btn btn-outline-secondary show-more' data-target='block-$blockId'>Показать ещё</button>";
            }
        } else {
            echo "<p class='text-center text-muted fst-italic'>Нет данных для отображения</p>";
        }
        echo "</div>";
    }

    render_cards('🏫 Школьные олимпиады', $groups['школьные'], 'school', 'school', 'img/d1.jpg');
    render_cards('🇹🇯 Республиканские олимпиады', $groups['республиканские'], 'republic', 'republic', 'img/d4.jpg');
    render_cards('🌍 Международные олимпиады', $groups['международные'], 'international', 'international', 'img/d3.jpg');
    render_cards('⚽ Спортивные соревнования', $groups['спортивные'], 'sport', 'sport', 'img/d2.jpg');
    ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    
    document.querySelectorAll(".show-more").forEach(btn => {
        btn.addEventListener("click", () => {
            const block = document.getElementById(btn.dataset.target);
            const hiddenCards = block.querySelectorAll(".hidden-card");
            if (hiddenCards.length > 0) {
                hiddenCards.forEach(card => card.classList.remove("hidden-card"));
                btn.textContent = "Свернуть";
                btn.classList.replace("btn-outline-secondary", "btn-outline-danger");
            } else {
                const allCards = block.querySelectorAll(".col-lg-3");
                allCards.forEach((card, index) => { if(index>=4) card.classList.add("hidden-card"); });
                btn.textContent = "Показать ещё";
                btn.classList.replace("btn-outline-danger", "btn-outline-secondary");
                block.scrollIntoView({ behavior: "smooth", block: "start" });
            }
        });
    });

   
    const darkColors = ['#336caaff','#2c5194ff','#2f569eff','#778DA9','#E0E1DD','#1F3B4D','#2E4A6D','#1f1f63ff'];

    
    new Chart(document.getElementById('chartTypes'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_keys($stats)) ?>,
            datasets: [{
                data: <?= json_encode(array_values($stats)) ?>,
                backgroundColor: darkColors,
                borderColor: '#fff',
                borderWidth: 2,
                hoverOffset: 12
            }]
        },
        options: {
            plugins: {
                legend: { position: 'bottom', labels: { color:'#000000ff', font:{size:14} } },
                title: {
                    display: true,
                    text: 'Соотношение достижений по типам',
                    color: '#2C3E50',
                    font:{size:18, weight:'bold'}
                },
                tooltip: { backgroundColor:'rgba(0,0,0,0.85)', titleColor:'#fff', bodyColor:'#ddd' }
            },
            animation: { animateScale:true, animateRotate:true, duration:1200 }
        }
    });

   
    new Chart(document.getElementById('chartYears'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_keys($yearStats)) ?>,
            
            datasets: [{
                label: 'Количество достижений',
                data: <?= json_encode(array_values($yearStats)) ?>,
                backgroundColor: darkColors,
                borderRadius: 8
            }]
        },
        options: {
            plugins:{ legend:{ display:false } },
            scales:{ y:{ beginAtZero:true, ticks:{ stepSize:1 } } }
        }
    });
});
</script>
</body>
</html>

<?php
$conn->close();
require_once("js.php");
?>
