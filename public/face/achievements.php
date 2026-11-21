<?php
require_once("header.php");
require_once("config2.php");

$selected_class = $_GET['class'] ?? 'all';
$selected_year  = $_GET['year'] ?? 'all';
$selected_place = $_GET['place'] ?? 'all';

/* ---------- SQL ---------- */
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

$params = [];
$types = "";

if ($selected_class != 'all') { $sql .= " AND u.class = ?"; $params[] = $selected_class; $types .= "s"; }
if ($selected_year  != 'all') { $sql .= " AND a.school_year = ?"; $params[] = $selected_year; $types .= "s"; }
if ($selected_place != 'all') { $sql .= " AND a.place = ?"; $params[] = $selected_place; $types .= "s"; }

$sql .= " ORDER BY a.competition_type, CAST(a.place AS UNSIGNED), a.place;";

$stmt = $conn->prepare($sql);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

/* ---------- Группировка ---------- */
$groups = ['school' => [], 'republic' => [], 'international' => [], 'sport' => []];
$yearStats = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $name = mb_strtolower($row['competition_name'], 'UTF-8');
        $type = mb_strtolower($row['competition_type'], 'UTF-8');
        $all  = $name . ' ' . $type;

        if (mb_strpos($all, 'спорт', 0, 'UTF-8') !== false
         || mb_strpos($all, 'футбол', 0, 'UTF-8') !== false
         || mb_strpos($all, 'теннис', 0, 'UTF-8') !== false
         || mb_strpos($all, 'шахмат', 0, 'UTF-8') !== false
         || mb_strpos($all, 'плаван', 0, 'UTF-8') !== false
         || mb_strpos($all, 'эстафет', 0, 'UTF-8') !== false) {
            $groups['sport'][] = $row;
        } elseif (mb_strpos($all, 'международ', 0, 'UTF-8') !== false) {
            $groups['international'][] = $row;
        } elseif (mb_strpos($all, 'республик', 0, 'UTF-8') !== false
               || mb_strpos($all, 'городск', 0, 'UTF-8') !== false) {
            $groups['republic'][] = $row;
        } else {
            $groups['school'][] = $row;
        }

        $y = $row['school_year'];
        if (!isset($yearStats[$y])) $yearStats[$y] = 0;
        $yearStats[$y]++;
    }
}
ksort($yearStats);

/* ---------- Списки для фильтров ---------- */
$classes = []; $years = []; $places = [];
$classRes = $conn->query("SELECT DISTINCT class FROM users ORDER BY class");
while ($r = $classRes->fetch_assoc()) $classes[] = $r['class'];
$yearRes = $conn->query("SELECT DISTINCT school_year FROM achievements ORDER BY school_year DESC");
while ($r = $yearRes->fetch_assoc()) $years[] = $r['school_year'];
$placeRes = $conn->query("SELECT DISTINCT place FROM achievements ORDER BY CAST(place AS UNSIGNED), place ASC");
while ($r = $placeRes->fetch_assoc()) $places[] = $r['place'];

/* ---------- Итоговая статистика ---------- */
$stats = [
    'Школьные'        => count($groups['school']),
    'Республиканские' => count($groups['republic']),
    'Международные'   => count($groups['international']),
    'Спортивные'      => count($groups['sport']),
];
$totalAchievements = array_sum($stats);

/* ---------- Хелперы ---------- */
function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function place_meta($place) {
    $p = trim((string)$place);
    $num = (int)preg_replace('/[^0-9]/', '', $p);
    if ($num === 1) return ['class' => 'gold',   'icon' => 'fa-crown',       'label' => 'Победитель'];
    if ($num === 2) return ['class' => 'silver', 'icon' => 'fa-medal',       'label' => 'Призёр'];
    if ($num === 3) return ['class' => 'bronze', 'icon' => 'fa-award',       'label' => 'Призёр'];
    return ['class' => 'plain', 'icon' => 'fa-star', 'label' => 'Участник'];
}

