<?php
session_start();
require_once("header.php");
require_once("config2.php");
date_default_timezone_set("Asia/Dushanbe");

// Пагинация
$per_page = 15;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $per_page;

// Получаем общее количество учеников
$total_query = $conn->query("SELECT COUNT(DISTINCT u.id) as total FROM users u");
$total_students = $total_query->fetch_assoc()['total'];
$total_pages = ceil($total_students / $per_page);

// Оптимизированный запрос с LIMIT и OFFSET
$sql = "
SELECT 
    u.id,
    u.surname,
    u.name,
    u.grade,
    u.class,
    COALESCE(att.attendance, 0) AS attendance,
    COALESCE(gr.avg_grade, 0) AS avg_grade,
    ROUND((COALESCE(att.attendance, 0) + COALESCE(gr.avg_grade * 10, 0)) / 2, 2) AS rating
FROM users u
LEFT JOIN (
    SELECT user_id, ROUND(SUM(visit = 'visited') / NULLIF(COUNT(*), 0) * 100, 2) AS attendance
    FROM visits
    GROUP BY user_id
) att ON u.id = att.user_id
LEFT JOIN (
    SELECT user_id, ROUND(AVG(grade_value), 2) AS avg_grade
    FROM grades
    GROUP BY user_id
) gr ON u.id = gr.user_id
ORDER BY rating DESC
LIMIT $per_page OFFSET $offset
";

$result = $conn->query($sql);

// Получаем рейтинги для топ-3
$top3_sql = "
SELECT 
    u.id,
    u.surname,
    u.name,
    u.grade,
    u.class,
    ROUND((COALESCE(att.attendance, 0) + COALESCE(gr.avg_grade * 10, 0)) / 2, 2) AS rating
FROM users u
LEFT JOIN (
    SELECT user_id, ROUND(SUM(visit = 'visited') / NULLIF(COUNT(*), 0) * 100, 2) AS attendance
    FROM visits
    GROUP BY user_id
) att ON u.id = att.user_id
LEFT JOIN (
    SELECT user_id, ROUND(AVG(grade_value), 2) AS avg_grade
    FROM grades
    GROUP BY user_id
) gr ON u.id = gr.user_id
ORDER BY rating DESC
LIMIT 3
";
$top3_result = $conn->query($top3_sql);
$top3 = [];
while ($row = $top3_result->fetch_assoc()) {
    $top3[] = $row;
}
?>

