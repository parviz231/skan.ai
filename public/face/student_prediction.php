<?php
session_start();
require_once("config2.php");

$report = "";
$avg_subjects = [];
$good_subjects = [];
$bad_subjects = [];
$overall_avg = 0;
$student_name = "";
$student_class = "";
$has_data = false;
$attendance_percent = 0;
$attendance_total = 0;
$subject_progress = [];
$strong_subject_threshold = 7;
$lowest_bad_subject = '';
$lowest_bad_avg = 0;
$all_grades_count = 0;

// Получаем список классов для выпадающего списка
$classes = $conn->query("SELECT DISTINCT CONCAT(grade, class) AS class_name FROM users ORDER BY class_name");

// Определяем четверть и год
$currentMonth = (int)date('n');
$currentYear = (int)date('Y');
$defaultQuarter = 4;
if ($currentMonth >= 9 && $currentMonth <= 10) {
    $defaultQuarter = 1;
} elseif ($currentMonth >= 11 && $currentMonth <= 12) {
    $defaultQuarter = 2;
} elseif ($currentMonth >= 1 && $currentMonth <= 3) {
    $defaultQuarter = 3;
} elseif ($currentMonth >= 4 && $currentMonth <= 5) {
    $defaultQuarter = 4;
}
$quarter = isset($_REQUEST['quarter']) ? (int)$_REQUEST['quarter'] : $defaultQuarter;
$year    = isset($_REQUEST['year']) ? (int)$_REQUEST['year'] : $currentYear;

$quarters = [
    1 => ["start" => "$year-09-01", "end" => "$year-10-31"],
    2 => ["start" => "$year-11-01", "end" => "$year-12-31"],
    3 => ["start" => "$year-01-01", "end" => "$year-03-31"],
    4 => ["start" => "$year-04-01", "end" => "$year-05-31"]
];
if (!isset($quarters[$quarter])) $quarter = 1;
$startDate = $quarters[$quarter]['start'];
$endDate   = $quarters[$quarter]['end'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $surname = $conn->real_escape_string($_POST["surname"]);
    $name    = $conn->real_escape_string($_POST["name"]);
    $class   = $conn->real_escape_string($_POST["class"]);
    $student_name = $surname . " " . $name;
    $student_class = $class;

    // Ищем ученика
    $u_sql = "SELECT id, surname, name FROM users WHERE surname LIKE '%$surname%' AND name LIKE '%$name%' AND CONCAT(grade, class) = '$class' LIMIT 1";
    $u_res = $conn->query($u_sql);

    if ($u_res && $u_res->num_rows > 0) {
        $student = $u_res->fetch_assoc();
        $user_id = $student['id'];
        $has_data = true;

        // 1. Считаем посещаемость
        $v_sql = "SELECT visit FROM visits WHERE user_id = $user_id AND DATE(time) BETWEEN '$startDate' AND '$endDate'";
        $v_res = $conn->query($v_sql);
        $total_visits = 0;
        $visited = 0;
        while ($v_row = $v_res->fetch_assoc()) {
            if (!empty($v_row['visit'])) {
                $total_visits++;
                if ($v_row['visit'] == 'visited') $visited++;
            }
        }
        $attendance_total = $total_visits;
        $attendance_percent = $total_visits > 0 ? round(($visited / $total_visits) * 100, 1) : 0;

        // 2. Получаем названия предметов
        $all_subjects = [];
        $subj_res = $conn->query("SELECT id, name FROM subjects ORDER BY id ASC");
        while ($s = $subj_res->fetch_assoc()) {
            $all_subjects[$s['id']] = $s['name'];
        }

        // 3. Получаем оценки ученика
        $g_sql = "
            SELECT sub.id AS subject_id, sub.name AS subject, g.grade_value 
            FROM grades g
            JOIN subjects sub ON sub.id = g.subject_id
            WHERE g.user_id = $user_id AND g.grade_date BETWEEN '$startDate' AND '$endDate'
            ORDER BY g.grade_date ASC, g.id ASC
        ";
        $g_res = $conn->query($g_sql);
        
        $all_grades_sum = 0;
        $all_grades_count = 0;
        $subject_grades = [];

        if ($g_res) {
            while ($g_row = $g_res->fetch_assoc()) {
                $val = str_replace(',', '.', $g_row['grade_value']);
                if (is_numeric($val) && $val > 0 && $val <= 10) {
                    $subject_grades[$g_row['subject_id']][] = floatval($val);
                    $all_grades_sum += floatval($val);
                    $all_grades_count++;
                }
            }
        }

        // 4. Формируем список всех предметов
        foreach ($all_subjects as $sub_id => $sub_name) {
            if (isset($subject_grades[$sub_id])) {
                $avg = round(array_sum($subject_grades[$sub_id]) / count($subject_grades[$sub_id]), 2);
                $avg_subjects[$sub_name] = $avg;
                if ($avg >= $strong_subject_threshold) {
                    $good_subjects[$sub_name] = $avg;
                } else {
                    $bad_subjects[$sub_name] = $avg;
                }

                $grades = $subject_grades[$sub_id];
                if (count($grades) >= 2) {
                    $split_at = (int)floor(count($grades) / 2);
                    $earlier_grades = array_slice($grades, 0, $split_at);
                    $recent_grades = array_slice($grades, $split_at);
                    $earlier_avg = array_sum($earlier_grades) / count($earlier_grades);
                    $recent_avg = array_sum($recent_grades) / count($recent_grades);
                    $subject_progress[$sub_name] = [
                        'earlier' => round($earlier_avg, 2),
                        'recent' => round($recent_avg, 2),
                        'change' => round($recent_avg - $earlier_avg, 2),
                    ];
                }
            }
        }

        // 5. Считаем общий средний балл
        if ($all_grades_count > 0) {
            $overall_avg = round($all_grades_sum / $all_grades_count, 2);
        }
        if (!empty($bad_subjects)) {
            asort($bad_subjects);
            $lowest_bad_subject = key($bad_subjects);
            $lowest_bad_avg = current($bad_subjects);
        }

    } else {
        $report = "❌ Нет данных по этому ученику.";
    }
}

