<?php
mb_internal_encoding('UTF-8');
date_default_timezone_set('Asia/Dushanbe');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once("config2.php");

if (!$conn->set_charset('utf8mb4')) {
    error_log('face/index.php: failed to set database charset: ' . $conn->error);
    http_response_code(500);
    exit('Не удалось загрузить страницу. Попробуйте позже.');
}

$escape = function ($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};

function faceIndexFail($details)
{
    error_log('face/index.php: ' . $details);
    http_response_code(500);
    exit('Не удалось загрузить страницу. Попробуйте позже.');
}

// Фильтры
$in_time = 0;
$late = 0;
$absent = 0;
$selected_date_is_weekend = false;
$grade = 'Все';
$date = date('Y-m-d');

if (isset($_GET['date']) && is_string($_GET['date'])) {
    $requested_date = DateTimeImmutable::createFromFormat('!Y-m-d', $_GET['date']);
    $date_errors = DateTimeImmutable::getLastErrors();
    if (
        $requested_date !== false
        && ($date_errors === false || ($date_errors['warning_count'] === 0 && $date_errors['error_count'] === 0))
        && $requested_date->format('Y-m-d') === $_GET['date']
    ) {
        $date = $_GET['date'];
    }
}

if (isset($_GET['grade']) && is_string($_GET['grade'])) {
    $requested_grade = trim($_GET['grade']);
    if (
        $requested_grade === 'Все'
        || preg_match('/^(?:[5-9]|10|11)[АБВ]$/u', $requested_grade)
    ) {
        $grade = $requested_grade;
    }
}

$selected_day = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
$selected_date_is_weekend = $selected_day->format('N') === '7';
$date_start = $selected_day->format('Y-m-d') . ' 00:00:00';
$date_end = $selected_day->modify('+1 day')->format('Y-m-d') . ' 00:00:00';

if ($date === date('Y-m-d') && !$selected_date_is_weekend) {
    $attendanceStmt = $conn->prepare("
        INSERT INTO visits (user_id, visit, `time`)
        SELECT u.id, 'did not visit', ?
        FROM users u
        WHERE NOT EXISTS (
            SELECT 1
            FROM visits v
            WHERE v.user_id = u.id AND v.`time` >= ? AND v.`time` < ?
        )
    ");
    if (!$attendanceStmt) {
        faceIndexFail($conn->error);
    }
    $attendanceStmt->bind_param('sss', $date_start, $date_start, $date_end);
    if (!$attendanceStmt->execute()) {
        faceIndexFail($attendanceStmt->error);
    }
    $attendanceStmt->close();
}

// Рейтинг учитывает сегодняшние записи об отсутствиях.
$sql = "
    SELECT u.id, u.name, u.surname, u.grade, u.class, u.photo,
           COALESCE(ROUND(AVG(v.visit IN ('visited', 'late')) * 100, 2), 0) AS attendance
    FROM users u
    LEFT JOIN (
        SELECT v.user_id, v.visit
        FROM visits v
        JOIN (
            SELECT user_id, DATE(`time`) AS visit_day, MAX(visit_id) AS visit_id
            FROM visits
            GROUP BY user_id, DATE(`time`)
        ) latest ON latest.visit_id = v.visit_id
    ) v ON u.id = v.user_id
    GROUP BY u.id, u.name, u.surname, u.grade, u.class, u.photo
    ORDER BY attendance DESC, u.surname ASC, u.name ASC
    LIMIT 10
";
$result = $conn->query($sql);
if (!$result) {
    faceIndexFail($conn->error);
}
$topData = [];
while ($row = $result->fetch_assoc()) {
    $topData[] = $row;
}
$result->free();

if ($grade !== 'Все') {
    $class = mb_substr($grade, -1, null, 'UTF-8');
    $gradeOriginal = (int)mb_substr($grade, 0, -1, 'UTF-8');
}

// 3. Запрос данных
$attendanceRows = [];
if (!$selected_date_is_weekend) {
    $base_sql = "
        SELECT 
            o.visit_id AS id, 
            u.id AS user_id, 
            u.surname, u.name AS user_name, u.patronymic, 
            u.gender, u.birth, u.grade, u.class,
            o.visit,
            CASE WHEN o.visit = 'did not visit' THEN ''
                 ELSE IFNULL(DATE_FORMAT(o.time, '%H:%i:%s'), '') END AS time,
            CASE 
                WHEN o.visit = 'did not visit' OR o.visit IS NULL THEN 'absent'
                WHEN o.visit = 'late' THEN 'late'
                WHEN (u.grade >= 5 AND u.grade <= 7) THEN 
                    CASE 
                        WHEN TIME(o.time) <= '13:00:00' THEN 'present'
                        ELSE 'late'
                    END
                WHEN (u.grade >= 8 AND u.grade <= 11) THEN 
                    CASE 
                        WHEN TIME(o.time) <= '08:00:00' THEN 'present'
                        ELSE 'late'
                    END
                ELSE 'present'
            END AS status
        FROM users u
        LEFT JOIN visits o ON o.visit_id = (
            SELECT v.visit_id
            FROM visits v
            WHERE v.user_id = u.id AND v.time >= ? AND v.time < ?
            ORDER BY v.time DESC, v.visit_id DESC
            LIMIT 1
        )
        WHERE 1=1
    ";

    if ($grade === 'Все') {
        $stmt = $conn->prepare($base_sql . " ORDER BY u.surname ASC, u.name ASC");
        if (!$stmt) {
            faceIndexFail($conn->error);
        }
        $stmt->bind_param('ss', $date_start, $date_end);
    } else {
        $stmt = $conn->prepare($base_sql . " AND u.grade = ? AND u.class = ? ORDER BY u.surname ASC, u.name ASC");
        if (!$stmt) {
            faceIndexFail($conn->error);
        }
        $stmt->bind_param('ssis', $date_start, $date_end, $gradeOriginal, $class);
    }

    if (!$stmt->execute()) {
        faceIndexFail($stmt->error);
    }
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $attendanceRows[] = $row;
        if ($row['status'] === 'present') {
            $in_time++;
        } elseif ($row['status'] === 'late') {
            $late++;
        } else {
            $absent++;
        }
    }
    $stmt->close();
}

