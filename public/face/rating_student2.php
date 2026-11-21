<?php
session_start();
require_once("header.php");
require_once("config2.php");
if ($conn->connect_error) die("Ошибка подключения: " . $conn->connect_error);
date_default_timezone_set("Asia/Dushanbe");

// Пагинация
$per_page = 12;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $per_page;

// Общее количество классов
$total_query = $conn->query("SELECT COUNT(DISTINCT CONCAT(grade, class)) as total FROM users");
$total_classes = $total_query->fetch_assoc()['total'];
$total_pages = ceil($total_classes / $per_page);

// Оптимизированный запрос
$sql = "
SELECT 
    CONCAT(u.grade, u.class) AS class_name,
    COALESCE(att.attendance, 0) AS attendance,
    COALESCE(gr.avg_grade, 0) AS avg_grade,
    ROUND((COALESCE(att.attendance, 0) + COALESCE(gr.avg_grade * 10, 0)) / 2, 2) AS rating
FROM (
    SELECT DISTINCT grade, class FROM users
) u
LEFT JOIN (
    SELECT u2.grade, u2.class, ROUND(SUM(v.visit = 'visited') / NULLIF(COUNT(v.visit), 0) * 100, 2) AS attendance
    FROM users u2 LEFT JOIN visits v ON u2.id = v.user_id
    GROUP BY u2.grade, u2.class
) att ON u.grade = att.grade AND u.class = att.class
LEFT JOIN (
    SELECT u3.grade, u3.class, ROUND(AVG(g.grade_value), 2) AS avg_grade
    FROM users u3 LEFT JOIN grades g ON u3.id = g.user_id
    GROUP BY u3.grade, u3.class
) gr ON u.grade = gr.grade AND u.class = gr.class
ORDER BY rating DESC
LIMIT $per_page OFFSET $offset
";

$result = $conn->query($sql);

// Топ-3
$top3_sql = "
SELECT 
    CONCAT(u.grade, u.class) AS class_name,
    COALESCE(att.attendance, 0) AS attendance,
    COALESCE(gr.avg_grade, 0) AS avg_grade,
    ROUND((COALESCE(att.attendance, 0) + COALESCE(gr.avg_grade * 10, 0)) / 2, 2) AS rating
FROM (
    SELECT DISTINCT grade, class FROM users
) u
LEFT JOIN (
    SELECT u2.grade, u2.class, ROUND(SUM(v.visit = 'visited') / NULLIF(COUNT(v.visit), 0) * 100, 2) AS attendance
    FROM users u2 LEFT JOIN visits v ON u2.id = v.user_id
    GROUP BY u2.grade, u2.class
) att ON u.grade = att.grade AND u.class = att.class
LEFT JOIN (
    SELECT u3.grade, u3.class, ROUND(AVG(g.grade_value), 2) AS avg_grade
    FROM users u3 LEFT JOIN grades g ON u3.id = g.user_id
    GROUP BY u3.grade, u3.class
) gr ON u.grade = gr.grade AND u.class = gr.class
ORDER BY rating DESC
LIMIT 3
";