require_once("header.php");
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>СКАН — Анализ успеваемости</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --primary: #4f46e5; --secondary: #7c3aed; --success: #10b981; --danger: #ef4444; --warning: #f59e0b;
            --text-primary: #1f2937; --text-secondary: #6b7280; --bg-main: #f9fafb; --bg-card: #ffffff;
            --border-color: #e5e7eb; --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05); --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --radius: 12px; --radius-lg: 16px; --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, sans-serif; background: var(--bg-main); color: var(--text-primary); -webkit-font-smoothing: antialiased; }

        @keyframes fadeUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        .anim-up { animation: fadeUp 0.6s cubic-bezier(0.2, 0.8, 0.2, 1) forwards; opacity: 0; }
        .d-1 { animation-delay: 0.1s; } .d-2 { animation-delay: 0.2s; } .d-3 { animation-delay: 0.3s; } .d-4 { animation-delay: 0.4s; } .d-5 { animation-delay: 0.5s; }

        .container { max-width: 1000px; margin: 0 auto; padding: 40px 20px; }
        .page-header { display: flex; align-items: center; gap: 16px; margin-bottom: 32px; }
        .page-header .icon-wrap { width: 56px; height: 56px; background: linear-gradient(135deg, var(--primary), var(--secondary)); border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: #fff; box-shadow: 0 8px 24px rgba(79, 70, 229, 0.3); }
        .page-header h1 { font-size: 32px; font-weight: 800; color: var(--text-primary); letter-spacing: -0.03em; }
        .page-header h1 span { background: linear-gradient(135deg, var(--primary), var(--secondary)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }

        .card { background: var(--bg-card); border-radius: var(--radius-lg); border: 1px solid var(--border-color); overflow: hidden; box-shadow: var(--shadow-sm); margin-bottom: 24px; transition: var(--transition); }
        .card:hover { box-shadow: var(--shadow-lg); transform: translateY(-2px); }
        .card-header { padding: 20px 24px; background: #f3f4f6; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; gap: 12px; }
        .card-header .header-icon { width: 36px; height: 36px; background: rgba(79, 70, 229, 0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 16px; }
        .card-header h2 { font-size: 18px; font-weight: 700; color: var(--text-primary); margin: 0; }
        .card-body { padding: 24px; }

        .form-row { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr 1fr; gap: 16px; margin-bottom: 24px; }
        .form-group { display: flex; flex-direction: column; gap: 8px; }
        .form-group label { font-size: 13px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; }
        .form-group input, .form-group select { width: 100%; padding: 12px 16px; border: 1px solid var(--border-color); border-radius: var(--radius); font-size: 15px; font-family: 'Inter', sans-serif; background: var(--bg-main); color: var(--text-primary); outline: none; transition: var(--transition); height: 48px; }
        .form-group input:focus, .form-group select:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1); background: #fff; }

        .btn { padding: 12px 32px; background: linear-gradient(135deg, var(--primary), var(--secondary)); color: #fff; border: none; border-radius: var(--radius); font-size: 15px; font-weight: 600; cursor: pointer; transition: var(--transition); font-family: 'Inter', sans-serif; box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3); height: 48px; display: inline-flex; align-items: center; gap: 8px; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(79, 70, 229, 0.4); }

        .student-info { background: linear-gradient(135deg, var(--primary), var(--secondary)); padding: 24px; border-radius: var(--radius-lg); margin-bottom: 24px; font-size: 18px; color: #fff; display: flex; align-items: center; gap: 16px; box-shadow: 0 10px 30px rgba(79, 70, 229, 0.3); }
        .student-info .avatar { width: 56px; height: 56px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: 700; backdrop-filter: blur(10px); }

        .result-box { background: var(--bg-card); border-radius: var(--radius-lg); padding: 24px; margin-bottom: 20px; border-left: 6px solid var(--primary); box-shadow: var(--shadow-sm); transition: var(--transition); }
        .result-box:hover { box-shadow: var(--shadow-lg); transform: translateX(4px); }
        .result-box h3 { margin: 0 0 16px 0; font-size: 18px; font-weight: 700; color: var(--text-primary); display: flex; align-items: center; gap: 10px; }
        .result-box ul { margin: 0; padding-left: 20px; }
        .result-box ul li { padding: 8px 0; font-size: 15px; color: var(--text-secondary); border-bottom: 1px solid var(--border-color); }
        .result-box ul li:last-child { border-bottom: none; }
        .result-box ul li strong { color: var(--text-primary); }

        .good-box { border-left-color: var(--success); background: #f0fdf4; }
        .bad-box { border-left-color: var(--danger); background: #fef2f2; }
        .recommend-box { border-left-color: var(--warning); background: #fffbeb; }
        .overall-box { border-left-color: var(--primary); background: #f8fafc; }

        .overall-score { font-size: 42px; font-weight: 800; color: var(--primary); letter-spacing: -0.03em; }
        .chart-container { max-width: 100%; margin: 0 auto; padding: 20px 0; position: relative; height: 450px; }

        @media (max-width: 768px) {
            .container { padding: 20px 16px; }
            .page-header h1 { font-size: 24px; }
            .form-row { grid-template-columns: 1fr; }
            .student-info { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="page-header anim-up">
        <span class="icon-wrap"><i class="fas fa-chart-line"></i></span>
        <h1>Анализ <span>успеваемости</span></h1>
    </div>

    <div class="card anim-up d-1">
        <div class="card-header"><span class="header-icon"><i class="fas fa-search"></i></span><h2>Поиск ученика</h2></div>
        <div class="card-body">
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label>Фамилия</label>
                        <input type="text" name="surname" value="<?= htmlspecialchars($_POST['surname'] ?? '') ?>" required placeholder="Фамилия">
                    </div>
                    <div class="form-group">
                        <label>Имя</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required placeholder="Имя">
                    </div>
                    <div class="form-group">
                        <label>Класс</label>
                        <select name="class" required>
                            <option value="">— выберите —</option>
                            <?php if ($classes): while($row = $classes->fetch_assoc()): $sel = (isset($_POST['class']) && $_POST['class'] == $row['class_name']) ? 'selected' : ''; ?>
                                <option value="<?= $row['class_name'] ?>" <?= $sel ?>><?= $row['class_name'] ?></option>
                            <?php endwhile; endif; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Четверть</label>
                        <select name="quarter">
                            <?php for($q=1; $q<=4; $q++): ?>
                                <option value="<?= $q ?>" <?= $quarter == $q ? 'selected' : '' ?>><?= $q ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Год</label>
                        <input type="number" name="year" value="<?= $year ?>" min="2020" max="2030">
                    </div>
                </div>
                <button type="submit" class="btn"><i class="fas fa-chart-bar"></i> Показать анализ</button>
            </form>
        </div>
    </div>

    <?php if ($has_data): ?>
        <div class="student-info anim-up d-2">
            <div class="avatar"><?= mb_substr($student_name, 0, 1) ?></div>
            <div>
                <strong><?= htmlspecialchars($student_name) ?></strong>
                <span style="opacity:0.8;"> • <?= htmlspecialchars($student_class) ?> класс • <?= $quarter ?>-я четверть</span>
            </div>
        </div>

        <?php if ($overall_avg > 0): ?>
        <div class="result-box overall-box anim-up d-3">
            <h3><i class="fas fa-star"></i> Общая успеваемость (Средний балл)</h3>
            <div class="overall-score"><?= $overall_avg ?> <span style="font-size:20px; color:var(--text-secondary); font-weight:600;">/ 10</span></div>
        </div>
        <?php endif; ?>

        <?php if ($all_grades_count === 0): ?>
        <div class="result-box overall-box anim-up d-3">
            <h3><i class="fas fa-info-circle"></i> Пока нет оценок за выбранную четверть</h3>
            <p style="margin:0; color:var(--text-secondary);">Выберите другую четверть или проверьте журнал позже. Сильные и слабые предметы появятся, когда будут внесены оценки.</p>
        </div>
        <?php endif; ?>

        <?php if (!empty($good_subjects)): ?>
        <div class="result-box good-box anim-up d-3">
            <h3><i class="fas fa-check-circle"></i> Сильные стороны (≥ <?= $strong_subject_threshold ?>)</h3>
            <ul>
                <?php foreach($good_subjects as $sub => $avg): ?>
                    <li><strong><?= htmlspecialchars($sub) ?></strong> — средний балл <strong><?= $avg ?></strong></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if (!empty($bad_subjects)): ?>
        <div class="result-box bad-box anim-up d-4">
            <h3><i class="fas fa-exclamation-circle"></i> Предметы, которые нужно подтянуть (&lt; <?= $strong_subject_threshold ?>)</h3>
            <ul>
                <?php foreach($bad_subjects as $sub => $avg): ?>
                    <li><strong><?= htmlspecialchars($sub) ?></strong> — средний балл <strong><?= $avg ?></strong>. Повторите последние темы и разберите ошибки в работах по этому предмету.</li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="result-box recommend-box anim-up d-4">
            <h3><i class="fas fa-lightbulb"></i> Следующие шаги</h3>
            <ul>
                <?php if ($all_grades_count === 0): ?>
                    <li>Проверьте, правильно ли выбрана четверть; если оценок действительно ещё нет, попросите учителя обновить журнал.</li>
                <?php elseif (!empty($bad_subjects)): ?>
                    <li>Начните с предмета с самым низким средним баллом: <strong><?= htmlspecialchars($lowest_bad_subject) ?> (<?= $lowest_bad_avg ?>)</strong>.</li>
                <?php else: ?>
                    <li>Продолжайте в том же темпе и закрепляйте темы, по которым уже есть хорошие результаты.</li>
                <?php endif; ?>
                <li>Посещаемость за выбранную четверть: <strong><?= $attendance_total > 0 ? $attendance_percent . '%' : 'нет данных' ?></strong><?php if ($attendance_total > 0 && $attendance_percent < 80): ?> — старайтесь не пропускать занятия, чтобы не отставать от новых тем.<?php endif; ?></li>
                <?php if ($all_grades_count > 0): ?>
                    <li>Цель на следующий период: средний балл не ниже <strong><?= $strong_subject_threshold ?></strong> по каждому предмету.</li>
                <?php endif; ?>
            </ul>
        </div>

        <?php if (!empty($subject_progress)): ?>
        <div class="result-box overall-box anim-up d-4">
            <h3><i class="fas fa-chart-line"></i> Динамика оценок</h3>
            <p style="margin: 0 0 12px; color: var(--text-secondary);">Сравнение более ранних оценок с последними оценками за четверть:</p>
            <ul>
                <?php foreach ($subject_progress as $sub => $progress): ?>
                    <?php $change = $progress['change']; ?>
                    <li>
                        <strong><?= htmlspecialchars($sub) ?></strong>:
                        <?= $progress['earlier'] ?> → <?= $progress['recent'] ?>
                        <?php if ($change > 0): ?>
                            <strong style="color:var(--success);"><i class="fas fa-arrow-up"></i> +<?= $change ?></strong>
                        <?php elseif ($change < 0): ?>
                            <strong style="color:var(--danger);"><i class="fas fa-arrow-down"></i> <?= $change ?></strong>
                        <?php else: ?>
                            <strong>без изменений</strong>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if (!empty($avg_subjects)): ?>
        <div class="card anim-up d-5">
            <div class="card-header"><span class="header-icon"><i class="fas fa-chart-bar"></i></span><h2>Диаграмма успеваемости</h2></div>
            <div class="card-body">
                <div class="chart-container"><canvas id="chart"></canvas></div>
            </div>
        </div>
        <?php endif; ?>
        
    <?php elseif ($_SERVER["REQUEST_METHOD"] == "POST" && !$has_data): ?>
        <div class="result-box bad-box anim-up d-2">
            <h3><i class="fas fa-times-circle"></i> Данных нет</h3>
            <p style="margin: 0; color: #991b1b;">За выбранную четверть оценок не найдено или ученик не существует.</p>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($avg_subjects)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('chart').getContext('2d');
    const labels = <?= json_encode(array_keys($avg_subjects), JSON_UNESCAPED_UNICODE) ?>;
    const data = <?= json_encode(array_values($avg_subjects)) ?>;
    
    // Градиенты
    let gradientBlue = ctx.createLinearGradient(0, 0, 600, 0);
    gradientBlue.addColorStop(0, '#6366f1');
    gradientBlue.addColorStop(1, '#8b5cf6');

    let gradientRed = ctx.createLinearGradient(0, 0, 600, 0);
    gradientRed.addColorStop(0, '#ef4444');
    gradientRed.addColorStop(1, '#f87171');

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Средний балл',
                data: data,
                backgroundColor: data.map(v => v >= <?= $strong_subject_threshold ?> ? gradientBlue : gradientRed),
                borderRadius: 8,
                borderSkipped: false,
                barThickness: 22
            }]
        },
        options: {
            indexAxis: 'y', // ГОРИЗОНТАЛЬНЫЙ ГРАФИК
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 1500, easing: 'easeOutQuart' },
            plugins: { 
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(17, 24, 39, 0.9)',
                    titleFont: { size: 14, family: 'Inter' },
                    bodyFont: { size: 14, weight: 'bold', family: 'Inter' },
                    padding: 12,
                    cornerRadius: 8,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return 'Балл: ' + context.raw;
                        }
                    }
                }
            },
            scales: {
                x: { 
                    beginAtZero: true, 
                    max: 10, 
                    ticks: { 
                        stepSize: 1, // ВСЕ ЧИСЛА ОТ 0 ДО 10
                        font: { size: 12, family: 'Inter' } 
                    }, 
                    grid: { color: '#f3f4f6' } 
                },
                y: { 
                    grid: { display: false },
                    ticks: {
                        font: { size: 13, weight: '600', family: 'Inter' },
                        color: '#4b5563'
                    }
                }
            }
        }
    });
});
</script>
<?php endif; ?>

</body>
</html>