<style>
    /* ===== Изолированные стили страницы рейтинга ===== */
    .rating-page-wrap {
        max-width: 860px;
        margin: 40px auto;
        padding: 0 24px;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        color: #1d1d1f;
        box-sizing: border-box;
    }
    
    .rating-page-wrap * {
        box-sizing: border-box;
    }
    
    /* Header */
    .rating-header {
        text-align: center;
        margin-bottom: 32px;
    }
    
    .rating-header-icon {
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
    
    .rating-header h1 {
        font-size: 32px;
        font-weight: 800;
        letter-spacing: -0.03em;
        margin: 0 0 6px;
    }
    
    .rating-header p {
        font-size: 16px;
        color: #6e6e73;
        margin: 0;
    }
    
    /* Top 3 */
    .top3-grid {
        display: grid;
        grid-template-columns: 1fr 1.3fr 1fr;
        gap: 16px;
        margin-bottom: 24px;
        align-items: end;
    }
    
    .top3-card {
        background: #fff;
        border: 1px solid #e5e5ea;
        border-radius: 16px;
        padding: 24px 20px;
        text-align: center;
        box-shadow: 0 4px 16px rgba(0,0,0,0.04);
        transition: all 0.3s ease;
    }
    
    .top3-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 32px rgba(0,0,0,0.1);
    }
    
    .top3-card.first {
        border-color: #fbbf24;
        padding: 32px 20px;
        box-shadow: 0 8px 32px rgba(251,191,36,0.2);
        background: linear-gradient(180deg, #fff 0%, #fffbeb 100%);
    }
    
    .top3-medal {
        font-size: 40px;
        margin-bottom: 10px;
    }
    
    .top3-name {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 4px;
        letter-spacing: -0.01em;
    }
    
    .top3-class {
        font-size: 13px;
        color: #6e6e73;
        margin-bottom: 10px;
    }
    
    .top3-rating {
        font-size: 28px;
        font-weight: 800;
        color: #0071e3;
        letter-spacing: -0.02em;
    }
    
    .top3-rating-label {
        font-size: 12px;
        color: #6e6e73;
        font-weight: 500;
        margin-top: 4px;
    }
    
    /* Table */
    .rating-card {
        background: #fff;
        border: 1px solid #e5e5ea;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0,0,0,0.04);
        margin-bottom: 20px;
    }
    
    .rating-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .rating-table thead th {
        padding: 16px 20px;
        background: #1d1d1f;
        color: #fff;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        text-align: left;
    }
    
    .rating-table thead th:first-child {
        width: 80px;
    }
    
    .rating-table thead th:last-child {
        width: 120px;
    }
    
    .rating-table tbody tr {
        border-bottom: 1px solid #f0f0f2;
        transition: background 0.15s;
    }
    
    .rating-table tbody tr:hover {
        background: #f8f8fa;
    }
    
    .rating-table tbody tr:last-child {
        border-bottom: none;
    }
    
    .rating-table tbody td {
        padding: 16px 20px;
        font-size: 15px;
    }
    
    .rank-cell {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .rank-num {
        font-weight: 700;
        font-size: 16px;
        width: 24px;
        text-align: center;
    }
    
    .rating-badge {
        padding: 6px 16px;
        border-radius: 100px;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: -0.01em;
    }
    
    .rating-high {
        background: #f0fdf4;
        color: #059669;
    }
    
    .rating-medium {
        background: #fffbeb;
        color: #d97706;
    }
    
    .rating-low {
        background: #fef2f2;
        color: #dc2626;
    }
    
    /* Pagination */
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }
    
    .page-link {
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
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .page-link:hover {
        background: #f0f0f2;
        border-color: #d2d2d7;
    }
    
    .page-link.active {
        background: #0071e3;
        color: #fff;
        border-color: #0071e3;
        box-shadow: 0 4px 12px rgba(0,113,227,0.3);
    }
    
    .page-link.disabled {
        opacity: 0.3;
        pointer-events: none;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .rating-page-wrap {
            margin: 20px auto;
            padding: 0 12px;
        }
        
        .rating-header h1 {
            font-size: 24px;
        }
        
        .rating-header-icon {
            width: 48px;
            height: 48px;
            font-size: 20px;
        }
        
        .top3-grid {
            grid-template-columns: 1fr;
            gap: 10px;
        }
        
        .top3-card.first {
            order: -1;
            padding: 24px 20px;
        }
        
        .rating-table thead th,
        .rating-table tbody td {
            padding: 10px 12px;
            font-size: 13px;
        }
        
        .page-link {
            min-width: 36px;
            height: 36px;
            font-size: 12px;
        }
    }
</style>

<div class="rating-page-wrap">
    <!-- Header -->
    <div class="rating-header">
        <div class="rating-header-icon">
            <i class="fas fa-trophy"></i>
        </div>
        <h1>Рейтинг учеников</h1>
        <p>Лучшие ученики по успеваемости и посещаемости</p>
    </div>

    <!-- Top 3 -->
    <?php if (!empty($top3)): ?>
    <div class="top3-grid">
        <?php
        $order = [1, 0, 2];
        $medals = ['🥇', '🥈', '🥉'];
        foreach ($order as $idx):
            if (!isset($top3[$idx])) continue;
            $student = $top3[$idx];
            $isFirst = $idx === 0;
        ?>
        <div class="top3-card <?= $isFirst ? 'first' : '' ?>">
            <div class="top3-medal"><?= $medals[$idx] ?></div>
            <div class="top3-name"><?= htmlspecialchars($student['surname'] . ' ' . $student['name']) ?></div>
            <div class="top3-class"><?= $student['grade'] . $student['class'] ?> класс</div>
            <div class="top3-rating"><?= number_format($student['rating'], 1) ?></div>
            <div class="top3-rating-label">Рейтинг</div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Table -->
    <div class="rating-card">
        <table class="rating-table">
            <thead>
                <tr>
                    <th>Место</th>
                    <th>ФИО</th>
                    <th>Класс</th>
                    <th>Рейтинг</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result && $result->num_rows > 0) {
                    $rank = $offset + 1;
                    while($row = $result->fetch_assoc()) {
                        $fio = $row["surname"] . " " . $row["name"];
                        $class = $row["grade"] . $row["class"];
                        $rating = $row["rating"] ?? 0;
                        
                        $medal = '';
                        if ($rank == 1) $medal = '🥇';
                        elseif ($rank == 2) $medal = '🥈';
                        elseif ($rank == 3) $medal = '🥉';
                        
                        $ratingClass = 'rating-high';
                        if ($rating < 40) $ratingClass = 'rating-low';
                        elseif ($rating < 70) $ratingClass = 'rating-medium';
                        
                        echo "<tr>
                                <td>
                                    <span class='rank-cell'>
                                        <span class='rank-num'>$rank</span>
                                        <span>$medal</span>
                                    </span>
                                </td>
                                <td><strong>" . htmlspecialchars($fio) . "</strong></td>
                                <td>" . htmlspecialchars($class) . "</td>
                                <td><span class='rating-badge $ratingClass'>" . number_format($rating, 1) . "</span></td>
                              </tr>";
                        $rank++;
                    }
                } else {
                    echo "<tr><td colspan='4' style='text-align:center;padding:32px;color:#86868b;font-size:15px;'>Нет данных</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <a href="?page=<?= max(1, $page - 1) ?>" class="page-link <?= $page <= 1 ? 'disabled' : '' ?>">
            <i class="fas fa-chevron-left" style="font-size:12px;"></i>
        </a>
        
        <?php
        $start_page = max(1, $page - 2);
        $end_page = min($total_pages, $page + 2);
        
        if ($start_page > 1) {
            echo "<a href='?page=1' class='page-link'>1</a>";
            if ($start_page > 2) echo "<span class='page-link disabled'>...</span>";
        }
        
        for ($i = $start_page; $i <= $end_page; $i++) {
            $active = $i == $page ? 'active' : '';
            echo "<a href='?page=$i' class='page-link $active'>$i</a>";
        }
        
        if ($end_page < $total_pages) {
            if ($end_page < $total_pages - 1) echo "<span class='page-link disabled'>...</span>";
            echo "<a href='?page=$total_pages' class='page-link'>$total_pages</a>";
        }
        ?>
        
        <a href="?page=<?= min($total_pages, $page + 1) ?>" class="page-link <?= $page >= $total_pages ? 'disabled' : '' ?>">
            <i class="fas fa-chevron-right" style="font-size:12px;"></i>
        </a>
    </div>
    <?php endif; ?>
</div>