$typeMeta = [
    'school'        => ['icon' => 'fa-school',  'color' => '#4f46e5', 'color2' => '#7c3aed', 'label' => 'Школьные олимпиады',        'img' => 'img/d1.jpg'],
    'republic'      => ['icon' => 'fa-flag',    'color' => '#7c3aed', 'color2' => '#c026d3', 'label' => 'Республиканские олимпиады', 'img' => 'img/d4.jpg'],
    'international' => ['icon' => 'fa-globe',   'color' => '#10b981', 'color2' => '#059669', 'label' => 'Международные олимпиады',   'img' => 'img/d3.jpg'],
    'sport'         => ['icon' => 'fa-futbol',  'color' => '#f59e0b', 'color2' => '#ef4444', 'label' => 'Спортивные соревнования',   'img' => 'img/d2.jpg'],
];
?>

<!doctype html>
<html class="no-js" lang="ru">
<head>
    <meta charset="utf-8">
    <title>СКАН — Достижения учеников</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/x-icon" href="img/logo-scan.png">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <style>
        :root {
            --c-primary: #4f46e5; --c-primary-2: #7c3aed; --c-emerald: #10b981;
            --c-amber: #f59e0b; --c-pink: #c026d3; --c-text: #0f172a; --c-muted: #64748b;
            --c-line: #e2e8f0; --c-bg: #f8fafc; --radius: 20px;
            --shadow-sm: 0 1px 2px rgba(15, 23, 42, 0.04), 0 1px 3px rgba(15, 23, 42, 0.06);
            --shadow-md: 0 4px 12px rgba(15, 23, 42, 0.06), 0 2px 6px rgba(15, 23, 42, 0.04);
            --shadow-lg: 0 20px 40px -12px rgba(79, 70, 229, 0.18), 0 8px 16px -8px rgba(15, 23, 42, 0.08);
            --shadow-glow: 0 0 0 1px rgba(79, 70, 229, 0.08), 0 20px 50px -12px rgba(79, 70, 229, 0.35);
        }
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif !important; background: var(--c-bg) !important; color: var(--c-text) !important; margin: 0; padding: 0; -webkit-font-smoothing: antialiased; position: relative; overflow-x: hidden; min-height: 100vh; }
        
        body::before, body::after { content: ''; position: fixed; border-radius: 50%; filter: blur(120px); opacity: 0.35; pointer-events: none; z-index: 0; }
        body::before { width: 560px; height: 560px; background: radial-gradient(circle, #818cf8, transparent 70%); top: -160px; left: -160px; animation: float1 18s ease-in-out infinite; }
        body::after { width: 620px; height: 620px; background: radial-gradient(circle, #c084fc, transparent 70%); bottom: -220px; right: -180px; animation: float2 22s ease-in-out infinite; }
        @keyframes float1 { 0%, 100% { transform: translate(0, 0) scale(1); } 50% { transform: translate(60px, 40px) scale(1.1); } }
        @keyframes float2 { 0%, 100% { transform: translate(0, 0) scale(1); } 50% { transform: translate(-50px, -40px) scale(1.08); } }

        .custom-container { max-width: 1320px; margin: 0 auto; padding: 40px 24px 60px; width: 100%; position: relative; z-index: 1; }

        /* HERO */
        .hero { display: flex; justify-content: space-between; align-items: flex-end; gap: 24px; margin-bottom: 32px; flex-wrap: wrap; }
        .hero-left { display: flex; align-items: center; gap: 20px; }
        .hero-icon { width: 68px; height: 68px; border-radius: 20px; background: linear-gradient(135deg, var(--c-primary), var(--c-primary-2)); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 30px; box-shadow: var(--shadow-glow); flex-shrink: 0; }
        .hero-title { font-size: 36px; font-weight: 800; letter-spacing: -0.03em; margin: 0 0 6px; color: var(--c-text); }
        .hero-title span { background: linear-gradient(135deg, var(--c-primary), var(--c-primary-2) 60%, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .hero-subtitle { font-size: 15px; color: var(--c-muted); margin: 0; font-weight: 500; }
        .hero-badge { display: inline-flex; align-items: center; gap: 8px; background: #fff; padding: 10px 18px; border-radius: 100px; font-size: 13px; font-weight: 700; color: var(--c-primary); box-shadow: var(--shadow-md); border: 1px solid var(--c-line); }
        .hero-badge .pulse-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--c-emerald); animation: pulse 2s infinite; }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); } 70% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); } 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); } }

        /* KPI */
        .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; margin-bottom: 32px; }
        .kpi { background: #fff; border-radius: var(--radius); padding: 22px; border: 1px solid var(--c-line); box-shadow: var(--shadow-sm); position: relative; overflow: hidden; transition: transform 0.4s, box-shadow 0.4s; }
        .kpi::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: var(--kpi-color, var(--c-primary)); }
        .kpi:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); }
        .kpi-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
        .kpi-icon { width: 40px; height: 40px; border-radius: 12px; background: color-mix(in srgb, var(--kpi-color) 12%, #fff); color: var(--kpi-color); display: flex; align-items: center; justify-content: center; font-size: 16px; }
        .kpi-label { font-size: 11px; font-weight: 700; color: var(--c-muted); text-transform: uppercase; margin: 0 0 6px; }
        .kpi-value { font-family: 'Manrope', sans-serif; font-size: 32px; font-weight: 800; color: var(--c-text); line-height: 1; }
        .kpi-value small { font-size: 13px; color: var(--c-muted); font-weight: 600; margin-left: 6px; }
        .kpi-bar { height: 6px; border-radius: 100px; background: #f1f5f9; margin-top: 14px; overflow: hidden; }
        .kpi-bar > span { display: block; height: 100%; background: var(--kpi-color); border-radius: 100px; width: 0; transition: width 1.2s; }

        /* FILTER */
        .filter-card { background: #fff; border-radius: var(--radius); border: 1px solid var(--c-line); box-shadow: var(--shadow-md); margin-bottom: 32px; overflow: hidden; }
        .filter-head { display: flex; align-items: center; gap: 12px; padding: 18px 26px; border-bottom: 1px solid var(--c-line); background: linear-gradient(180deg, #fbfcfe, #fff); }
        .filter-head .fi { width: 36px; height: 36px; background: linear-gradient(135deg, rgba(79,70,229,0.12), rgba(124,58,237,0.12)); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--c-primary); font-size: 15px; }
        .filter-head h2 { font-size: 16px; font-weight: 800; margin: 0; }
        .filter-head .active-count { margin-left: auto; font-size: 12px; font-weight: 700; color: var(--c-primary); background: rgba(79,70,229,0.1); padding: 6px 12px; border-radius: 100px; }
        .filter-body { padding: 24px 26px; }
        .filter-row { display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 16px; align-items: end; }
        .fg { display: flex; flex-direction: column; gap: 8px; }
        .fg label { font-size: 11px; font-weight: 700; color: var(--c-muted); text-transform: uppercase; margin: 0; }
        .select-wrap { position: relative; }
        .select-wrap select { width: 100%; padding: 12px 40px 12px 16px; border: 1.5px solid var(--c-line); border-radius: 12px; font-size: 14px; font-weight: 500; background: #fbfcfe; height: 48px; cursor: pointer; outline: none; appearance: none; transition: 0.2s; }
        .select-wrap select:focus { border-color: var(--c-primary); box-shadow: 0 0 0 4px rgba(79,70,229,0.12); background: #fff; }
        .select-wrap::after { content: '\f107'; font-family: 'Font Awesome 6 Free'; font-weight: 900; position: absolute; right: 16px; top: 50%; transform: translateY(-50%); color: var(--c-muted); pointer-events: none; font-size: 12px; }
        .btn-apply { height: 48px; padding: 0 26px; background: linear-gradient(135deg, var(--c-primary), var(--c-primary-2)); color: #fff; border: none; border-radius: 12px; font-size: 14px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 8px 20px -6px rgba(79,70,229,0.45); transition: 0.3s; white-space: nowrap; }
        .btn-apply:hover { transform: translateY(-2px); box-shadow: 0 12px 26px -6px rgba(79,70,229,0.55); }
        .btn-reset { height: 48px; padding: 0 18px; background: #fff; color: var(--c-muted); border: 1.5px solid var(--c-line); border-radius: 12px; font-size: 14px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; transition: 0.2s; }
        .btn-reset:hover { background: #f8fafc; color: var(--c-text); }

        /* CHARTS */
        .charts-grid { display: grid; grid-template-columns: 1fr 1.3fr; gap: 24px; margin-bottom: 40px; }
        .chart-card { background: #fff; border-radius: var(--radius); border: 1px solid var(--c-line); box-shadow: var(--shadow-md); padding: 26px; }
        .chart-head { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; gap: 16px; }
        .chart-head h3 { font-size: 17px; font-weight: 800; margin: 0 0 4px; }
        .chart-head p { font-size: 13px; color: var(--c-muted); margin: 0; font-weight: 500; }
        .chart-tag { font-size: 11px; font-weight: 700; padding: 6px 12px; border-radius: 100px; background: rgba(79,70,229,0.08); color: var(--c-primary); white-space: nowrap; }
        .chart-canvas-wrap { position: relative; height: 280px; }
        .doughnut-center { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; pointer-events: none; }
        .doughnut-center .num { font-family: 'Manrope', sans-serif; font-size: 34px; font-weight: 800; color: var(--c-text); line-height: 1; }
        .doughnut-center .lbl { font-size: 11px; font-weight: 700; color: var(--c-muted); text-transform: uppercase; margin-top: 4px; }

        /* SECTION */
        .section-block { margin-bottom: 44px; }
        .section-head { display: flex; align-items: center; gap: 14px; margin-bottom: 20px; padding: 0 4px; }
        .section-icon { width: 46px; height: 46px; border-radius: 14px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 18px; background: linear-gradient(135deg, var(--s-color, var(--c-primary)), var(--s-color2, var(--c-primary-2))); box-shadow: 0 10px 22px -10px var(--s-color, var(--c-primary)); }
        .section-head h2 { font-size: 22px; font-weight: 800; margin: 0; letter-spacing: -0.02em; }
        .section-count { background: rgba(15, 23, 42, 0.06); color: var(--c-text); padding: 5px 14px; border-radius: 100px; font-size: 13px; font-weight: 700; }

        /* CARDS */
        .cards-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
        .ach-card { background: #fff; border-radius: var(--radius); border: 1px solid var(--c-line); box-shadow: var(--shadow-sm); overflow: hidden; display: flex; flex-direction: column; transition: transform 0.4s, box-shadow 0.4s, border-color 0.3s; position: relative; }
        .ach-card:hover { transform: translateY(-6px); box-shadow: var(--shadow-lg); border-color: rgba(79,70,229,0.25); }
        .ach-image { position: relative; height: 170px; overflow: hidden; background: #eef2ff; }
        .ach-image img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.6s; }
        .ach-card:hover .ach-image img { transform: scale(1.08); }
        .ach-image::after { content: ''; position: absolute; inset: 0; background: linear-gradient(180deg, transparent 45%, rgba(15,23,42,0.55)); }
        .ach-medal { position: absolute; top: 14px; right: 14px; display: inline-flex; align-items: center; gap: 6px; padding: 7px 13px; border-radius: 100px; font-size: 11px; font-weight: 800; text-transform: uppercase; backdrop-filter: blur(10px); z-index: 2; box-shadow: 0 4px 14px rgba(0,0,0,0.2); }
        .ach-medal.gold { background: linear-gradient(135deg, #fde68a, #f59e0b); color: #78350f; }
        .ach-medal.silver { background: linear-gradient(135deg, #e2e8f0, #94a3b8); color: #1e293b; }
        .ach-medal.bronze { background: linear-gradient(135deg, #fed7aa, #d97706); color: #7c2d12; }
        .ach-medal.plain { background: rgba(255,255,255,0.9); color: #3730a3; }
        .ach-place-text { position: absolute; bottom: 14px; left: 16px; color: #fff; z-index: 2; font-size: 12px; font-weight: 700; display: flex; align-items: center; gap: 6px; text-shadow: 0 1px 4px rgba(0,0,0,0.4); }
        .ach-body { padding: 18px; display: flex; flex-direction: column; flex: 1; }
        .ach-title { font-size: 14px; font-weight: 700; color: var(--c-text); line-height: 1.4; margin-bottom: 12px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; min-height: 40px; }
        .ach-info { display: flex; flex-direction: column; gap: 6px; margin-bottom: 14px; }
        .ach-info-row { display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: var(--c-muted); font-weight: 500; }
        .ach-info-row i { width: 20px; height: 20px; background: #f1f5f9; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 10px; color: var(--c-primary); }
        .ach-info-row strong { color: var(--c-text); font-weight: 700; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .ach-footer { margin-top: auto; display: flex; align-items: center; justify-content: space-between; padding-top: 12px; border-top: 1px solid #f1f5f9; }
        .ach-year { font-size: 11px; font-weight: 700; color: var(--c-muted); background: #f8fafc; padding: 4px 10px; border-radius: 6px; }
        .ach-status { font-size: 11px; font-weight: 800; color: var(--c-primary); text-transform: uppercase; }

        /* BUTTONS */
        .show-more-wrap { display: flex; justify-content: center; margin-top: 28px; }
        
        /* ОБНОВЛЕННАЯ КНОПКА ПОКАЗАТЬ ЕЩЕ */
        .show-more-btn {
            padding: 14px 32px;
            background: linear-gradient(135deg, #f8fafc, #ffffff);
            color: var(--c-primary);
            border: 1.5px solid #cbd5e1;
            border-radius: 100px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .show-more-btn:hover {
            border-color: var(--c-primary);
            background: #ffffff;
            color: var(--c-primary);
            box-shadow: 0 8px 24px rgba(79,70,229,0.15);
            transform: translateY(-2px);
        }
        .show-more-btn i {
            font-size: 12px;
            transition: transform 0.3s;
        }
        .show-more-btn:hover i {
            transform: translateY(2px);
        }

        /* EMPTY */
        .empty-state { text-align: center; padding: 56px 24px; background: #fff; border: 1.5px dashed var(--c-line); border-radius: var(--radius); }
        .empty-state .es-icon { width: 64px; height: 64px; margin: 0 auto 16px; border-radius: 18px; background: #f8fafc; display: flex; align-items: center; justify-content: center; color: #cbd5e1; font-size: 26px; }
        .empty-state h4 { font-size: 15px; font-weight: 700; margin: 0 0 4px; }
        .empty-state p { font-size: 13px; color: var(--c-muted); margin: 0; }

        /* ANIMATIONS & RESPONSIVE */
        .fade-in { opacity: 0; transform: translateY(24px); transition: opacity 0.7s, transform 0.7s; }
        .fade-in.in { opacity: 1; transform: translateY(0); }

        @media (max-width: 1100px) { .cards-grid { grid-template-columns: repeat(3, 1fr); } .kpi-grid { grid-template-columns: repeat(2, 1fr); } .charts-grid { grid-template-columns: 1fr; } }
        @media (max-width: 820px) { .filter-row { grid-template-columns: 1fr 1fr; } .btn-apply, .btn-reset { grid-column: span 2; justify-content: center; } }
        @media (max-width: 768px) { .custom-container { padding: 24px 16px 40px; } .cards-grid { grid-template-columns: repeat(2, 1fr); gap: 14px; } .hero-title { font-size: 26px; } .hero-icon { width: 56px; height: 56px; font-size: 24px; } .kpi-grid { gap: 12px; } .kpi { padding: 16px; } .kpi-value { font-size: 26px; } .filter-body { padding: 18px; } .chart-card { padding: 18px; } .chart-canvas-wrap { height: 240px; } .doughnut-center .num { font-size: 28px; } .section-head h2 { font-size: 18px; } }
        @media (max-width: 480px) { .cards-grid { grid-template-columns: 1fr; } .kpi-grid { grid-template-columns: 1fr; } .filter-row { grid-template-columns: 1fr; } .btn-apply, .btn-reset { grid-column: auto; } .hero-badge { display: none; } }
    </style>
</head>
<body>

<div class="custom-container">
    <!-- HERO -->
    <div class="hero fade-in">
        <div class="hero-left">
            <div class="hero-icon"><i class="fas fa-trophy"></i></div>
            <div>
                <h1 class="hero-title">Достижения <span>учеников</span></h1>
                <p class="hero-subtitle">Олимпиады, конкурсы и спортивные победы школы</p>
            </div>
        </div>
        <div class="hero-badge">
            <span class="pulse-dot"></span>
            Всего достижений: <?= (int)$totalAchievements ?>
        </div>
    </div>

    <!-- KPI -->
    <div class="kpi-grid fade-in">
        <?php
        $kpiMap = [
            'school'        => ['label' => 'Школьные',        'color' => '#4f46e5'],
            'republic'      => ['label' => 'Республиканские', 'color' => '#7c3aed'],
            'international' => ['label' => 'Международные',   'color' => '#10b981'],
            'sport'         => ['label' => 'Спортивные',      'color' => '#f59e0b'],
        ];
        foreach ($kpiMap as $key => $meta):
            $val = count($groups[$key]);
            $pct = $totalAchievements > 0 ? round($val / $totalAchievements * 100) : 0;
        ?>
        <div class="kpi" style="--kpi-color: <?= $meta['color'] ?>">
            <div class="kpi-head">
                <div class="kpi-icon"><i class="fas <?= $typeMeta[$key]['icon'] ?>"></i></div>
                <span class="kpi-label"><?= e($meta['label']) ?></span>
            </div>
            <div class="kpi-value" data-count="<?= $val ?>"><?= $val ?><small>шт.</small></div>
            <div class="kpi-bar"><span data-width="<?= $pct ?>"></span></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- FILTER -->
    <div class="filter-card fade-in">
        <div class="filter-head">
            <span class="fi"><i class="fas fa-sliders"></i></span>
            <h2>Фильтры</h2>
            <?php
            $activeCount = 0;
            if ($selected_class != 'all') $activeCount++;
            if ($selected_year  != 'all') $activeCount++;
            if ($selected_place != 'all') $activeCount++;
            ?>
            <?php if ($activeCount > 0): ?>
                <span class="active-count"><i class="fas fa-circle-check"></i> Активно: <?= $activeCount ?></span>
            <?php endif; ?>
        </div>
        <div class="filter-body">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="fg">
                        <label>Класс</label>
                        <div class="select-wrap">
                            <select name="class">
                                <option value="all">Все классы</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?= e($class) ?>" <?= ($selected_class == $class) ? 'selected' : '' ?>><?= e($class) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="fg">
                        <label>Учебный год</label>
                        <div class="select-wrap">
                            <select name="year">
                                <option value="all">Все годы</option>
                                <?php foreach ($years as $y): ?>
                                    <option value="<?= e($y) ?>" <?= ($selected_year == $y) ? 'selected' : '' ?>><?= e($y) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="fg">
                        <label>Место</label>
                        <div class="select-wrap">
                            <select name="place">
                                <option value="all">Все места</option>
                                <?php foreach ($places as $p): ?>
                                    <option value="<?= e($p) ?>" <?= ($selected_place == $p) ? 'selected' : '' ?>><?= e($p) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div style="display:flex; gap:10px;">
                        <button type="submit" class="btn-apply"><i class="fas fa-check"></i> Применить</button>
                        <?php if ($activeCount > 0): ?>
                            <a href="?" class="btn-reset"><i class="fas fa-rotate-left"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- CHARTS -->
    <div class="charts-grid">
        <div class="chart-card fade-in">
            <div class="chart-head">
                <div><h3>Распределение по типам</h3><p>Доля каждой категории в общем числе</p></div>
                <span class="chart-tag"><?= (int)$totalAchievements ?> всего</span>
            </div>
            <div class="chart-canvas-wrap">
                <canvas id="chartTypes"></canvas>
                <div class="doughnut-center">
                    <div class="num" data-count="<?= (int)$totalAchievements ?>">0</div>
                    <div class="lbl">достижений</div>
                </div>
            </div>
        </div>
        <div class="chart-card fade-in">
            <div class="chart-head">
                <div><h3>Динамика по годам</h3><p>Количество достижений за каждый учебный год</p></div>
                <span class="chart-tag" id="yearPeakTag">—</span>
            </div>
            <div class="chart-canvas-wrap"><canvas id="chartYears"></canvas></div>
        </div>
    </div>

    <!-- SECTIONS -->
    <?php
    function render_section($key, $title, $data, $meta, $typeMeta) {
        $count = count($data);
        $color = $typeMeta[$key]['color'];
        $color2 = $typeMeta[$key]['color2'];
        $icon = $typeMeta[$key]['icon'];
        $img = $typeMeta[$key]['img'];
        ?>
        <div class="section-block fade-in">
            <div class="section-head" style="--s-color: <?= $color ?>; --s-color2: <?= $color2 ?>;">
                <div class="section-icon"><i class="fas <?= $icon ?>"></i></div>
                <h2><?= e($title) ?></h2>
                <span class="section-count"><?= $count ?></span>
            </div>

            <?php if ($count > 0): ?>
                <div class="cards-grid" id="block-<?= $key ?>">
                    <?php foreach ($data as $i => $row):
                        $hidden = ($i >= 4) ? 'hidden-card' : '';
                        $pm = place_meta($row['place']);
                        $num = (int)preg_replace('/[^0-9]/', '', $row['place']);
                        $placeLabel = $num > 0 ? $num . ' место' : $row['place'];
                    ?>
                    <div class="ach-card <?= $hidden ?>" style="display: <?= $hidden ? 'none' : 'flex' ?>;">
                        <div class="ach-image">
                            <img src="<?= e($img) ?>" alt="" loading="lazy">
                            <div class="ach-medal <?= $pm['class'] ?>"><i class="fas <?= $pm['icon'] ?>"></i> <?= e($pm['label']) ?></div>
                            <div class="ach-place-text"><i class="fas fa-medal"></i> <?= e($placeLabel) ?></div>
                        </div>
                        <div class="ach-body">
                            <div class="ach-title" title="<?= e($row['competition_name']) ?>"><?= e($row['competition_name']) ?></div>
                            <div class="ach-info">
                                <div class="ach-info-row"><i class="fas fa-user"></i><strong><?= e($row['student_name']) ?> <?= e($row['student_surname']) ?></strong></div>
                                <div class="ach-info-row"><i class="fas fa-users"></i><span><?= e($row['student_class']) ?> класс</span></div>
                                <div class="ach-info-row"><i class="fas fa-calendar"></i><span><?= e($row['school_year']) ?></span></div>
                            </div>
                            <div class="ach-footer">
                                <span class="ach-year"><?= e($row['school_year']) ?></span>
                                <span class="ach-status"><?= e($pm['label']) ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if ($count > 4): ?> 
                    <div class="show-more-wrap">
                        <button class="show-more-btn" data-target="block-<?= $key ?>">
                            <i class="fas fa-chevron-down"></i> Показать ещё (<?= $count - 4 ?>)
                        </button>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="es-icon"><i class="fas fa-inbox"></i></div>
                    <h4>Нет достижений</h4>
                    <p>В этой категории пока нет записей с выбранными фильтрами</p>
                </div>
            <?php endif; ?>
        </div>
    <?php
    }

    render_section('school',        'Школьные олимпиады',        $groups['school'],        [], $typeMeta);
    render_section('republic',      'Республиканские олимпиады', $groups['republic'],      [], $typeMeta);
    render_section('international', 'Международные олимпиады',   $groups['international'], [], $typeMeta);
    render_section('sport',         'Спортивные соревнования',   $groups['sport'],         [], $typeMeta);
    ?>
</div>

<script>
(function() {
    'use strict';

    /* ---------- Показать больше (УЛУЧШЕННЫЙ СКРИПТ) ---------- */
    document.querySelectorAll('.show-more-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const block = document.getElementById(btn.dataset.target);
            const cards = block.querySelectorAll('.ach-card');
            const hidden = block.querySelectorAll('.ach-card.hidden-card');
            
            if (hidden.length > 0) {
                // Раскрываем все
                hidden.forEach(c => { c.classList.remove('hidden-card'); c.style.display = 'flex'; });
                btn.innerHTML = '<i class="fas fa-chevron-up"></i> Свернуть';
            } else {
                // Скрываем все, кроме первых 4
                cards.forEach((c, i) => {
                    if (i >= 4) { c.classList.add('hidden-card'); c.style.display = 'none'; }
                });
                btn.innerHTML = '<i class="fas fa-chevron-down"></i> Показать ещё (' + (cards.length - 4) + ')';
                // Плавно прокручиваем к началу блока
                block.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    /* ---------- Анимация появления ---------- */
    const io = new IntersectionObserver(entries => {
        entries.forEach(en => {
            if (en.isIntersecting) {
                en.target.classList.add('in');
                io.unobserve(en.target);
            }
        });
    }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
    document.querySelectorAll('.fade-in').forEach(el => io.observe(el));

    /* ---------- Счётчики KPI ---------- */
    const kpiObserver = new IntersectionObserver(entries => {
        entries.forEach(en => {
            if (!en.isIntersecting) return;
            const el = en.target;
            const target = parseInt(el.dataset.count, 10) || 0;
            const dur = 1400;
            const start = performance.now();
            const easeOut = t => 1 - Math.pow(1 - t, 3);
            function tick(now) {
                const p = Math.min((now - start) / dur, 1);
                el.firstChild.nodeValue = Math.round(easeOut(p) * target);
                if (p < 1) requestAnimationFrame(tick);
            }
            requestAnimationFrame(tick);
            kpiObserver.unobserve(el);
        });
    }, { threshold: 0.5 });
    document.querySelectorAll('.kpi-value').forEach(el => kpiObserver.observe(el));

    /* ---------- KPI-бары ---------- */
    setTimeout(() => {
        document.querySelectorAll('.kpi-bar > span').forEach(s => {
            s.style.width = (s.dataset.width || 0) + '%';
        });
    }, 300);

    /* ---------- Chart.js defaults ---------- */
    if (window.Chart) {
        Chart.defaults.font.family = "'Inter', sans-serif";
        Chart.defaults.font.size = 12;
        Chart.defaults.color = '#64748b';
    }

    /* ---------- Doughnut ---------- */
    const typeColors = ['#4f46e5', '#7c3aed', '#10b981', '#f59e0b'];
    const typeLabels = <?= json_encode(array_keys($stats)) ?>;
    const typeData   = <?= json_encode(array_values($stats)) ?>;
    const totalSum   = typeData.reduce((a, b) => a + b, 0);

    if (totalSum > 0) {
        new Chart(document.getElementById('chartTypes'), {
            type: 'doughnut',
            data: {
                labels: typeLabels,
                datasets: [{
                    data: typeData,
                    backgroundColor: typeColors,
                    borderColor: '#fff',
                    borderWidth: 4,
                    hoverOffset: 14,
                    spacing: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                animation: { animateRotate: true, duration: 1400, easing: 'easeOutQuart' },
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'circle', padding: 16, boxWidth: 8, boxHeight: 8, font: { size: 12, weight: '600' } } },
                    tooltip: { backgroundColor: '#0f172a', padding: 12, cornerRadius: 10, titleFont: { size: 13, weight: '700' }, bodyFont: { size: 12 }, displayColors: true, callbacks: { label: ctx => { const v = ctx.parsed; const pct = totalSum ? ((v / totalSum) * 100).toFixed(1) : 0; return '  ' + ctx.label + ': ' + v + ' (' + pct + '%)'; } } }
                }
            }
        });
    }

    /* ---------- Bar (по годам) ---------- */
    const yearLabels = <?= json_encode(array_keys($yearStats)) ?>;
    const yearValues = <?= json_encode(array_values($yearStats)) ?>;

    if (yearLabels.length > 0) {
        const ctxBar = document.getElementById('chartYears').getContext('2d');
        const grad = ctxBar.createLinearGradient(0, 0, 0, 300);
        grad.addColorStop(0, '#6366f1');
        grad.addColorStop(0.5, '#7c3aed');
        grad.addColorStop(1, '#a855f7');

        if (yearValues.length) {
            const peak = Math.max(...yearValues);
            const peakIdx = yearValues.indexOf(peak);
            const tag = document.getElementById('yearPeakTag');
            if (tag) tag.textContent = 'Пик: ' + yearLabels[peakIdx] + ' — ' + peak;
        }

        new Chart(document.getElementById('chartYears'), {
            type: 'bar',
            data: {
                labels: yearLabels,
                datasets: [{ label: 'Достижения', data: yearValues, backgroundColor: grad, hoverBackgroundColor: '#4f46e5', borderRadius: 10, borderSkipped: false, maxBarThickness: 52, barPercentage: 0.7 }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, animation: { duration: 1200, easing: 'easeOutQuart' },
                plugins: { legend: { display: false }, tooltip: { backgroundColor: '#0f172a', padding: 12, cornerRadius: 10, titleFont: { size: 13, weight: '700' }, bodyFont: { size: 12 }, callbacks: { label: ctx => '  Достижений: ' + ctx.parsed.y } } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0, padding: 8, font: { size: 11 } }, grid: { color: '#f1f5f9', drawTicks: false }, border: { display: false } }, x: { grid: { display: false }, ticks: { font: { size: 11, weight: '600' }, padding: 6 }, border: { display: false } } }
            }
        });
    }

    /* ---------- Финальный счётчик в центре doughnut ---------- */
    const centerNum = document.querySelector('.doughnut-center .num');
    if (centerNum) {
        const target = parseInt(centerNum.dataset.count, 10) || 0;
        const dur = 1400;
        const start = performance.now();
        const easeOut = t => 1 - Math.pow(1 - t, 3);
        function tick(now) {
            const p = Math.min((now - start) / dur, 1);
            centerNum.textContent = Math.round(easeOut(p) * target);
            if (p < 1) requestAnimationFrame(tick);
        }
        requestAnimationFrame(tick);
    }
})();
</script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
require_once("js.php");
?>