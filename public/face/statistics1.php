<?php
session_start();
require_once("header.php");
require_once("config2.php");

$selectedDate = isset($_POST['date']) ? $_POST['date'] : date('Y-m-d');

// Выходной только воскресенье (7)
$dayOfWeek = date('N', strtotime($selectedDate));
$isWeekend = ($dayOfWeek == 7);

$total = $conn->query("SELECT COUNT(*) as cnt FROM users")->fetch_assoc()['cnt'];

$present = 0;
if (!$isWeekend) {
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT user_id) as cnt 
        FROM visits 
        WHERE DATE(time) = ? AND visit = 'visited'
    ");
    $stmt->bind_param("s", $selectedDate);
    $stmt->execute();
    $present = $stmt->get_result()->fetch_assoc()['cnt'];
}

$absent = $isWeekend ? 0 : ($total - $present);
$percentPresent = ($total > 0 && !$isWeekend) ? round(($present / $total) * 100, 1) : 0;
$percentAbsent = $isWeekend ? 0 : (100 - $percentPresent);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>СКАН — Посещаемость школы</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    body {
        background: #f5f5f7;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        color: #1d1d1f;
        margin: 0;
        padding-top: 0 !important;
        overflow-x: hidden;
    }

    .stats-page-wrap {
        max-width: 1050px;
        margin: 40px auto;
        padding: 0 24px;
        box-sizing: border-box;
    }

    /* Анимации появления */
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .anim-fade-up {
        animation: fadeUp 0.6s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
        opacity: 0;
    }
    .delay-1 { animation-delay: 0.1s; }
    .delay-2 { animation-delay: 0.2s; }
    .delay-3 { animation-delay: 0.3s; }

    /* Header */
    .stats-header {
        text-align: center;
        margin-bottom: 36px;
    }
    .stats-header-icon {
        width: 60px;
        height: 60px;
        border-radius: 18px;
        background: linear-gradient(135deg, #1d1d1f, #434344);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 16px;
        font-size: 26px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    .stats-header-icon:hover {
        transform: scale(1.15) rotate(10deg);
    }
    .stats-header h1 {
        font-size: 32px;
        font-weight: 800;
        letter-spacing: -0.03em;
        margin: 0 0 8px;
        color: #1d1d1f;
    }
    .stats-header p {
        font-size: 16px;
        color: #86868b;
        margin: 0;
        font-weight: 500;
    }

    /* Filter */
    .filter-card {
        background: #ffffff;
        border: 1px solid #e5e5ea;
        border-radius: 20px;
        padding: 24px 32px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.03);
        margin-bottom: 32px;
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        align-items: flex-end;
        justify-content: center;
        transition: transform 0.3s;
    }
    .filter-card:hover {
        transform: translateY(-2px);
    }
    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .filter-group label {
        font-size: 12px;
        font-weight: 700;
        color: #86868b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .filter-group input {
        padding: 12px 16px;
        border-radius: 10px;
        border: 1px solid #d2d2d7;
        font-size: 15px;
        font-family: inherit;
        background: #f5f5f7;
        color: #1d1d1f;
        outline: none;
        transition: all 0.2s;
        font-weight: 600;
    }
    .filter-group input:focus {
        border-color: #0071e3;
        background: #ffffff;
        box-shadow: 0 0 0 4px rgba(0,113,227,0.15);
    }
    
    .btn-primary {
        padding: 12px 24px;
        background: #0071e3;
        color: #fff;
        border: none;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        height: 46px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn-primary:hover {
        background: #0077ed;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0,113,227,0.3);
    }

    .btn-secondary {
        padding: 12px 24px;
        background: #f5f5f7;
        color: #1d1d1f;
        border: 1px solid #d2d2d7;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        height: 46px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn-secondary:hover {
        background: #e5e5ea;
        transform: translateY(-2px);
    }

    /* Grid Content */
    .content-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        align-items: stretch; /* Одинаковая высота */
    }

    /* Cards */
    .stat-card {
        background: #ffffff;
        border: 1px solid #e5e5ea;
        border-radius: 24px;
        padding: 32px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.03);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        transition: transform 0.3s, box-shadow 0.3s;
        min-height: 360px;
        position: relative;
        overflow: hidden;
    }
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; width: 100%; height: 4px;
        background: linear-gradient(90deg, #0071e3, #34c759);
        opacity: 0;
        transition: opacity 0.3s;
    }
    .stat-card:hover::before {
        opacity: 1;
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.08);
    }
    
    .stat-card h2 {
        font-size: 20px;
        font-weight: 800;
        color: #1d1d1f;
        margin: 0 0 24px 0;
        text-align: center;
        letter-spacing: -0.02em;
    }

    /* Table */
    .custom-table {
        width: 100%;
        border-collapse: collapse;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,0.03);
        border: 1px solid #f0f0f2;
    }
    .custom-table th {
        background: #1d1d1f;
        color: #ffffff;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 16px 12px;
        text-align: center;
    }
    .custom-table td {
        padding: 24px 12px;
        text-align: center;
        font-size: 22px;
        font-weight: 800;
        border-bottom: 1px solid #f0f0f2;
        transition: background 0.2s;
        background: #ffffff;
    }
    .custom-table tr:hover td {
        background: #f9f9fb;
    }
    .custom-table tr:last-child td {
        border-bottom: none;
    }
    .custom-table td.val-present { color: #34c759; }
    .custom-table td.val-absent { color: #ff3b30; }
    
    .table-subtext {
        display: block;
        font-size: 13px;
        color: #86868b;
        font-weight: 600;
        margin-top: 6px;
    }

    /* Chart */
    .chart-wrap {
        position: relative;
        width: 100%;
        max-width: 220px;
        margin: 0 auto 24px;
        transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    .chart-wrap:hover {
        transform: scale(1.08);
    }
    
    .chart-center-text {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        text-align: center;
        pointer-events: none;
    }
    .chart-center-num {
        font-size: 36px;
        font-weight: 900;
        color: #1d1d1f;
        line-height: 1;
        letter-spacing: -0.04em;
    }
    .chart-center-lbl {
        font-size: 11px;
        font-weight: 700;
        color: #86868b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-top: 6px;
    }

    /* Percent Info */
    .percent-info {
        display: flex;
        gap: 12px;
        justify-content: center;
        width: 100%;
    }
    .p-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 700;
        background: #f5f5f7;
        padding: 10px 16px;
        border-radius: 12px;
        transition: transform 0.2s;
    }
    .p-item:hover {
        transform: scale(1.05);
    }
    .p-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
    }
    
    .weekend-msg {
        text-align: center;
        padding: 20px;
        color: #ff3b30;
        font-size: 20px;
        font-weight: 800;
    }
    .weekend-msg i {
        display: block;
        font-size: 64px;
        margin-bottom: 20px;
        animation: float 3s ease-in-out infinite;
    }
    
    @keyframes float {
        0% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-15px) rotate(5deg); }
        100% { transform: translateY(0px) rotate(0deg); }
    }

    @media (max-width: 768px) {
        .stats-page-wrap { margin: 20px auto; padding: 0 16px; }
        .content-grid { grid-template-columns: 1fr; }
        .filter-card { flex-direction: column; align-items: stretch; padding: 20px; }
        .btn-primary, .btn-secondary { width: 100%; justify-content: center; }
        .stat-card { min-height: auto; padding: 24px; }
        .custom-table td { padding: 16px 8px; font-size: 18px; }
    }
