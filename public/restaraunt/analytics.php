<?php
require_once __DIR__ . '/api/db.php';
header('Content-Type: text/html; charset=utf-8');

// Обработка кнопки генерации демонстрационных событий в SQLite (для презентации/олимпиады)
if (isset($_POST['seed_demo'])) {
    $statuses = [
        ['FREE', 'WAITING', 2, 120],
        ['WAITING', 'SERVED', 2, 145],
        ['SERVED', 'DIRTY', 2, 2400],
        ['DIRTY', 'FREE', 0, 180],
        ['FREE', 'WAITING', 4, 90],
        ['WAITING', 'SERVED', 4, 340], // превышение SLA > 300 сек
        ['SERVED', 'AWAY', 4, 1500],
        ['AWAY', 'SERVED', 4, 210],
        ['SERVED', 'DIRTY', 4, 1900],
        ['DIRTY', 'FREE', 0, 150],
    ];
    $insert = $pdo->prepare("
        INSERT INTO table_events (table_id, old_status, new_status, guests_count, duration_prev_state_sec, created_at)
        VALUES (?, ?, ?, ?, ?, datetime('now', ?))
    ");
    for ($i = 0; $i < 24; $i++) {
        $step = $statuses[$i % count($statuses)];
        $tableId = ($i % 8) + 1;
        $hoursAgo = '-' . rand(0, 11) . ' hours';
        $insert->execute([$tableId, $step[0], $step[1], $step[2], $step[3], $hoursAgo]);
    }
    header("Location: analytics.php");
    exit;
}

// 1. KPI: Ожидание официанта (WAITING -> SERVED)
$waitStats = $pdo->query("
    SELECT 
        COUNT(*) as total_orders,
        ROUND(AVG(duration_prev_state_sec), 1) as avg_wait_sec,
        SUM(CASE WHEN duration_prev_state_sec <= 300 THEN 1 ELSE 0 END) as on_time_orders
    FROM table_events 
    WHERE old_status = 'WAITING' AND new_status = 'SERVED'
")->fetch();

// 2. KPI: Скорость уборки столов (DIRTY -> FREE)
$cleanStats = $pdo->query("
    SELECT 
        COUNT(*) as total_cleanings,
        ROUND(AVG(duration_prev_state_sec), 1) as avg_clean_sec
    FROM table_events 
    WHERE old_status = 'DIRTY' AND new_status = 'FREE'
")->fetch();

// 3. График посадок по часам суток
$hourlyRaw = $pdo->query("
    SELECT strftime('%H', created_at) as hour, COUNT(*) as seatings
    FROM table_events
    WHERE new_status = 'WAITING'
    GROUP BY hour
    ORDER BY hour ASC
")->fetchAll();

// 4. Загруженность по каждому столику
$tableLoadRaw = $pdo->query("
    SELECT t.id, COUNT(e.id) as events_count
    FROM restaurant_tables t
    LEFT JOIN table_events e ON t.id = e.table_id AND e.new_status = 'WAITING'
    GROUP BY t.id
    ORDER BY t.id ASC
")->fetchAll();

// 5. Последние 15 записей из журнала событий (Event Sourcing)
$recentEvents = $pdo->query("
    SELECT * FROM table_events 
    ORDER BY id DESC 
    LIMIT 15
")->fetchAll();

$totalOrders = (int)($waitStats['total_orders'] ?? 0);
$onTimeOrders = (int)($waitStats['on_time_orders'] ?? 0);
$slaPercent = $totalOrders > 0 ? round(($onTimeOrders / $totalOrders) * 100, 1) : 100.0;
$avgWaitSec = (float)($waitStats['avg_wait_sec'] ?? 0);
$avgCleanSec = (float)($cleanStats['avg_clean_sec'] ?? 0);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>VisionTable AI — BI-Аналитика и Event Sourcing (SQLite)</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
    body { background: #090d16; color: #f8fafc; min-height: 100vh; padding: 24px; }
    .container { max-width: 1280px; margin: 0 auto; display: flex; flex-direction: column; gap: 20px; }

    .header {
      display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 12px;
      background: #0f172a; border: 1px solid #1e293b; border-radius: 16px; padding: 18px 24px;
    }
    .header h1 { font-size: 20px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
    .header p { font-size: 13px; color: #94a3b8; margin-top: 4px; }

    .btn-group { display: flex; gap: 10px; flex-wrap: wrap; }
    .btn {
      background: #1e293b; color: #e2e8f0; border: 1px solid #334155;
      padding: 9px 16px; border-radius: 10px; font-size: 13px; font-weight: 600;
      cursor: pointer; text-decoration: none; transition: all 0.2s;
    }
    .btn:hover { background: #334155; }
    .btn-primary { background: #0284c7; border-color: #38bdf8; color: #fff; }
    .btn-primary:hover { background: #0369a1; }
    .btn-demo { background: #6d28d9; border-color: #8b5cf6; color: #fff; }
    .btn-demo:hover { background: #5b21b6; }

    .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; }
    .kpi-card {
      background: #0f172a; border: 1px solid #1e293b; border-radius: 16px; padding: 20px;
      display: flex; flex-direction: column; justify-content: space-between;
    }
    .kpi-label { font-size: 13px; color: #94a3b8; font-weight: 600; }
    .kpi-value { font-size: 32px; font-weight: 800; margin: 10px 0 6px; color: #f8fafc; }
    .kpi-sub { font-size: 12px; color: #38bdf8; }

    .charts-grid { display: grid; grid-template-columns: 1.4fr 1fr; gap: 16px; }
    @media (max-width: 900px) { .charts-grid { grid-template-columns: 1fr; } }

    .card {
      background: #0f172a; border: 1px solid #1e293b; border-radius: 16px; padding: 20px;
    }
    .card-title { font-size: 15px; font-weight: 700; margin-bottom: 16px; color: #e2e8f0; }

    table { width: 100%; border-collapse: collapse; font-size: 13px; }
    th { text-align: left; padding: 12px; color: #94a3b8; border-bottom: 1px solid #1e293b; font-weight: 600; }
    td { padding: 12px; border-bottom: 1px solid #1e293b; color: #e2e8f0; }
    tr:hover td { background: rgba(30, 41, 59, 0.4); }

    .badge { padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; display: inline-block; }
    .b-FREE { background: rgba(34, 197, 94, 0.2); color: #4ade80; }
    .b-WAITING { background: rgba(239, 68, 68, 0.2); color: #f87171; }
    .b-SERVED { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }
    .b-AWAY { background: rgba(168, 85, 247, 0.2); color: #c084fc; }
    .b-DIRTY { background: rgba(234, 179, 8, 0.2); color: #facc15; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <div>
        <h1>📊 VisionTable AI — BI-Аналитика зала (SQLite Event Sourcing)</h1>
        <p>Анализ скорости обслуживания, соблюдения SLA и оборачиваемости столов на основе журнала событий</p>
      </div>
      <div class="btn-group">
        <form method="POST" style="display:inline;">
          <button type="submit" name="seed_demo" class="btn btn-demo">⚡ Добавить демо-события в БД</button>
        </form>
        <a href="index.php" class="btn btn-primary">← Вернуться в 3D-зал</a>
      </div>
    </div>

    <div class="kpi-grid">
      <div class="kpi-card">
        <div class="kpi-label">Соблюдение SLA (≤ 5 мин)</div>
        <div class="kpi-value" style="color: <?= $slaPercent >= 80 ? '#4ade80' : '#f87171' ?>;">
          <?= $slaPercent ?>%
        </div>
        <div class="kpi-sub">Вовремя принято: <?= $onTimeOrders ?> из <?= $totalOrders ?> заказов</div>
      </div>

      <div class="kpi-card">
        <div class="kpi-label">Среднее время ожидания официанта</div>
        <div class="kpi-value"><?= gmdate("i:s", (int)$avgWaitSec) ?></div>
        <div class="kpi-sub"><?= $avgWaitSec ?> сек (цель: до 05:00)</div>
      </div>

      <div class="kpi-card">
        <div class="kpi-label">Среднее время уборки стола</div>
        <div class="kpi-value"><?= gmdate("i:s", (int)$avgCleanSec) ?></div>
        <div class="kpi-sub">От ухода гостей (DIRTY) до FREE</div>
      </div>

      <div class="kpi-card">
        <div class="kpi-label">Всего зафиксировано посадок</div>
        <div class="kpi-value"><?= $totalOrders ?></div>
        <div class="kpi-sub">Событий в журнале: <?= count($recentEvents) ?>+</div>
      </div>
    </div>

    <div class="charts-grid">
      <div class="card">
        <div class="card-title">Пиковая активность посадок по часам суток</div>
        <canvas id="hourlyChart" height="130"></canvas>
      </div>
      <div class="card">
        <div class="card-title">Популярность столиков (количество посадок)</div>
        <canvas id="tablesChart" height="175"></canvas>
      </div>
    </div>

    <div class="card">
      <div class="card-title">Журнал событий (Event Sourcing — последние 15 переходов состояний из SQLite)</div>
      <div style="overflow-x: auto;">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Столик</th>
              <th>Переход статуса</th>
              <th>Гостей</th>
              <th>Длительность прошлого этапа</th>
              <th>Время фиксации (UTC)</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($recentEvents)): ?>
              <tr>
                <td colspan="6" style="text-align:center; color:#64748b; padding: 24px;">
                  В базе пока нет событий. Покликайте по статусам в 3D-зале или нажмите «⚡ Добавить демо-события в БД».
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($recentEvents as $ev): ?>
                <tr>
                  <td>#<?= (int)$ev['id'] ?></td>
                  <td><strong>Столик №<?= (int)$ev['table_id'] ?></strong></td>
                  <td>
                    <span class="badge b-<?= htmlspecialchars($ev['old_status']) ?>"><?= htmlspecialchars($ev['old_status']) ?></span>
                    &nbsp;→&nbsp;
                    <span class="badge b-<?= htmlspecialchars($ev['new_status']) ?>"><?= htmlspecialchars($ev['new_status']) ?></span>
                  </td>
                  <td><?= (int)$ev['guests_count'] ?> чел.</td>
                  <td><?= gmdate("i:s", (int)$ev['duration_prev_state_sec']) ?> (<?= (int)$ev['duration_prev_state_sec'] ?> сек)</td>
                  <td style="color:#94a3b8;"><?= htmlspecialchars($ev['created_at']) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <script>
    const hourlyData = <?= json_encode($hourlyRaw, JSON_UNESCAPED_UNICODE) ?>;
    const tableLoadData = <?= json_encode($tableLoadRaw, JSON_UNESCAPED_UNICODE) ?>;

    // График по часам
    new Chart(document.getElementById('hourlyChart'), {
      type: 'line',
      data: {
        labels: hourlyData.length ? hourlyData.map(r => r.hour + ':00') : ['12:00', '14:00', '18:00', '20:00'],
        datasets: [{
          label: 'Количество посадок',
          data: hourlyData.length ? hourlyData.map(r => r.seatings) : [0, 0, 0, 0],
          borderColor: '#38bdf8',
          backgroundColor: 'rgba(56, 189, 248, 0.15)',
          borderWidth: 3,
          fill: true,
          tension: 0.35
        }]
      },
      options: {
        plugins: { legend: { display: false } },
        scales: {
          x: { ticks: { color: '#94a3b8' }, grid: { color: '#1e293b' } },
          y: { beginAtZero: true, ticks: { color: '#94a3b8', stepSize: 1 }, grid: { color: '#1e293b' } }
        }
      }
    });

    // График по столикам
    new Chart(document.getElementById('tablesChart'), {
      type: 'bar',
      data: {
        labels: tableLoadData.map(t => 'Стол №' + t.id),
        datasets: [{
          label: 'Посадок',
          data: tableLoadData.map(t => t.events_count),
          backgroundColor: '#8b5cf6',
          borderRadius: 6
        }]
      },
      options: {
        plugins: { legend: { display: false } },
        scales: {
          x: { ticks: { color: '#94a3b8' }, grid: { display: false } },
          y: { beginAtZero: true, ticks: { color: '#94a3b8', stepSize: 1 }, grid: { color: '#1e293b' } }
        }
      }
    });
  </script>
</body>
</html>