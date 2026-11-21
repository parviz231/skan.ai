<?php
session_start();
require_once("header.php");
 require_once("config2.php");
$selectedDate = isset($_POST['date']) ? $_POST['date'] : date('Y-m-d');
$total = $conn->query("SELECT COUNT(*) as cnt FROM users")->fetch_assoc()['cnt'];
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT user_id) as cnt 
    FROM visits 
    WHERE DATE(time) = ? AND visit = 'visited'
");
$stmt->bind_param("s", $selectedDate);
$stmt->execute();
$present = $stmt->get_result()->fetch_assoc()['cnt'];

$absent = $total - $present;
$percentPresent = $total > 0 ? round(($present / $total) * 100, 1) : 0;
$percentAbsent = 100 - $percentPresent;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Посещаемость школы</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
.filter {
    margin-top: 15px;
}
.filter input, .filter button {
    padding: 8px 12px;
    font-size: 16px;
    border-radius: 8px;
    border: 1px solid #ccc;
}
.filter button {
    background: #2C4361;
    color: white;
    border: none;
    cursor: pointer;
    transition: 0.3s;
}
.filter button:hover {
    background: #2c436191;
    transform: scale(1.05);
}
.stats-block {
    display: flex;
    gap: 40px;
   flex-wrap: wrap;
    justify-content: center;
    align-items: flex-start;
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(10px);
    padding: 20px;
    border:none;
    border-radius: 25px;
   box-shadow: 0 4px 15px rgba(0,0,0,0.1);

}

.table-container, .chart-container {
    flex: 1 1 300px;
    min-width: 300px;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 16px;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}
table th, table td {
    padding: 12px;
    text-align: center;     
}
table th {
    background: #2C4361;
    color: white;
}
table tr:nth-child(even) {
    background: rgba(255,255,255,0.3);
}
table tr:nth-child(odd) {
    background: rgba(255,255,255,0.5);
}

.chart-container {
    text-align: center;
    margin-left: 40px;
}
canvas {
    max-width: 260px !important;
    max-height: 260px !important;
}

.percent-info {
    margin-top: 15px;
    font-size: 16px;
    color: #2c3e50;
}
</style>
</head>
<body>
     <div style="margin-left:100px; margin-right:100px; margin-top:20px;">
 
    <h1>📊 Текущая посещаемость школы</h1>
    <div class="filter">
        <form method="POST">
            <label>Выберите дату:</label>
            <input type="date" name="date" value="<?= htmlspecialchars($selectedDate) ?>">
            <button type="submit">Показать</button>
            <button type="submit" name="date" value="<?= date('Y-m-d') ?>">Сегодня</button>
        </form>
    </div>
<div class="stats-block">
    <div class="table-container">
        <h2>Общая статистика (<?= date('d.m.Y', strtotime($selectedDate)) ?>)</h2>
        <table>
            <tr>
                <th>Всего учеников</th>
                <th>Присутствует</th>
                <th>Отсутствует</th>
            </tr>
            <tr>
                <td><?= $total ?></td>
                <td><?= $present ?> (<?= $percentPresent ?>%)</td>
                <td><?= $absent ?> (<?= $percentAbsent ?>%)</td>
            </tr>
        </table>
    </div>

    <div class="chart-container">
        <h2>Диаграмма</h2>
        <canvas id="attendanceChart"></canvas>
        <div class="percent-info">
            ✅ Присутствует: <?= $percentPresent ?>%<br>
            ❌ Отсутствует: <?= $percentAbsent ?>%
        </div>
    </div>
</div>
</div>
<script>
const ctx = document.getElementById('attendanceChart');
new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['Присутствует', 'Отсутствует'],
        datasets: [{
            data: [<?= $present ?>, <?= $absent ?>],
            backgroundColor: ['#2ecc71', '#e74c3c'],
            borderWidth: 2
        }]
    },
    options: {
        responsive: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: { color: '#2c3e50' }
            }
        }
    }
});
</script>
</body>
</html>
<?php require_once("js.php"); ?>