$top3_result = $conn->query($top3_sql);
$top3 = [];
while ($row = $top3_result->fetch_assoc()) {
    $top3[] = $row;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>СКАН — Рейтинг классов</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .cr-wrap {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 24px;
            font-family: 'Inter', -apple-system, sans-serif;
            color: #1d1d1f;
            box-sizing: border-box;
        }
        .cr-wrap * { box-sizing: border-box; margin: 0; padding: 0; }
        
        /* ===== HEADER ===== */
        .cr-header {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .cr-header-icon {
            width: 64px;
            height: 64px;
            border-radius: 18px;
            background: #1d1d1f;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            font-size: 28px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        }
        
        .cr-header h1 {
            font-size: 32px;
            font-weight: 800;
            letter-spacing: -0.03em;
            margin-bottom: 6px;
        }
        
        .cr-header p {
            font-size: 16px;
            color: #6e6e73;
        }
        
        /* ===== TOP 3 ===== */
        .cr-top3 {
            display: grid;
            grid-template-columns: 1fr 1.3fr 1fr;
            gap: 16px;
            margin-bottom: 24px;
            align-items: end;
        }
        
        .cr-top3-card {
            background: #fff;
            border: 1px solid #e5e5ea;
            border-radius: 16px;
            padding: 24px 20px;
            text-align: center;
            box-shadow: 0 4px 16px rgba(0,0,0,0.04);
            transition: all 0.3s ease;
        }
        
        .cr-top3-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(0,0,0,0.1);
        }
        
        .cr-top3-card.first {
            border-color: #fbbf24;
            padding: 32px 20px;
            box-shadow: 0 8px 32px rgba(251,191,36,0.2);
            background: linear-gradient(180deg, #fff 0%, #fffbeb 100%);
        }
        
        .cr-medal {
            font-size: 40px;
            margin-bottom: 10px;
        }
        
        .cr-class-name {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.02em;
            margin-bottom: 6px;
        }
        
        .cr-rating {
            font-size: 28px;
            font-weight: 800;
            color: #1a73e8;
            letter-spacing: -0.02em;
        }
        
        .cr-rating-label {
            font-size: 12px;
            color: #6e6e73;
            font-weight: 500;
            margin-top: 4px;
        }
        
        /* ===== CARD ===== */
        .cr-card {
            background: #fff;
            border: 1px solid #e5e5ea;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
            margin-bottom: 20px;
        }
        
        .cr-card-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e5e5ea;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .cr-card-header i {
            color: #1a73e8;
            font-size: 20px;
        }
        
        .cr-card-header h2 {
            font-size: 18px;
            font-weight: 700;
        }
        
        /* ===== TABLE ===== */
        .cr-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .cr-table th {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6e6e73;
            text-align: left;
            padding: 14px 20px;
            border-bottom: 2px solid #e5e5ea;
            background: #f8f8fa;
        }
        
        .cr-table td {
            font-size: 15px;
            padding: 16px 20px;
            border-bottom: 1px solid #f1f3f4;
            color: #1d1d1f;
        }
        
        .cr-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .cr-table tbody tr {
            transition: background 0.15s;
        }
        
        .cr-table tbody tr:hover {
            background: #f8f8fa;
        }
        
        .cr-rank {
            font-weight: 700;
            font-size: 16px;
            color: #1d1d1f;
        }
        
        .cr-class-cell {
            font-weight: 700;
            font-size: 16px;
        }
        
        .cr-rating-cell {
            font-weight: 700;
            color: #1a73e8;
            font-size: 16px;
        }
        
        /* ===== BADGE ===== */
        .cr-badge {
            padding: 6px 16px;
            border-radius: 100px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .cr-badge.good {
            background: #e6f4ea;
            color: #137333;
        }
        
        .cr-badge.warn {
            background: #fef7e0;
            color: #b06000;
        }
        
        .cr-badge.bad {
            background: #fce8e6;
            color: #c5221f;
        }
        
        /* ===== PAGINATION ===== */
        .cr-pagination {
            display: flex;
            justify-content: center;
            gap: 6px;
            flex-wrap: wrap;
        }
        
        .cr-page-link {
            min-width: 44px;
            height: 44px;
            padding: 0 12px;
            border-radius: 10px;
            border: 1px solid #e5e5ea;
            background: #fff;
            color: #1d1d1f;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .cr-page-link:hover {
            background: #f1f3f4;
            border-color: #d2d2d7;
        }
        
        .cr-page-link.active {
            background: #1a73e8;
            color: #fff;
            border-color: #1a73e8;
            box-shadow: 0 4px 12px rgba(26,115,232,0.3);
        }
        
        .cr-page-link.disabled {
            opacity: 0.3;
            pointer-events: none;
        }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .cr-wrap {
                margin: 20px auto;
                padding: 0 12px;
            }
            
            .cr-header h1 {
                font-size: 24px;
            }
            
            .cr-header-icon {
                width: 48px;
                height: 48px;
                font-size: 20px;
            }
            
            .cr-top3 {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .cr-top3-card.first {
                order: -1;
                padding: 24px 20px;
            }
            
            .cr-table th,
            .cr-table td {
                padding: 10px 12px;
                font-size: 13px;
            }
            
            .cr-page-link {
                min-width: 36px;
                height: 36px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>

<div class="cr-wrap">
    <!-- Header -->
    <div class="cr-header">
        <div class="cr-header-icon">
            <i class="fas fa-school"></i>
        </div>
        <h1>Рейтинг классов</h1>
        <p>Успеваемость и посещаемость по классам</p>
    </div>

    <!-- Top 3 -->
    <?php if (!empty($top3)): ?>
    <div class="cr-top3">
        <?php
        $order = [1, 0, 2];
        $medals = ['🥇', '🥈', '🥉'];
        foreach ($order as $idx):
            if (!isset($top3[$idx])) continue;
            $cls = $top3[$idx];
            $isFirst = $idx === 0;
        ?>
        <div class="cr-top3-card <?= $isFirst ? 'first' : '' ?>">
            <div class="cr-medal"><?= $medals[$idx] ?></div>
            <div class="cr-class-name"><?= htmlspecialchars($cls['class_name']) ?> класс</div>
            <div class="cr-rating"><?= number_format($cls['rating'], 1) ?></div>
            <div class="cr-rating-label">Рейтинг</div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Table -->
    <div class="cr-card">
        <div class="cr-card-header">
            <i class="fas fa-list"></i>
            <h2>Все классы</h2>
        </div>
        <table class="cr-table">
            <thead>
                <tr>
                    <th style="width:70px;">Место</th>
                    <th>Класс</th>
                    <th>Посещаемость</th>
                    <th>Средний балл</th>
                    <th>Рейтинг</th>
                    <th>Статус</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result && $result->num_rows > 0) {
                    $rank = $offset + 1;
                    while($row = $result->fetch_assoc()) {
                        $attendance = $row["attendance"] ?? 0;
                        $avg_grade = $row["avg_grade"] ?? 0;
                        $rating = $row["rating"] ?? 0;
                        
                        if ($rating < 20 || $attendance < 50) {
                            $badge_class = 'bad';
                            $status_text = 'Слабый';
                        } elseif ($rating < 40 || $attendance < 70) {
                            $badge_class = 'warn';
                            $status_text = 'Средний';
                        } else {
                            $badge_class = 'good';
                            $status_text = 'Отличный';
                        }
                        
                        echo "<tr>
                                <td><span class='cr-rank'>$rank</span></td>
                                <td><span class='cr-class-cell'>" . htmlspecialchars($row['class_name']) . "</span></td>
                                <td>$attendance%</td>
                                <td>$avg_grade</td>
                                <td><span class='cr-rating-cell'>$rating</span></td>
                                <td><span class='cr-badge $badge_class'>$status_text</span></td>
                              </tr>";
                        $rank++;
                    }
                } else {
                    echo "<tr><td colspan='6' style='text-align:center;padding:32px;color:#6e6e73;font-size:15px;'>Нет данных для отображения</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="cr-pagination">
        <a href="?page=<?= max(1, $page - 1) ?>" class="cr-page-link <?= $page <= 1 ? 'disabled' : '' ?>">
            <i class="fas fa-chevron-left" style="font-size:12px;"></i>
        </a>
        
        <?php
        $start_page = max(1, $page - 2);
        $end_page = min($total_pages, $page + 2);
        
        if ($start_page > 1) {
            echo "<a href='?page=1' class='cr-page-link'>1</a>";
            if ($start_page > 2) echo "<span class='cr-page-link disabled'>...</span>";
        }
        
        for ($i = $start_page; $i <= $end_page; $i++) {
            $active = $i == $page ? 'active' : '';
            echo "<a href='?page=$i' class='cr-page-link $active'>$i</a>";
        }
        
        if ($end_page < $total_pages) {
            if ($end_page < $total_pages - 1) echo "<span class='cr-page-link disabled'>...</span>";
            echo "<a href='?page=$total_pages' class='cr-page-link'>$total_pages</a>";
        }
        ?>
        
        <a href="?page=<?= min($total_pages, $page + 1) ?>" class="cr-page-link <?= $page >= $total_pages ? 'disabled' : '' ?>">
            <i class="fas fa-chevron-right" style="font-size:12px;"></i>
        </a>
    </div>
    <?php endif; ?>
</div>

<?php require_once("js.php"); ?>
</body>
</html>