$conn->close();
ob_start();
require_once("header.php");
$header_markup = ob_get_clean();
ob_start();
?>

    <style>
        /* ============ ROOT ============ */
        :root {
            --primary: #4f46e5;
            --primary-light: #818cf8;
            --primary-dark: #3730a3;
            --secondary: #7c3aed;
            --success: #10b981;
            --success-dark: #059669;
            --danger: #ef4444;
            --warning: #f59e0b;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --text-light: #9ca3af;
            --bg-main: #f9fafb;
            --bg-card: #ffffff;
            --border-color: #e5e7eb;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            --radius: 12px;
            --radius-lg: 16px;
            --radius-xl: 24px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: var(--bg-main);
            color: var(--text-primary);
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: var(--bg-main); }
        ::-webkit-scrollbar-thumb { background: var(--primary); border-radius: 10px; }

        /* ============ SLIDER ============ */
        .hero-slider { position: relative; overflow: hidden; border-radius: 0 0 var(--radius-lg) var(--radius-lg); margin-bottom: 40px; }
        .hero-slider .slide { min-height: 480px; display: flex; align-items: center; position: relative; background-size: cover; background-position: center; }
        .hero-slider .slide:not(:first-child) { display: none; }
        .hero-slider .slide::before { content: ''; position: absolute; inset: 0; background: linear-gradient(135deg, rgba(15, 23, 42, 0.8) 0%, rgba(15, 23, 42, 0.4) 100%); z-index: 1; }
        .hero-slider .slide-content { position: relative; z-index: 2; padding: 60px 0; max-width: 600px; }
        .hero-slider .slide-content .badge { display: inline-block; padding: 6px 18px; background: rgba(79, 70, 229, 0.2); backdrop-filter: blur(8px); border: 1px solid rgba(79, 70, 229, 0.3); border-radius: 100px; font-size: 11px; font-weight: 600; color: #a5b4fc; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 16px; }
        .hero-slider .slide-content h2 { font-size: 44px; font-weight: 800; color: #fff; line-height: 1.1; letter-spacing: -0.02em; margin-bottom: 16px; }
        .hero-slider .slide-content h2 .highlight { background: linear-gradient(135deg, #818cf8, #c4b5fd); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .hero-slider .slide-content p { font-size: 16px; color: rgba(255,255,255,0.7); line-height: 1.7; margin-bottom: 28px; }
        .hero-slider .slide-content .btn-primary { display: inline-flex; align-items: center; gap: 10px; padding: 14px 32px; background: linear-gradient(135deg, var(--primary), var(--secondary)); color: #fff; border: none; border-radius: 60px; font-size: 14px; font-weight: 600; cursor: pointer; transition: var(--transition); text-decoration: none; box-shadow: 0 8px 32px rgba(79, 70, 229, 0.3); }
        .hero-slider .slide-content .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 40px rgba(79, 70, 229, 0.4); }
        .slider-dots { position: absolute; bottom: 24px; left: 50%; transform: translateX(-50%); display: flex; gap: 8px; z-index: 10; }
        .slider-dots .dot { width: 8px; height: 8px; border-radius: 50%; background: rgba(255,255,255,0.3); transition: var(--transition); cursor: pointer; border: none; }
        .slider-dots .dot.active { background: var(--primary); width: 28px; border-radius: 20px; }

        /* ============ SECTION ============ */
        .section { max-width: 1280px; margin: 0 auto; padding: 60px 48px; }
        .section-header { text-align: center; margin-bottom: 44px; }
        .section-header .tag { display: inline-block; padding: 4px 16px; background: rgba(79, 70, 229, 0.08); border-radius: 100px; font-size: 11px; font-weight: 600; color: var(--primary); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; }
        .section-header h2 { font-size: 34px; font-weight: 700; letter-spacing: -0.02em; color: var(--text-primary); }
        .section-header h2 .highlight { background: linear-gradient(135deg, var(--primary), var(--secondary)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .section-header p { font-size: 16px; color: var(--text-secondary); max-width: 500px; margin: 8px auto 0; }

        /* ============ FEATURES ============ */
        .features-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
        .feature-card { background: var(--bg-card); padding: 32px 28px; border-radius: var(--radius); border: 1px solid var(--border-color); transition: var(--transition); box-shadow: var(--shadow-sm); }
        .feature-card:hover { transform: translateY(-6px); box-shadow: var(--shadow-lg); border-color: rgba(79, 70, 229, 0.12); }
        .feature-card .icon { width: 52px; height: 52px; border-radius: var(--radius); background: rgba(79, 70, 229, 0.08); display: flex; align-items: center; justify-content: center; font-size: 22px; color: var(--primary); margin-bottom: 16px; transition: var(--transition); }
        .feature-card:hover .icon { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: #fff; transform: scale(1.05) rotate(-4deg); }
        .feature-card h3 { font-size: 17px; font-weight: 700; margin-bottom: 4px; }
        .feature-card p { color: var(--text-secondary); font-size: 13px; line-height: 1.7; }

        /* ============ ABOUT ============ */
        .about-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 44px; align-items: center; background: var(--bg-card); border-radius: var(--radius-lg); padding: 48px 44px; box-shadow: var(--shadow-sm); border: 1px solid var(--border-color); }
        .about-content h3 { font-size: 28px; font-weight: 700; margin-bottom: 12px; letter-spacing: -0.02em; }
        .about-content p { color: var(--text-secondary); line-height: 1.8; font-size: 14px; margin-bottom: 10px; }
        .about-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 16px; }
        .about-stats .item { text-align: center; padding: 14px; background: var(--bg-main); border-radius: var(--radius); transition: var(--transition); }
        .about-stats .item:hover { background: rgba(79, 70, 229, 0.06); transform: translateY(-2px); }
        .about-stats .item h4 { font-size: 22px; font-weight: 700; color: var(--primary); }
        .about-stats .item span { font-size: 12px; color: var(--text-secondary); font-weight: 500; }
        .about-image { border-radius: var(--radius); overflow: hidden; background: linear-gradient(135deg, var(--primary), var(--secondary)); min-height: 260px; display: flex; align-items: center; justify-content: center; color: rgba(255,255,255,0.85); font-size: 64px; transition: var(--transition); }
        .about-image:hover { transform: scale(1.01); }

        /* ============ ATTENDANCE TABLE ============ */
        .attendance-section { background: var(--bg-card); border-radius: var(--radius-lg); padding: 50px 44px; box-shadow: var(--shadow-sm); border: 1px solid var(--border-color); }
        .attendance-controls { display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-end; justify-content: center; margin-bottom: 32px; padding: 24px; background: var(--bg-main); border-radius: var(--radius); border: 1px solid var(--border-color); width: 100%; }
        .control-group { display: flex; flex-direction: column; gap: 6px; flex: 1; min-width: 140px; max-width: 220px; }
        .control-group label { font-size: 12px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; }
        .attendance-controls select, .attendance-controls input[type="date"] { padding: 12px 16px; border-radius: var(--radius); border: 1px solid var(--border-color); font-size: 14px; font-family: 'Inter', sans-serif; background: #fff; color: var(--text-primary); outline: none; transition: var(--transition); width: 100%; cursor: pointer; }
        .attendance-controls select:focus, .attendance-controls input[type="date"]:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
        .attendance-controls .btn-submit { padding: 12px 32px; background: linear-gradient(135deg, var(--primary), var(--secondary)); color: #fff; border: none; border-radius: var(--radius); font-size: 14px; font-weight: 600; cursor: pointer; transition: var(--transition); font-family: 'Inter', sans-serif; display: flex; align-items: center; gap: 8px; height: 44px; }
        .attendance-controls .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 24px rgba(79, 70, 229, 0.25); }

        .table-wrap { background: var(--bg-card); border-radius: var(--radius); border: 1px solid var(--border-color); overflow-x: auto; width: 100%; box-shadow: var(--shadow-sm); }
        .table-wrap table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .table-wrap thead th { padding: 16px; text-align: center; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary); border-bottom: 2px solid var(--border-color); background: var(--bg-main); white-space: nowrap; }
        .table-wrap tbody td { padding: 12px 16px; border-bottom: 1px solid var(--border-color); color: var(--text-primary); text-align: center; vertical-align: middle; transition: background 0.2s; }
        .table-wrap tbody tr:last-child td { border-bottom: none; }
        .table-wrap tbody tr:hover td { background: rgba(79, 70, 229, 0.03); }
        .table-wrap input[type="time"] { padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius); font-size: 13px; font-family: 'Inter', sans-serif; background: var(--bg-main); outline: none; transition: var(--transition); color: var(--text-primary); font-weight: 500; text-align: center; }
        .table-wrap input[type="time"]:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); background: #fff; }
        .table-wrap input[type="radio"] { width: 18px; height: 18px; accent-color: var(--primary); cursor: pointer; transition: var(--transition); }
        .table-wrap input[type="radio"]:hover { transform: scale(1.15); }

        .table-actions { display: flex; gap: 12px; justify-content: center; margin: 24px 0; flex-wrap: wrap; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 28px; border-radius: 60px; font-size: 14px; font-weight: 600; cursor: pointer; transition: var(--transition); text-decoration: none; font-family: 'Inter', sans-serif; border: none; }
        .btn-primary-solid { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: #fff; box-shadow: 0 4px 15px rgba(79, 70, 229, 0.25); }
        .btn-primary-solid:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(79, 70, 229, 0.35); }
        .btn-outline { background: transparent; color: var(--text-primary); border: 2px solid var(--border-color); }
        .btn-outline:hover { border-color: var(--primary); background: rgba(79, 70, 229, 0.04); }

        .result-table { margin-top: 24px; padding: 24px; background: var(--bg-card); border-radius: var(--radius); border: 1px solid var(--border-color); box-shadow: var(--shadow-md); display: none; overflow-x: auto; width: 100%; }
        .result-table table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .result-table thead th { padding: 12px 16px; text-align: left; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary); border-bottom: 2px solid var(--border-color); background: var(--bg-main); }
        .result-table tbody td { padding: 12px 16px; border-bottom: 1px solid var(--border-color); color: var(--text-primary); }
        .result-table tbody tr:hover td { background: rgba(79, 70, 229, 0.02); }
        
        .status-badge { display: inline-block; padding: 6px 12px; border-radius: 100px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-present { background: rgba(16, 185, 129, 0.1); color: var(--success-dark); }
        .status-absent { background: rgba(239, 68, 68, 0.1); color: var(--danger); }
        .status-late { background: rgba(245, 158, 11, 0.1); color: var(--warning); }

        .chart-box { max-width: 400px; margin: 40px auto 0; padding: 24px; background: var(--bg-card); border-radius: var(--radius); border: 1px solid var(--border-color); transition: var(--transition); }
        .chart-box:hover { box-shadow: var(--shadow-lg); }

        /* ============ STUDENT CARDS ============ */
        .student-section { padding: 60px 48px; max-width: 1280px; margin: 0 auto; }
        .top-three { display: grid; grid-template-columns: 1fr 1.1fr 1fr; gap: 16px; margin-bottom: 32px; }
        .student-card-top { background: var(--bg-card); border-radius: var(--radius); overflow: hidden; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); transition: var(--transition); position: relative; text-align: center; padding-bottom: 14px; }
        .student-card-top:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); border-color: rgba(79, 70, 229, 0.15); }
        .student-card-top.rank-1 { border-color: rgba(79, 70, 229, 0.2); box-shadow: 0 12px 48px rgba(79, 70, 229, 0.12); transform: scale(1.02); }
        .student-card-top.rank-1:hover { transform: scale(1.02) translateY(-4px); }
        .student-card-top .rank-badge { position: absolute; top: 10px; left: 12px; color: #fff; font-size: 10px; font-weight: 700; padding: 4px 14px; border-radius: 100px; z-index: 2; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .student-card-top .rank-badge.gold { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
        .student-card-top .rank-badge.silver { background: linear-gradient(135deg, #94a3b8, #cbd5e1); }
        .student-card-top .rank-badge.bronze { background: linear-gradient(135deg, #d97706, #f59e0b); }
        .student-card-top .avatar-wrap { width: 100%; padding-top: 100%; position: relative; overflow: hidden; background: linear-gradient(135deg, rgba(79,70,229,0.05), rgba(79,70,229,0.12)); }
        .student-card-top .avatar-wrap img { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; }
        .student-card-top .avatar-wrap .avatar-fallback, .student-card-sm .avatar-wrap .avatar-fallback { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; color: var(--primary); font-weight: 700; background: linear-gradient(135deg, rgba(79,70,229,0.05), rgba(79,70,229,0.12)); }
        .student-card-top .avatar-wrap .avatar-fallback { font-size: 56px; }
        .student-card-top .score-circle { position: absolute; top: 10px; right: 12px; width: 40px; height: 40px; border-radius: 50%; background: #fff; border: 2px solid var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px; color: var(--primary); box-shadow: 0 4px 16px rgba(0,0,0,0.06); z-index: 2; }
        .student-card-top .info { padding: 12px 14px 4px; }
        .student-card-top .info h4 { font-size: 15px; font-weight: 700; margin-bottom: 2px; }
        .student-card-top .info p { color: var(--text-secondary); font-size: 12px; }
        
        .carousel-track { display: flex; gap: 14px; overflow-x: auto; padding: 8px 4px 20px; scroll-behavior: smooth; -webkit-overflow-scrolling: touch; }
        .carousel-track::-webkit-scrollbar { height: 4px; }
        .carousel-track::-webkit-scrollbar-track { background: var(--border-color); border-radius: 10px; }
        .carousel-track::-webkit-scrollbar-thumb { background: var(--primary); border-radius: 10px; }
        .student-card-sm { flex: 0 0 150px; background: var(--bg-card); border-radius: var(--radius); border: 1px solid var(--border-color); overflow: hidden; transition: var(--transition); box-shadow: var(--shadow-sm); cursor: pointer; position: relative; }
        .student-card-sm:hover { transform: scale(1.1) translateY(-6px); box-shadow: var(--shadow-lg); border-color: rgba(79, 70, 229, 0.2); z-index: 5; }
        .student-card-sm .avatar-wrap { width: 100%; padding-top: 100%; position: relative; overflow: hidden; background: linear-gradient(135deg, rgba(79,70,229,0.04), rgba(79,70,229,0.1)); }
        .student-card-sm .avatar-wrap img { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; }
        .student-card-sm .avatar-wrap .avatar-fallback { font-size: 38px; }
        .student-card-sm .score-badge { position: absolute; top: 6px; right: 6px; background: linear-gradient(135deg, var(--primary), var(--secondary)); color: #fff; font-size: 9px; font-weight: 700; padding: 2px 10px; border-radius: 100px; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25); }
        .student-card-sm .info { padding: 8px 10px 10px; text-align: center; }
        .student-card-sm .info h4 { font-size: 12px; font-weight: 600; }
        .student-card-sm .info p { color: var(--text-secondary); font-size: 10px; }

        /* ============ ACHIEVEMENTS ============ */
        .achievements-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .achievement-card { background: var(--bg-card); border-radius: var(--radius); overflow: hidden; border: 1px solid var(--border-color); transition: var(--transition); }
        .achievement-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); border-color: rgba(79, 70, 229, 0.12); }
        .achievement-card img { width: 100%; height: 170px; object-fit: cover; transition: var(--transition); }
        .achievement-card:hover img { transform: scale(1.03); }
        .achievement-card .content { padding: 16px 20px 20px; }
        .achievement-card .content .tag { display: inline-block; padding: 2px 10px; background: rgba(79, 70, 229, 0.08); border-radius: 100px; font-size: 9px; font-weight: 600; color: var(--primary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .achievement-card .content h4 { font-size: 16px; font-weight: 700; margin-bottom: 4px; }
        .achievement-card .content p { color: var(--text-secondary); font-size: 13px; line-height: 1.7; }

        /* ============ TABS ============ */
        .tabs { display: flex; justify-content: center; gap: 4px; flex-wrap: wrap; margin-bottom: 28px; }
        .tabs .tab { padding: 8px 24px; border-radius: var(--radius); font-weight: 500; font-size: 13px; color: var(--text-secondary); background: transparent; border: 1px solid transparent; cursor: pointer; transition: var(--transition); }
        .tabs .tab:hover { color: var(--primary); background: rgba(79, 70, 229, 0.04); }
        .tabs .tab.active { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: #fff; border-color: var(--primary); box-shadow: 0 4px 15px rgba(79, 70, 229, 0.25); }

        /* ============ CLUBS ============ */
        .clubs-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
        .club-card { background: var(--bg-card); border-radius: var(--radius); padding: 32px 20px 28px; text-align: center; border: 1px solid var(--border-color); transition: var(--transition); box-shadow: var(--shadow-sm); min-height: 220px; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .club-card:hover { transform: translateY(-6px); box-shadow: var(--shadow-lg); border-color: rgba(79, 70, 229, 0.12); }
        .club-card .icon { width: 60px; height: 60px; margin: 0 auto 12px; border-radius: 50%; background: rgba(79, 70, 229, 0.08); display: flex; align-items: center; justify-content: center; font-size: 26px; color: var(--primary); transition: var(--transition); }
        .club-card:hover .icon { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: #fff; transform: scale(1.06); }
        .club-card h4 { font-size: 17px; font-weight: 700; margin-bottom: 2px; }
        .club-card .teacher { color: var(--text-secondary); font-size: 13px; }
        .club-card .time { color: var(--text-light); font-size: 12px; margin-bottom: 14px; }
        .club-card .btn-club { display: inline-block; padding: 6px 22px; border-radius: 60px; background: linear-gradient(135deg, var(--primary), var(--secondary)); color: #fff; border: none; font-weight: 600; font-size: 12px; cursor: pointer; transition: var(--transition); text-decoration: none; font-family: 'Inter', sans-serif; }
        .club-card .btn-club:hover { transform: scale(1.04); box-shadow: 0 6px 24px rgba(79, 70, 229, 0.25); }

        /* ============ TESTIMONIALS ============ */
        .testimonials-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .testimonial-card { background: var(--bg-card); padding: 28px 24px; border-radius: var(--radius); border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); transition: var(--transition); }
        .testimonial-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); border-color: rgba(79, 70, 229, 0.12); }
        .testimonial-card .stars { color: #f59e0b; font-size: 13px; letter-spacing: 2px; margin-bottom: 10px; }
        .testimonial-card blockquote { font-size: 13px; line-height: 1.8; color: var(--text-secondary); font-style: italic; margin-bottom: 12px; }
        .testimonial-card .author { display: flex; align-items: center; gap: 10px; }
        .testimonial-card .author .avatar-sm { width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), var(--secondary)); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 13px; }
        .testimonial-card .author .name { font-weight: 600; font-size: 13px; }
        .testimonial-card .author .role { font-size: 11px; color: var(--text-light); }

        /* ============ PARTNERS ============ */
        .partners-grid { display: flex; justify-content: center; align-items: center; gap: 32px; flex-wrap: wrap; padding: 6px 0; }
        .partner-item { font-size: 15px; font-weight: 600; color: var(--text-light); opacity: 0.4; transition: var(--transition); letter-spacing: 0.5px; padding: 6px 14px; border-radius: var(--radius); border: 1px solid transparent; }
        .partner-item:hover { opacity: 1; color: var(--primary); border-color: rgba(79, 70, 229, 0.08); background: rgba(79, 70, 229, 0.04); transform: translateY(-2px); }

        /* ============ CTA ============ */
        .cta-section { background: linear-gradient(135deg, #0a0e1a, #1a1a3e); border-radius: var(--radius-lg); padding: 48px 40px; text-align: center; color: #fff; position: relative; overflow: hidden; transition: var(--transition); }
        .cta-section:hover { transform: scale(1.005); }
        .cta-section h2 { font-size: 28px; font-weight: 700; margin-bottom: 10px; letter-spacing: -0.02em; }
        .cta-section p { font-size: 15px; opacity: 0.6; max-width: 440px; margin: 0 auto 20px; line-height: 1.7; }
        .cta-section .btn-cta { background: #fff; color: #0a0e1a; box-shadow: none; }
        .cta-section .btn-cta:hover { transform: translateY(-3px) scale(1.02); box-shadow: 0 12px 48px rgba(255,255,255,0.15); }

        /* ============ FOOTER ============ */
        .footer { max-width: 1280px; margin: 40px auto 0; padding: 40px 48px 24px; border-top: 1px solid var(--border-color); display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 40px; background: var(--bg-card); border-radius: var(--radius-lg) var(--radius-lg) 0 0; box-shadow: 0 -8px 40px rgba(0,0,0,0.04); width: 100%; }
        .footer .brand { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
        .footer .brand-icon { width: 36px; height: 36px; background: linear-gradient(135deg, var(--primary), var(--secondary)); border-radius: var(--radius); display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 800; font-size: 16px; }
        .footer .brand-name { font-size: 20px; font-weight: 800; color: var(--text-primary); }
        .footer .brand-name span { color: var(--primary); }
        .footer .desc { color: var(--text-secondary); font-size: 13px; line-height: 1.7; max-width: 280px; }
        .footer h4 { font-size: 14px; font-weight: 700; margin-bottom: 12px; }
        .footer a { display: block; color: var(--text-secondary); text-decoration: none; font-size: 13px; padding: 4px 0; transition: var(--transition); }
        .footer a:hover { color: var(--primary); transform: translateX(4px); }
        .footer .copyright { grid-column: 1 / -1; text-align: center; font-size: 13px; color: var(--text-light); padding-top: 20px; border-top: 1px solid var(--border-color); }

        /* ============ ANIMATIONS & RESPONSIVE ============ */
        .fade-up { opacity: 0; transform: translateY(28px); transition: all 0.7s cubic-bezier(0.23, 1, 0.32, 1); }
        .fade-up.visible { opacity: 1; transform: translateY(0); }

        @media (max-width: 1024px) {
            .section { padding: 40px 24px; }
            .student-section { padding: 40px 24px; }
            .features-grid { grid-template-columns: 1fr 1fr; }
            .about-grid { grid-template-columns: 1fr; text-align: center; padding: 32px 24px; }
            .about-stats { grid-template-columns: 1fr 1fr; }
            .clubs-grid { grid-template-columns: repeat(2, 1fr); }
            .achievements-grid { grid-template-columns: 1fr 1fr; }
            .testimonials-grid { grid-template-columns: 1fr 1fr; }
            .footer { grid-template-columns: 1fr 1fr; padding: 32px 24px; }
            .top-three { grid-template-columns: 1fr 1fr 1fr; gap: 12px; }
            .hero-slider .slide-content h2 { font-size: 32px; }
        }

        @media (max-width: 768px) {
            .section { padding: 32px 16px; }
            .student-section { padding: 32px 16px; }
            .section-header h2 { font-size: 26px; }
            .features-grid { grid-template-columns: 1fr; }
            .clubs-grid { grid-template-columns: 1fr 1fr; }
            .achievements-grid { grid-template-columns: 1fr; }
            .testimonials-grid { grid-template-columns: 1fr; }
            .footer { grid-template-columns: 1fr; text-align: center; padding: 28px 16px; margin: 20px 0 0; border-radius: 0; }
            .footer .desc { margin: 0 auto; }
            .attendance-controls { flex-direction: column; align-items: stretch; padding: 16px; }
            .control-group { max-width: none; }
            .attendance-controls .btn-submit { width: 100%; justify-content: center; }
            .about-stats { grid-template-columns: 1fr; }
            .cta-section { padding: 32px 20px; }
            .cta-section h2 { font-size: 24px; }
            .top-three { grid-template-columns: 1fr; gap: 16px; max-width: 280px; margin: 0 auto 24px; }
            .student-card-top.rank-1 { transform: scale(1); }
            .student-card-top.rank-1:hover { transform: translateY(-4px); }
            .student-card-sm { flex: 0 0 130px; }
            .hero-slider .slide-content h2 { font-size: 26px; }
            .hero-slider .slide-content p { font-size: 14px; }
            .hero-slider .slide { min-height: 400px; }
        }

        @media (max-width: 480px) {
            .section-header h2 { font-size: 22px; }
            .table-wrap table { font-size: 11px; }
            .table-wrap thead th, .table-wrap tbody td { padding: 6px 6px; }
            .table-wrap input[type="time"] { max-width: 70px; padding: 2px 4px; font-size: 10px; }
            .table-wrap input[type="radio"] { width: 12px; height: 12px; }
            .student-card-sm { flex: 0 0 110px; }
            .hero-slider .slide-content h2 { font-size: 22px; }
            .btn { padding: 10px 20px; font-size: 13px; }
            .about-grid { padding: 24px 16px; }
            .result-table { padding: 8px 12px; }
            .result-table table { font-size: 10px; }
            .result-table thead th, .result-table tbody td { padding: 4px 6px; }
            .clubs-grid { grid-template-columns: 1fr; }
            .footer { padding: 20px 12px; }
            .footer .brand { justify-content: center; }
            .attendance-controls { padding: 12px; }
            .attendance-controls select, .attendance-controls input[type="date"] { padding: 8px 12px; font-size: 13px; }
            .attendance-controls .btn-submit { padding: 10px 20px; font-size: 13px; }
            .chart-box { max-width: 280px; }
            .hero-slider .slide { min-height: 340px; }
        }
    </style>
<?php
$page_styles = ob_get_clean();
echo str_replace('</head>', $page_styles . '</head>', $header_markup);
?>

<!-- ============ HERO SLIDER ============ -->
<div class="hero-slider">
    <div class="slides-wrapper">
        <div class="slide" style="background-image: url('img/img1.jpg');">
            <div class="container">
                <div class="slide-content">
                    <div class="badge">Инновационная платформа</div>
                    <h2>СКАН — <span class="highlight">умный мониторинг</span> образования</h2>
                    <p>Следи за посещаемостью, успеваемостью и развитием учеников в реальном времени с помощью передовых технологий.</p>
                    <a href="statistics.php" class="btn-primary">
                        <span>Начать</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="slide" style="background-image: url('img/img3.jpg');">
            <div class="container">
                <div class="slide-content">
                    <div class="badge">Аналитика</div>
                    <h2>Интуитивная <span class="highlight">аналитика</span> для каждого класса</h2>
                    <p>Графики, диаграммы и отчёты — все данные структурированы для максимального удобства.</p>
                    <a href="statistics1.php" class="btn-primary">
                        <span>Анализировать</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="slide" style="background-image: url('img/img6.jpg');">
            <div class="container">
                <div class="slide-content">
                    <div class="badge">Искусственный интеллект</div>
                    <h2>Распознавание лиц и <span class="highlight">AI-технологии</span></h2>
                    <p>Автоматическая фиксация посещаемости с помощью нейросетей — быстро, точно, безопасно.</p>
                    <a href="#attendanceTable" class="btn-primary">
                        <span>Попробовать</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="slide" style="background-image: url('img/9.png');">
            <div class="container">
                <div class="slide-content">
                    <div class="badge">AI</div>
                    <h2>Распознавание лиц <span class="highlight">и посещаемость</span> с помощью ИИ</h2>
                    <p>Автоматическая фиксация посещаемости с помощью нейросетей — быстро, точно, безопасно.</p>
                    <a href="#attendanceTable" class="btn-primary">
                        <span>Попробовать</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="slide" style="background-image: url('img/2.jpg');">
            <div class="container">
                <div class="slide-content">
                    <div class="badge">Достижения</div>
                    <h2>Анализ успеваемости <span class="highlight">и посещаемости</span></h2>
                    <p>Комплексный подход к оценке образовательного процесса в одном месте.</p>
                    <a href="#attendanceTable" class="btn-primary">
                        <span>Смотреть</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="slider-dots"></div>
</div>

<!-- ============ FEATURES ============ -->
<section class="section">
    <div class="section-header fade-up">
        <div class="tag">Преимущества</div>
        <h2>Почему <span class="highlight">СКАН</span>?</h2>
        <p>Современные технологии для образования будущего</p>
    </div>
    <div class="features-grid">
        <div class="feature-card fade-up">
            <div class="icon"><i class="fas fa-shield-alt"></i></div>
            <h3>Безопасность</h3>
            <p>Биометрическая идентификация с использованием сложных алгоритмов для максимальной защиты.</p>
        </div>
        <div class="feature-card fade-up">
            <div class="icon"><i class="fas fa-bullseye"></i></div>
            <h3>Точность</h3>
            <p>Распознавание лиц с точностью до 99.9% — превосходит традиционные методы идентификации.</p>
        </div>
        <div class="feature-card fade-up">
            <div class="icon"><i class="fas fa-bolt"></i></div>
            <h3>Мгновенность</h3>
            <p>Бесконтактное распознавание за доли секунды — удобно и эффективно.</p>
        </div>
    </div>
</section>

<!-- ============ ABOUT ============ -->
<section class="section">
    <div class="about-grid fade-up">
        <div class="about-content">
            <div class="tag" style="display:inline-block;padding:4px 16px;background:rgba(79,70,229,0.08);border-radius:100px;font-size:11px;font-weight:600;color:var(--primary);text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;">
                О нас
            </div>
            <h3>Создаём будущее <br><span class="highlight">образования</span></h3>
            <p>СКАН — это инновационная платформа, которая объединяет передовые технологии распознавания лиц и аналитики данных для создания эффективной системы мониторинга образования.</p>
            <p>Мы помогаем школам и учебным заведениям автоматизировать процесс учёта посещаемости, повышать успеваемость и создавать комфортную образовательную среду.</p>
            <div class="about-stats">
                <div class="item"><h4>50+</h4><span>Школ</span></div>
                <div class="item"><h4>12K</h4><span>Учеников</span></div>
                <div class="item"><h4>99.9%</h4><span>Точность</span></div>
            </div>
        </div>
        <div class="about-image">
            <i class="fas fa-graduation-cap"></i>
        </div>
    </div>
</section>

<!-- ============ ATTENDANCE ============ -->
<section class="section" id="attendanceTable">
    <div class="attendance-section">
        <div class="section-header fade-up">
            <div class="tag">Посещаемость</div>
            <h2>Учёт <span class="highlight">посещаемости</span></h2>
            <p>Выберите дату и класс, чтобы просмотреть и скорректировать отметки</p>
        </div>

        <form action="index.php#attendanceTable" method="GET" class="attendance-controls fade-up" accept-charset="UTF-8">
            <div class="control-group">
                <label for="gradeSelect">Класс</label>
                <select id="gradeSelect" name="grade">
                    <option value="Все" <?= $grade === 'Все' ? 'selected' : '' ?>>Все</option>
                    <?php
                    $a = ['А','Б','В','Г'];
                    for($i = 5; $i <= 11; $i++){
                        for($j = 0; $j < 3; $j++){
                            $option_grade = $i . $a[$j];
                            echo '<option value="' . $escape($option_grade) . '" ' . ($grade === $option_grade ? 'selected' : '') . '>' . $escape($option_grade) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>

            <div class="control-group">
                <label for="datePicker">Дата</label>
                <input type="date" id="datePicker" name="date" value="<?= $escape($date) ?>">
            </div>

            <button type="submit" class="btn-submit"><i class="fas fa-search"></i> Показать</button>
        </form>

        <div class="table-wrap fade-up">
            <table>
                <thead>
                    <tr>
                        <th style="text-align: left; padding-left: 24px;">Ученик</th>
                        <th>Класс</th>
                        <th>Время</th>
                        <th>Присутствует</th>
                        <th>Отсутствует</th>
                        <th>Опоздал</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $limit = 30;
                    $i = 1;
                    
                    if ($selected_date_is_weekend) {
                        echo '<tr><td colspan="6" style="padding:60px;text-align:center;font-size:18px;color:var(--danger);font-weight:700;">
                                <i class="fas fa-glass-cheers" style="font-size:32px;margin-bottom:10px;display:block;"></i>
                                В этот день выходной! Уроков нет.
                              </td></tr>';
                    } elseif (!empty($attendanceRows)) {
                        foreach ($attendanceRows as $row) {
                            $time = $row['time'];
                            $status = $row['status'];
                            if ($status == 'present') {
                                $check_present = 'checked'; $check_absent = ''; $check_late = '';
                            } elseif ($status == 'late') {
                                $check_present = ''; $check_absent = ''; $check_late = 'checked';
                            } else {
                                $check_present = ''; $check_absent = 'checked'; $check_late = '';
                            }
                            $user_name = trim($row['surname'] . ' ' . $row['user_name'] . ' ' . $row['patronymic']);
                            $row_attributes = ($i > $limit) ? ' class="extra-row" style="display:none;"' : '';
                            $time_input = '<input type="time" name="arrival_' . $i . '" step="1" value="' . $escape($time) . '" />';

                            echo '<tr' . $row_attributes . '>
                                <td style="text-align:left;padding-left:24px;"><strong>' . $escape($user_name) . '</strong></td>
                                <td><span style="background:var(--bg-main);padding:4px 10px;border-radius:8px;font-weight:600;font-size:12px;">' . $escape($row['grade'] . $row['class']) . '</span></td>
                                <td>' . $time_input . '</td>
                                <td><input type="radio" name="status_' . $i . '" value="Присутствует" ' . $check_present . '></td>
                                <td><input type="radio" name="status_' . $i . '" value="Отсутствует" ' . $check_absent . '></td>
                                <td><input type="radio" name="status_' . $i . '" value="Опоздал" ' . $check_late . '></td>
                            </tr>';
                            $i++;
                        }
                    } else {
                        echo '<tr><td colspan="6" style="padding:40px;color:var(--text-secondary);">Нет данных о посещаемости за эту дату</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="table-actions">
            <button type="button" class="btn btn-primary-solid" id="showAllBtn" style="display:none;"><i class="fas fa-expand"></i> Показать всех</button>
            <button type="button" class="btn btn-outline" id="collapseBtn" style="display:none;"><i class="fas fa-compress"></i> Свернуть</button>
            <button type="button" class="btn btn-primary-solid" id="summaryBtn"><i class="fas fa-chart-pie"></i> Показать сводку</button>
        </div>

        <!-- Блок результата -->
        <div class="result-table" id="resultTable">
            <h3 style="margin-bottom:16px;font-size:16px;color:var(--text-primary);">Предварительная сводка изменений</h3>
            <table>
                <thead>
                    <tr>
                        <th>Ученик</th>
                        <th>Класс</th>
                        <th>Время</th>
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody id="resultBody"></tbody>
            </table>
        </div>

        <!-- График -->
        <div class="chart-box">
            <canvas id="attendanceChart"></canvas>
        </div>
    </div>
</section>

<!-- ============ STUDENTS ============ -->
<section class="student-section">
    <div class="section-header fade-up">
        <div class="tag">Рейтинг</div>
        <h2>Лучшие <span class="highlight">ученики</span></h2>
        <p>Топ-10 по посещаемости</p>
    </div>

    <div class="top-three fade-up" id="topThree">
        <?php
        if (!empty($topData)) {
            $rankClasses = ['gold', 'silver', 'bronze'];
            $order = [1, 0, 2];
            for ($idx = 0; $idx < 3 && $idx < count($order); $idx++) {
                $i = $order[$idx];
                if ($i >= count($topData)) continue;
                $row = $topData[$i];
                $isFirst = $i === 0;
                $fio = trim($row['surname'] . ' ' . $row['name']);
                $class_name = $row['grade'] . $row['class'];
                $att = round((float)$row['attendance'], 2);
                $photoName = trim((string)$row['photo']);
                if ($photoName === '' || !preg_match('/\A[a-zA-Z0-9._-]+\z/', $photoName) || $photoName === '.' || $photoName === '..') {
                    $photoName = 'default.jpg';
                }
                $photo = $escape('img/students/' . $photoName);
                $photo_available = is_file(__DIR__ . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'students' . DIRECTORY_SEPARATOR . $photoName);
                $initial = $escape(mb_strtoupper(mb_substr($fio, 0, 1, 'UTF-8'), 'UTF-8'));
                $photo_image = $photo_available
                    ? '<img src="' . $photo . '" alt="' . $escape($fio) . '" loading="lazy" onerror="this.onerror=null;this.remove();">'
                    : '';
                $medal = $i === 0 ? '🥇' : ($i === 1 ? '🥈' : '🥉');
                echo '<div class="student-card-top rank-' . ($i + 1) . ' ' . ($isFirst ? 'rank-1' : '') . '">
                    <div class="rank-badge ' . $rankClasses[$i] . '"><span class="medal">' . $medal . '</span> ' . ($i + 1) . ' место</div>
                    <div class="score-circle">' . $att . '%</div>
                    <div class="avatar-wrap">
                        <span class="avatar-fallback" aria-hidden="true">' . $initial . '</span>' . $photo_image . '
                    </div>
                    <div class="info">
                        <h4>' . $escape($fio) . '</h4>
                        <p>' . $escape($class_name) . '</p>
                    </div>
                </div>';
            }
        }
        ?>
    </div>

    <div class="fade-up">
        <div class="carousel-track" id="studentCarousel">
            <?php
            if (count($topData) > 3) {
                $rank = 4;
                for ($i = 3; $i < count($topData) && $i < 10; $i++) {
                    $row = $topData[$i];
                    $fio = trim($row['surname'] . ' ' . $row['name']);
                    $class_name = $row['grade'] . $row['class'];
                    $att = round((float)$row['attendance'], 2);
                    $photoName = trim((string)$row['photo']);
                    if ($photoName === '' || !preg_match('/\A[a-zA-Z0-9._-]+\z/', $photoName) || $photoName === '.' || $photoName === '..') {
                        $photoName = 'default.jpg';
                    }
                    $photo = $escape('img/students/' . $photoName);
                    $photo_available = is_file(__DIR__ . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'students' . DIRECTORY_SEPARATOR . $photoName);
                    $initial = $escape(mb_strtoupper(mb_substr($fio, 0, 1, 'UTF-8'), 'UTF-8'));
                    $photo_image = $photo_available
                        ? '<img src="' . $photo . '" alt="' . $escape($fio) . '" loading="lazy" onerror="this.onerror=null;this.remove();">'
                        : '';

                    echo '<div class="student-card-sm">
                        <div class="score-badge">' . $att . '%</div>
                        <div class="avatar-wrap">
                            <span class="avatar-fallback" aria-hidden="true">' . $initial . '</span>' . $photo_image . '
                        </div>
                        <div class="info">
                            <h4>' . $escape($fio) . '</h4>
                            <p>' . $escape($class_name) . ' • #' . $rank . '</p>
                        </div>
                    </div>';
                    $rank++;
                }
            }
            ?>
        </div>
    </div>
</section>

<!-- ============ ACHIEVEMENTS ============ -->
<section class="section" style="background:var(--bg-main);border-radius:var(--radius-lg);padding:50px 44px;">
    <div class="section-header fade-up">
        <div class="tag">Достижения</div>
        <h2>Наши <span class="highlight">победы</span></h2>
        <p>Гордость школы — достижения наших учеников</p>
    </div>

    <div class="tabs fade-up" id="achievementTabs">
        <button class="tab active" data-tab="school">Школьные</button>
        <button class="tab" data-tab="international">Международные</button>
        <button class="tab" data-tab="sport">Спорт</button>
    </div>

    <div class="achievements-grid" id="achievementGrid"></div>
</section>

<!-- ============ CLUBS ============ -->
<section class="section">
    <div class="section-header fade-up">
        <div class="tag">Кружки</div>
        <h2>Наши <span class="highlight">кружки</span></h2>
        <p>В наших кружках всегда интересно и увлекательно!</p>
    </div>
    <div class="clubs-grid" id="clubsGrid"></div>
</section>

<!-- ============ PARTNERS ============ -->
<section class="section" style="background:var(--bg-card);border-radius:var(--radius-lg);padding:50px 44px;">
    <div class="section-header fade-up">
        <div class="tag">Партнёры</div>
        <h2>Нам <span class="highlight">доверяют</span></h2>
        <p>Ведущие учебные заведения выбирают СКАН</p>
    </div>
    <div class="partners-grid fade-up">
        <span class="partner-item">Школа №1</span>
        <span class="partner-item">Лицей «Интеллект»</span>
        <span class="partner-item">Гимназия №5</span>
        <span class="partner-item">Академия «Знание»</span>
        <span class="partner-item">EduTech Hub</span>
    </div>
</section>

<!-- ============ TESTIMONIALS ============ -->
<section class="section" style="background:var(--bg-main);border-radius:var(--radius-lg);padding:50px 44px;" id="testimonials">
    <div class="section-header fade-up">
        <div class="tag">Отзывы</div>
        <h2>Что говорят <span class="highlight">о нас</span></h2>
        <p>Мнения наших партнёров и пользователей</p>
    </div>
    <div class="testimonials-grid" id="testimonialsGrid"></div>
</section>

<!-- ============ CTA ============ -->
<section class="section">
    <div class="cta-section fade-up">
        <h2>Готовы начать?</h2>
        <p>Присоединяйтесь к СКАН и откройте новые возможности для мониторинга образования.</p>
        <a href="statistics.php" class="btn btn-cta">
            <i class="fas fa-rocket"></i> Начать сейчас
        </a>
    </div>
</section>

<!-- ============ FOOTER ============ -->
<footer class="footer">
    <div>
        <div class="brand">
            <div class="brand-icon">С</div>
            <span class="brand-name">СКАН</span>
        </div>
        <p class="desc">Современная платформа для мониторинга образования с использованием передовых технологий.</p>
    </div>
    <div>
        <h4>Продукт</h4>
        <a href="statistics.php">Статистика</a>
        <a href="schedule.php">Расписание</a>
        <a href="rating_student.php">Рейтинг</a>
        <a href="#">Поддержка</a>
    </div>
    <div>
        <h4>Компания</h4>
        <a href="#">О нас</a>
        <a href="#">Блог</a>
        <a href="#">Вакансии</a>
        <a href="#">Контакты</a>
    </div>
    <div>
        <h4>Юридическое</h4>
        <a href="#">Политика конфиденциальности</a>
        <a href="#">Условия использования</a>
        <a href="#">Лицензия</a>
    </div>
    <div class="copyright">© 2026 СКАН. Все права защищены.</div>
</footer>

<?php require_once("js.php"); ?>

<script>
    // ============ SLIDER ============
    (function() {
        const slides = document.querySelectorAll('.hero-slider .slide');
        const dotsContainer = document.querySelector('.slider-dots');
        let currentSlide = 0;
        let interval;

        if (slides.length > 0) {
            slides.forEach((_, index) => {
                const dot = document.createElement('button');
                dot.className = 'dot' + (index === 0 ? ' active' : '');
                dot.dataset.index = index;
                dot.addEventListener('click', () => goToSlide(index));
                dotsContainer.appendChild(dot);
            });

            window.goToSlide = function(index) {
                slides.forEach((slide, i) => { slide.style.display = i === index ? 'flex' : 'none'; });
                document.querySelectorAll('.slider-dots .dot').forEach((dot, i) => { dot.classList.toggle('active', i === index); });
                currentSlide = index;
            }

            function nextSlide() { window.goToSlide((currentSlide + 1) % slides.length); }
            function startSlider() {
                if (!interval) interval = setInterval(nextSlide, 4000);
            }
            function stopSlider() {
                clearInterval(interval);
                interval = null;
            }

            window.goToSlide(0);
            startSlider();
            const slider = document.querySelector('.hero-slider');
            slider.addEventListener('mouseenter', stopSlider);
            slider.addEventListener('mouseleave', startSlider);
        }
    })();

    // ============ SCROLL ANIMATIONS ============
    document.addEventListener('DOMContentLoaded', function() {
        if (!('IntersectionObserver' in window)) {
            document.querySelectorAll('.fade-up').forEach(el => el.classList.add('visible'));
            return;
        }

        document.querySelectorAll('.fade-up').forEach(el => {
            new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) entry.target.classList.add('visible');
                });
            }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' }).observe(el);
        });
    });

    // ============ SHOW/HIDE ROWS ============
    document.addEventListener('DOMContentLoaded', function() {
        const rows = document.querySelectorAll('#attendanceTable tbody tr');
        const limit = 30;
        const showBtn = document.getElementById('showAllBtn');
        const collapseBtn = document.getElementById('collapseBtn');

        if (showBtn && rows.length > limit) {
            showBtn.style.display = 'inline-flex';
        } else if (showBtn) {
            showBtn.style.display = 'none';
        }

        if (showBtn) {
            showBtn.addEventListener('click', function() {
                rows.forEach(r => r.style.display = '');
                showBtn.style.display = 'none';
                if (collapseBtn) collapseBtn.style.display = 'inline-flex';
            });
        }
        if (collapseBtn) {
            collapseBtn.addEventListener('click', function() {
                rows.forEach((r, i) => { if (i >= limit) r.style.display = 'none'; });
                collapseBtn.style.display = 'none';
                if (showBtn) showBtn.style.display = 'inline-flex';
            });
        }

        const summaryBtn = document.getElementById('summaryBtn');
        if (summaryBtn) summaryBtn.addEventListener('click', submitAttendance);
    });

    // ============ ATTENDANCE SUBMIT ============
    let attendanceChart;
    let chartCounts = [<?= $in_time ?>, <?= $absent ?>, <?= $late ?>];

    function submitAttendance() {
        const rows = document.querySelectorAll('#attendanceTable tbody tr');
        const tbody = document.getElementById('resultBody');
        const resultTable = document.getElementById('resultTable');
        const counts = { 'Присутствует': 0, 'Отсутствует': 0, 'Опоздал': 0 };
        const summaryRows = [];

        rows.forEach(row => {
            if (row.cells.length < 6) return;

            const name = row.cells[0].innerText.trim();
            const cls = row.cells[1].innerText.trim();
            const timeInput = row.querySelector('input[type="time"]');
            const time = timeInput ? timeInput.value : '';
            const selectedStatus = row.querySelector('input[type="radio"]:checked');
            if (!selectedStatus || counts[selectedStatus.value] === undefined) return;

            const status = selectedStatus.value;
            const summaryRow = document.createElement('tr');
            const nameCell = document.createElement('td');
            const nameStrong = document.createElement('strong');
            nameStrong.textContent = name;
            nameCell.appendChild(nameStrong);

            const classCell = document.createElement('td');
            classCell.textContent = cls;
            const timeCell = document.createElement('td');
            timeCell.textContent = time;
            const statusCell = document.createElement('td');
            const statusBadge = document.createElement('span');
            statusBadge.className = 'status-badge ' + ({
                'Присутствует': 'status-present',
                'Отсутствует': 'status-absent',
                'Опоздал': 'status-late'
            })[status];
            statusBadge.textContent = status;
            statusCell.appendChild(statusBadge);

            summaryRow.append(nameCell, classCell, timeCell, statusCell);
            summaryRows.push(summaryRow);
            counts[status]++;
        });

        while (tbody.firstChild) {
            tbody.removeChild(tbody.firstChild);
        }
        chartCounts = [counts['Присутствует'], counts['Отсутствует'], counts['Опоздал']];

        if (summaryRows.length === 0) {
            resultTable.style.display = 'none';
        } else {
            summaryRows.forEach(row => tbody.appendChild(row));
            resultTable.style.display = 'block';
            resultTable.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        if (attendanceChart) {
            attendanceChart.data.datasets[0].data = chartCounts;
            attendanceChart.update();
        }
    }

    // ============ CHART ============
    function initChart() {
        const canvas = document.getElementById('attendanceChart');
        if (!canvas || typeof Chart === 'undefined') return;
        
        const ctx = canvas.getContext('2d');
        attendanceChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Присутствует', 'Отсутствует', 'Опоздал'],
                datasets: [{
                    data: chartCounts,
                    backgroundColor: ['rgba(16, 185, 129, 0.8)', 'rgba(239, 68, 68, 0.8)', 'rgba(245, 158, 11, 0.8)'],
                    borderColor: ['#10b981', '#ef4444', '#f59e0b'],
                    borderWidth: 2,
                    hoverOffset: 14
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '65%',
                plugins: {
                    legend: { 
                        position: 'bottom', 
                        labels: { font: { size: 13, weight: '600', family: 'Inter' }, usePointStyle: true, padding: 20 } 
                    },
                    tooltip: { 
                        backgroundColor: 'rgba(15,23,42,0.9)', 
                        titleFont: { size: 14, family: 'Inter' }, 
                        bodyFont: { size: 13, family: 'Inter' },
                        padding: 12,
                        cornerRadius: 8
                    }
                }
            }
        });
    }

    const chartScript = document.createElement('script');
    chartScript.src = 'https://cdn.jsdelivr.net/npm/chart.js';
    chartScript.async = true;
    chartScript.onload = initChart;
    chartScript.onerror = function() {
        const canvas = document.getElementById('attendanceChart');
        if (canvas) {
            const message = document.createElement('p');
            message.textContent = 'График временно недоступен.';
            message.setAttribute('role', 'status');
            canvas.replaceWith(message);
        }
    };
    document.head.appendChild(chartScript);

    // ============ ACHIEVEMENTS DATA ============
    const achievementsData = {
        school: [
            { img: 'img/d3.jpg', title: 'Наши победы', desc: 'В школьных олимпиадах активно приняли участие учащиеся разных классов. Многие показали отличные знания по математике, физике, литературе и информатике.' },
            { img: 'img/d1.jpg', title: 'Призовые места', desc: 'Благодаря усердию и настойчивости наши ученики заняли призовые места и доказали высокий уровень подготовки.' },
            { img: 'img/d2.jpg', title: 'Гордость школы', desc: 'Результаты олимпиад вдохновляют других учеников и становятся примером стремления к знаниям.' }
        ],
        international: [
            { img: 'img/d4.jpg', title: 'Успехи за рубежом', desc: 'Наши школьники достойно представили страну на олимпиадах по естественным и гуманитарным наукам.' },
            { img: 'img/d3.jpg', title: 'Победители', desc: 'Их упорный труд и стремление к знаниям позволили завоевать медали и грамоты на мировом уровне.' },
            { img: 'img/d2.jpg', title: 'Признание', desc: 'Эти достижения стали не только гордостью школы, но и примером для будущих поколений учеников.' }
        ],
        sport: [
            { img: 'img/d8.jpg', title: 'Командные игры', desc: 'Наши школьные команды показали мастерство в футболе и баскетболе, заслужив уважение соперников.' },
            { img: 'img/d7.jpg', title: 'Лёгкая атлетика', desc: 'Ученики завоевали медали в беге, прыжках и эстафетах, проявив силу духа и выносливость.' },
            { img: 'img/d5.jpg', title: 'Гордость школы', desc: 'Эти победы укрепили командный дух и стали символом активной школьной жизни.' }
        ]
    };

    function renderAchievements(tab = 'school') {
        const grid = document.getElementById('achievementGrid');
        const data = achievementsData[tab] || achievementsData.school;
        const tags = { school: 'Школьные', international: 'Международные', sport: 'Спорт' };
        let html = '';
        data.forEach(item => {
            html += `<div class="achievement-card">
                <img src="${item.img}" alt="${item.title}">
                <div class="content">
                    <span class="tag">${tags[tab]}</span>
                    <h4>${item.title}</h4>
                    <p>${item.desc}</p>
                </div>
            </div>`;
        });
        grid.innerHTML = html;
    }

    // ============ CLUBS DATA ============
    const clubsData = [
        { name: 'Шахматы', teacher: 'Норматов М', icon: 'fa-chess-king', time: 'Ср/Пт 13:30-15:30', link: 'chess.php' },
        { name: 'Программирование', teacher: 'Ачилов Х', icon: 'fa-code', time: 'Ср/Пт 13:30-15:30', link: 'prog.php' },
        { name: 'Химия', teacher: 'Шафиев Н', icon: 'fa-flask', time: 'Ср/Пт 13:30-15:30', link: 'chemistry.php' },
        { name: 'Экономика', teacher: 'Юнусова Ш', icon: 'fa-chart-line', time: 'Ср/Пт 13:30-15:30', link: 'economy.php' }
    ];

    function renderClubs() {
        const grid = document.getElementById('clubsGrid');
        let html = '';
        clubsData.forEach(c => {
            html += `<div class="club-card">
                <div class="icon"><i class="fas ${c.icon}"></i></div>
                <h4>${c.name}</h4>
                <div class="teacher"><i class="fas fa-user"></i> ${c.teacher}</div>
                <div class="time"><i class="fas fa-clock"></i> ${c.time}</div>
                <a href="${c.link}" class="btn-club">Узнать больше <i class="fas fa-arrow-right"></i></a>
            </div>`;
        });
        grid.innerHTML = html;
    }

    // ============ TESTIMONIALS DATA ============
    const testimonialsData = [
        { name: 'Анна Сергеева', role: 'Директор школы №1', text: 'СКАН полностью изменил подход к учёту посещаемости в нашей школе. Система работает безупречно.', stars: 5 },
        { name: 'Михаил Петров', role: 'Учитель математики', text: 'Благодаря СКАН я могу мгновенно видеть статистику посещаемости моего класса и принимать оперативные меры.', stars: 5 },
        { name: 'Елена Иванова', role: 'Родитель', text: 'Теперь я всегда знаю, когда мой ребёнок пришёл в школу. Это даёт мне спокойствие и уверенность.', stars: 5 }
    ];

    function renderTestimonials() {
        const grid = document.getElementById('testimonialsGrid');
        let html = '';
        testimonialsData.forEach(t => {
            const stars = '★'.repeat(t.stars) + '☆'.repeat(5 - t.stars);
            html += `<div class="testimonial-card">
                <div class="stars">${stars}</div>
                <blockquote>"${t.text}"</blockquote>
                <div class="author">
                    <div class="avatar-sm">${t.name[0]}</div>
                    <div>
                        <div class="name">${t.name}</div>
                        <div class="role">${t.role}</div>
                    </div>
                </div>
            </div>`;
        });
        grid.innerHTML = html;
    }

    // ============ INIT ============
    document.addEventListener('DOMContentLoaded', function() {
        renderAchievements('school');
        renderClubs();
        renderTestimonials();

        document.querySelectorAll('#achievementTabs .tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('#achievementTabs .tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                renderAchievements(this.dataset.tab);
            });
        });
    });
    async function sendAiMessage() {
  const input = document.getElementById('ai-user-input');
  const text = input.value.trim();
  if (!text) return;

  const chat = document.getElementById('ai-chat-box');
  chat.innerHTML += `
    <div class="flex justify-end">
      <div class="p-3 rounded-2xl bg-blue-600 text-white max-w-md">${text}</div>
    </div>
  `;
  input.value = '';
  chat.scrollTop = chat.scrollHeight;

  try {
    const res = await fetch('http://127.0.0.1:8000/ask', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ message: text })
    });
    const data = await res.json();

    chat.innerHTML += `
      <div class="flex gap-3">
        <div class="w-7 h-7 rounded-lg bg-purple-600 text-white flex items-center justify-center shrink-0">🤖</div>
        <div class="p-3 rounded-2xl bg-white/5 border border-white/10 text-gray-200">${data.reply}</div>
      </div>
    `;
  } catch (err) {
    chat.innerHTML += `<div class="text-xs text-rose-400">Ошибка подключения к локальному серверу Python.</div>`;
  }
  chat.scrollTop = chat.scrollHeight;
}
</script>

</body>
</html>