</style>
</head>
<body>

<div class="stats-page-wrap">

    <!-- Header -->
    <div class="stats-header anim-fade-up">
        <div class="stats-header-icon">
            <i class="fas fa-school"></i>
        </div>
        <h1>Посещаемость школы</h1>
        <p>Общая статистика по всем ученикам</p>
    </div>

    <!-- Filter -->
    <div class="filter-card anim-fade-up delay-1">
        <form method="POST" style="display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-end; width: 100%; justify-content: center;">
            <div class="filter-group">
                <label>Введите дату</label>
                <input type="date" name="date" value="<?= htmlspecialchars($selectedDate) ?>">
            </div>
            <button type="submit" class="btn-primary"><i class="fas fa-search"></i> Показать</button>
            <button type="submit" name="date" value="<?= date('Y-m-d') ?>" class="btn-secondary"><i class="fas fa-calendar-day"></i> Сегодня</button>
        </form>
    </div>

    <!-- Content -->
    <div class="content-grid">
        
        <!-- Left: Table -->
        <div class="stat-card anim-fade-up delay-2">
            <h2>Статистика за <?= date('d.m.Y', strtotime($selectedDate)) ?></h2>
            
            <?php if ($isWeekend): ?>
                <div class="weekend-msg">
                    <i class="fas fa-glass-cheers"></i>
                    Сегодня выходной!
                </div>
            <?php else: ?>
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Всего</th>
                            <th>Присутствует</th>
                            <th>Отсутствует</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?= $total ?></td>
                            <td class="val-present"><?= $present ?> <span class="table-subtext">(<?= $percentPresent ?>%)</span></td>
                            <td class="val-absent"><?= $absent ?> <span class="table-subtext">(<?= $percentAbsent ?>%)</span></td>
                        </tr>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Right: Chart -->
        <div class="stat-card anim-fade-up delay-3">
            <h2>Соотношение</h2>
            
            <?php if ($isWeekend): ?>
                <div class="weekend-msg" style="color: #86868b;">
                    <i class="fas fa-bed" style="color: #d2d2d7;"></i>
                    Отдыхаем
                </div>
            <?php else: ?>
                <div class="chart-wrap">
                    <canvas id="attendanceChart"></canvas>
                    <div class="chart-center-text">
                        <div class="chart-center-num"><?= $total ?></div>
                        <div class="chart-center-lbl">Всего</div>
                    </div>
                </div>
                <div class="percent-info">
                    <div class="p-item">
                        <div class="p-dot" style="background: #34c759;"></div>
                        <span style="color: #34c759;"><?= $percentPresent ?>%</span>
                    </div>
                    <div class="p-item">
                        <div class="p-dot" style="background: #ff3b30;"></div>
                        <span style="color: #ff3b30;"><?= $percentAbsent ?>%</span>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
<?php if (!$isWeekend): ?>
const ctx = document.getElementById('attendanceChart');
new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['Присутствует', 'Отсутствует'],
        datasets: [{
            data: [<?= $present ?>, <?= $absent ?>],
            backgroundColor: ['#34c759', '#ff3b30'],
            borderWidth: 0,
            hoverOffset: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        cutout: '82%', // Идеально тонкое кольцо
        animation: {
            animateScale: true,
            animateRotate: true,
            duration: 2000, // Долгая красивая анимация
            easing: 'easeOutQuart'
        },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1d1d1f',
                titleFont: { family: 'Inter', size: 14, weight: 'bold' },
                bodyFont: { family: 'Inter', size: 14 },
                padding: 14,
                cornerRadius: 10,
                displayColors: false
            }
        }
    }
});
<?php endif; ?>
</script>
</body>
</html>
<?php require_once("js.php"); ?>