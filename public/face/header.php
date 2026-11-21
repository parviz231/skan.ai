<?php
// header.php - Общий заголовок для всех страниц
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Определяем язык
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$lang = $_SESSION['lang'] ?? 'ru';

// Подключаем языковые файлы
require_once("1.php");

// Данные для информационной панели
date_default_timezone_set("Asia/Dushanbe");
$year_start = 2026;
$year_end = 2027;
$school_year = "$year_start / $year_end";
$now = date("H:i");
$current = "Вне уроков";
$shift = "";

// ПРОВЕРКА НА ВЫХОДНОЙ ДЕНЬ
$dayOfWeek = date('N'); // 1 (Пн) - 7 (Вс)
$is_weekend = ($dayOfWeek == 7);

if ($is_weekend) {
    $current = "Сегодня Выходной!";
    $shift = "Отдыхаем";
} else {
    $schedule1 = [
        ["08:00", "08:45", "1 урок"], ["08:45", "08:50", "Перемена"],
        ["08:50", "09:35", "2 урок"], ["09:35", "09:40", "Перемена"],
        ["09:40", "10:25", "3 урок"], ["10:25", "10:35", "Перемена"],
        ["10:35", "11:20", "4 урок"], ["11:20", "11:25", "Перемена"],
        ["11:25", "12:10", "5 урок"], ["12:10", "12:15", "Перемена"],
        ["12:15", "13:00", "6 урок"]
    ];
    $schedule2 = [
        ["13:05", "13:50", "1 урок"], ["13:50", "13:55", "Перемена"],
        ["13:55", "14:40", "2 урок"], ["14:40", "14:45", "Перемена"],
        ["14:45", "15:30", "3 урок"], ["15:30", "15:45", "Перемена"],
        ["15:45", "16:30", "4 урок"], ["16:35", "16:40", "Перемена"],
        ["16:45", "17:30", "5 урок"], ["17:30", "17:35", "Перемена"],
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
}
$currentTime = date('H:i:s');
?>
<!DOCTYPE html>
<html class="no-js" lang="<?= $lang ?>">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>СКАН — Интеллектуальный мониторинг образования</title>
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
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<!-- ============ HEADER ============ -->
<div class="header-wrapper">
    <div class="header">
        <!-- Main header -->
        <div class="header-main">
            <!-- Logo -->
            <a href="index.php" class="header-logo">
                <div class="header-logo-icon">
                    <img src="img/logo-ritm.png" alt="СКАН">
                </div>
                <div class="header-logo-text">
                    <span class="header-logo-name">СКАН</span>
                    <span class="header-logo-sub">школьный портал</span>
                </div>
            </a>

            <!-- Navigation -->
            <nav class="header-nav" id="headerMainNav">
                <div class="header-nav-item">
                    <a href="index.php" class="header-nav-link active"><?= $texts[$lang]['home'] ?></a>
                </div>
                <div class="header-nav-item">
                    <a href="#" class="header-nav-link">
                        <?= $texts[$lang]['students'] ?>
                        <i class="fas fa-chevron-down header-chevron"></i>
                    </a>
                    <div class="header-dropdown">
                        <div class="header-dropdown-label"><?= $texts[$lang]['students'] ?></div>
                        <a href="schedule.php" class="header-dropdown-item">
                            <?= $texts[$lang]['schedule'] ?>
                            <i class="fas fa-arrow-right header-dd-arrow"></i>
                        </a>
                        <?php if(isset($_SESSION['id1'])){ ?>
                        <a href="student_prediction.php" class="header-dropdown-item">
                            <?= $texts[$lang]['predict'] ?>
                            <i class="fas fa-arrow-right header-dd-arrow"></i>
                        </a>
                        <?php } ?>
                        <a href="achievements.php" class="header-dropdown-item">
                            Достижения учеников
                            <i class="fas fa-arrow-right header-dd-arrow"></i>
                        </a>
                    </div>
                </div>
                <div class="header-nav-item">
                    <a href="#" class="header-nav-link">
                        <?= $texts[$lang]['menu_rating'] ?>
                        <i class="fas fa-chevron-down header-chevron"></i>
                    </a>
                    <div class="header-dropdown">
                        <div class="header-dropdown-label"><?= $texts[$lang]['menu_rating'] ?></div>
                        <a href="rating_student.php" class="header-dropdown-item">
                            <?= $texts[$lang]['menu_rating'] ?>
                            <i class="fas fa-arrow-right header-dd-arrow"></i>
                        </a>
                        <a href="rating_student2.php" class="header-dropdown-item">
                            <?= $texts[$lang]['menu_rating_status'] ?>
                            <i class="fas fa-arrow-right header-dd-arrow"></i>
                        </a>
                    </div>
                </div>
                <div class="header-nav-item">
                    <a href="#" class="header-nav-link">
                        <?= $texts[$lang]['stats'] ?>
                        <i class="fas fa-chevron-down header-chevron"></i>
                    </a>
                    <div class="header-dropdown">
                        <div class="header-dropdown-label"><?= $texts[$lang]['stats'] ?></div>
                        <a href="statistics.php" class="header-dropdown-item">
                            <?= $texts[$lang]['stats'] ?>
                            <i class="fas fa-arrow-right header-dd-arrow"></i>
                        </a>
                        <a href="statistics1.php" class="header-dropdown-item">
                            <?= $texts[$lang]['menu_current_attendance'] ?>
                            <i class="fas fa-arrow-right header-dd-arrow"></i>
                        </a>
                    </div>
                </div>
                <div class="header-nav-item">
                    <a href="lesson.php" class="header-nav-link"><?= $texts[$lang]['menu_current_lesson'] ?></a>
                </div>
                <!-- УМНАЯ ССЫЛКА НА ПРАКТИКУ -->
                <div class="header-nav-item">
                    <?php if(isset($_SESSION['is_teacher'])): ?>
                        <a href="teacher.php" class="header-nav-link"><?= $texts[$lang]['practice'] ?></a>
                    <?php else: ?>
                        <a href="practice.php" class="header-nav-link"><?= $texts[$lang]['practice'] ?></a>
                    <?php endif; ?>
                </div>
            </nav>

            <!-- User area -->
            <div class="header-user-area">
                <?php if(isset($_SESSION['id1']) && isset($_SESSION['username1'])){ ?>
                    <!-- УЧЕНИК -->
                    <div class="header-profile-wrapper">
                        <button class="header-profile-btn">
                            <span class="header-user-avatar"><?= mb_substr($_SESSION['username1'], 0, 1) ?></span>
                            <span class="header-user-name"><?= htmlspecialchars($_SESSION['username1']) ?></span>
                        </button>
                        <div class="header-profile-dropdown">
                            <a href="profile.php" class="header-profile-dropdown-item">
                                <i class="fas fa-user"></i>
                                Профиль
                            </a>
                            <div class="header-profile-dropdown-divider"></div>
                            <a href="logout.php" class="header-profile-dropdown-item header-danger">
                                <i class="fas fa-sign-out-alt"></i>
                                <?= $texts[$lang]['logout'] ?>
                            </a>
                        </div>
                    </div>
                <?php } elseif(isset($_SESSION['is_teacher'])) { ?>
                    <!-- УЧИТЕЛЬ (ЗАЩИТА ОТ ВЫХОДА) -->
                    <div class="header-profile-wrapper">
                        <button class="header-profile-btn">
                            <span class="header-user-avatar" style="background: #8b5cf6 !important;"><i class="fas fa-chalkboard-user"></i></span>
                            <span class="header-user-name"><?= htmlspecialchars($_SESSION['teacher_name'] ?? 'Учитель') ?></span>
                        </button>
                        <div class="header-profile-dropdown">
                            <a href="teacher_profile.php" class="header-profile-dropdown-item">
                                <i class="fas fa-chalkboard"></i>
                                Кабинет учителя
                            </a>
                            <div class="header-profile-dropdown-divider"></div>
                            <a href="logout.php" class="header-profile-dropdown-item header-danger">
                                <i class="fas fa-sign-out-alt"></i>
                                Выйти
                            </a>
                        </div>
                    </div>
                <?php } else { ?>
                    <!-- ГОСТЬ -->
                    <a href="login.php" class="header-profile-btn">
                        <span class="header-user-avatar"><i class="fas fa-user"></i></span>
                        <span class="header-user-name"><?= $texts[$lang]['login'] ?></span>
                    </a>
                <?php } ?>

                <!-- Language -->
                <div class="header-lang-switch">
                    <a href="?lang=ru" class="header-lang-btn <?= $lang == 'ru' ? 'active' : '' ?>">RU</a>
                    <a href="?lang=tj" class="header-lang-btn <?= $lang == 'tj' ? 'active' : '' ?>">TJ</a>
                </div>
            </div>

            <!-- Mobile -->
            <button class="header-mobile-btn" id="headerMobileBtn">
                <i class="fas fa-bars"></i>
            </button>
        </div>

        <!-- Info bar -->
        <div class="header-info-bar">
            <div class="header-info-item">
                <span class="header-info-icon"><i class="fas fa-clock"></i></span>
                <span class="header-info-label"><?= $texts[$lang]['time'] ?></span>
                <span class="header-info-value header-mono" id="headerClock"><?=$currentTime?></span>
            </div>
            <div class="header-info-item">
                <span class="header-info-icon"><i class="fas fa-book-open"></i></span>
                <span class="header-info-label"><?= $texts[$lang]['now']." ".$texts[$lang]['text1']?></span>
                <span class="header-info-value" <?php if($is_weekend) echo 'style="color: var(--header-danger);"'; ?>><?=$current?></span>
            </div>
            <div class="header-info-item">
                <span class="header-info-icon"><i class="fas fa-sync-alt"></i></span>
                <span class="header-info-label"><?=$texts[$lang]['shift']?></span>
                <span class="header-info-value"><?=$shift?></span>
            </div>
            <div class="header-info-item">
                <span class="header-info-icon"><i class="fas fa-calendar-alt"></i></span>
                <span class="header-info-label"><?=$texts[$lang]['year']?></span>
                <span class="header-info-value"><?=$school_year?></span>
            </div>
        </div>
    </div>
</div>

<style>
    :root {
        --header-bg: #f5f5f7;
        --header-surface: #ffffff;
        --header-surface-secondary: #f0f0f2;
        --header-text: #1d1d1f;
        --header-text-secondary: #6e6e73;
        --header-text-tertiary: #86868b;
        --header-border: #e5e5ea;
        --header-border-strong: #d2d2d7;
        --header-accent: #0071e3;
        --header-accent-hover: #0077ed;
        --header-accent-light: #e8f4fd;
        --header-success: #34c759;
        --header-success-light: #e8f8ed;
        --header-danger: #ff3b30;
        --header-danger-light: #fde8e7;
        --header-radius-sm: 6px;
        --header-radius-md: 8px;
        --header-radius-lg: 10px;
        --header-radius-full: 9999px;
        --header-shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.04);
        --header-shadow-md: 0 4px 16px rgba(0, 0, 0, 0.06);
        --header-shadow-lg: 0 12px 40px rgba(0, 0, 0, 0.1);
        --header-transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        --header-font-sans: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        --header-font-mono: 'JetBrains Mono', monospace;
    }

    .header-wrapper {
        width: 100%;
        max-width: 100%;
        margin: 0;
        padding: 0;
        position: relative;
        z-index: 1000;
    }

    .header {
        background: var(--header-surface);
        border-bottom: 1px solid var(--header-border);
        border-radius: 0;
        box-shadow: var(--header-shadow-sm);
        overflow: visible;
    }

    /* ============ MAIN HEADER ============ */
    .header-main {
        display: flex;
        align-items: center;
        gap: 20px;
        padding: 16px 24px;
        position: relative;
        max-width: 1280px;
        margin: 0 auto;
    }

    /* Logo */
    .header-logo {
        display: flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
        color: var(--header-text);
        flex-shrink: 0;
    }

    .header-logo-icon {
        width: 40px;
        height: 40px;
        border-radius: var(--header-radius-md);
        background: var(--header-text);
        color: var(--header-surface);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 17px;
        letter-spacing: -0.03em;
        transition: all var(--header-transition);
        overflow: hidden;
    }

    .header-logo-icon img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .header-logo:hover .header-logo-icon {
        background: var(--header-accent);
        transform: scale(1.04);
    }

    .header-logo-text {
        display: flex;
        flex-direction: column;
        line-height: 1.2;
    }

    .header-logo-name {
        font-size: 19px;
        font-weight: 700;
        letter-spacing: -0.04em;
        color: var(--header-text);
    }

    .header-logo-sub {
        font-size: 9px;
        color: var(--header-text-tertiary);
        font-weight: 500;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    /* Navigation */
    .header-nav {
        display: flex;
        align-items: center;
        gap: 4px;
        margin: 0 auto;
        flex-shrink: 1;
        min-width: 0;
        list-style: none;
        padding: 0;
    }

    .header-nav-item {
        position: relative;
        list-style: none;
    }

    .header-nav-link {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        color: var(--header-text-secondary);
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        letter-spacing: -0.01em;
        transition: color var(--header-transition);
        white-space: nowrap;
        cursor: pointer;
        position: relative;
        background: transparent;
        border: none;
    }

    .header-nav-link::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%) scaleX(0);
        width: 100%;
        height: 2px;
        background: var(--header-accent);
        border-radius: 1px;
        transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        transform-origin: right;
    }

    .header-nav-link:hover {
        color: var(--header-text);
    }

    .header-nav-link:hover::after {
        left: 50%;
        transform: translateX(-50%) scaleX(0.7);
        transform-origin: left;
    }

    .header-nav-link.active {
        color: var(--header-text);
        font-weight: 600;
    }

    .header-nav-link.active::after {
        left: 50%;
        transform: translateX(-50%) scaleX(0.5);
        transform-origin: center;
    }

    .header-nav-link .header-chevron {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 9px;
        color: var(--header-text-tertiary);
        transition: all var(--header-transition);
    }

    .header-nav-item:hover .header-nav-link .header-chevron {
        transform: rotate(180deg);
        color: var(--header-text);
    }

    /* Dropdown */
    .header-dropdown {
        position: absolute;
        top: calc(100% + 10px);
        left: 50%;
        transform: translateX(-50%) translateY(6px);
        min-width: 280px;
        background: var(--header-surface);
        border: 1px solid var(--header-border);
        border-radius: var(--header-radius-md);
        padding: 12px;
        opacity: 0;
        visibility: hidden;
        transition: all var(--header-transition);
        z-index: 200;
        box-shadow: var(--header-shadow-lg);
    }

    .header-dropdown::before {
        content: '';
        position: absolute;
        top: -5px;
        left: 50%;
        transform: translateX(-50%) rotate(45deg);
        width: 10px;
        height: 10px;
        background: var(--header-surface);
        border-left: 1px solid var(--header-border);
        border-top: 1px solid var(--header-border);
    }

    .header-nav-item:hover .header-dropdown {
        opacity: 1;
        visibility: visible;
        transform: translateX(-50%) translateY(0);
    }

    .header-dropdown-label {
        padding: 8px 14px 6px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--header-text-tertiary);
        border-bottom: 1px solid var(--header-border);
        margin-bottom: 4px;
    }

    .header-dropdown-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 11px 14px;
        border-radius: var(--header-radius-sm);
        color: var(--header-text-secondary);
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
        letter-spacing: -0.01em;
        transition: all var(--header-transition);
        position: relative;
    }

    .header-dropdown-item::before {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%) scaleY(0);
        width: 2px;
        height: 60%;
        background: var(--header-accent);
        border-radius: 1px;
        transition: transform 0.25s var(--header-transition);
    }

    .header-dropdown-item:hover {
        color: var(--header-text);
        background: var(--header-surface-secondary);
        padding-left: 20px;
    }

    .header-dropdown-item:hover::before {
        transform: translateY(-50%) scaleY(1);
    }

    .header-dropdown-item .header-dd-arrow {
        font-size: 10px;
        color: var(--header-accent);
        opacity: 0;
        transition: all var(--header-transition);
        transform: translateX(-4px);
    }

    .header-dropdown-item:hover .header-dd-arrow {
        opacity: 1;
        transform: translateX(0);
    }

    /* ============ PROFILE ============ */
    .header-user-area {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-shrink: 0;
    }

    .header-profile-btn {
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
        padding: 2px 12px 2px 2px !important;
        border-radius: 9999px !important;
        background: #ffffff !important;
        border: 1px solid #e5e5ea !important;
        cursor: pointer !important;
        text-decoration: none !important;
        color: #1d1d1f !important;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif !important;
        font-size: 12px !important;
        font-weight: 500 !important;
        letter-spacing: -0.01em !important;
        line-height: normal !important;
        box-shadow: none !important;
        text-transform: none !important;
        min-height: auto !important;
        min-width: auto !important;
        width: auto !important;
        height: auto !important;
        margin: 0 !important;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }

    .header-profile-btn:hover {
        border-color: #d2d2d7 !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04) !important;
        background: #ffffff !important;
    }

    .header-profile-btn .header-user-avatar {
        width: 34px !important;
        height: 34px !important;
        border-radius: 50% !important;
        background: #1d1d1f !important;
        color: #ffffff !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 13px !important;
        font-weight: 600 !important;
        flex-shrink: 0 !important;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
        border: none !important;
        box-shadow: none !important;
        text-transform: none !important;
        padding: 0 !important;
        margin: 0 !important;
        line-height: 1 !important;
    }

    .header-profile-btn:hover .header-user-avatar {
        background: #0071e3 !important;
        transform: scale(1.05) !important;
    }

    .header-profile-btn .header-user-avatar i {
        font-size: 13px !important;
        color: #ffffff !important;
        line-height: 1 !important;
    }

    .header-profile-btn .header-user-name {
        font-size: 12px !important;
        font-weight: 500 !important;
        letter-spacing: -0.01em !important;
        padding-left: 2px !important;
        padding-right: 0 !important;
        color: #1d1d1f !important;
        background: transparent !important;
        border: none !important;
        margin: 0 !important;
        line-height: 1 !important;
        text-transform: none !important;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif !important;
    }

    .header-profile-wrapper {
        position: relative !important;
        flex-shrink: 0 !important;
        display: inline-block !important;
    }

    .header-profile-dropdown {
        position: absolute !important;
        top: calc(100% + 10px) !important;
        right: 0 !important;
        min-width: 220px !important;
        background: #ffffff !important;
        border: 1px solid #e5e5ea !important;
        border-radius: 8px !important;
        padding: 8px !important;
        opacity: 0 !important;
        visibility: hidden !important;
        transform: translateY(6px) !important;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
        z-index: 300 !important;
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.1) !important;
    }

    .header-profile-dropdown::before {
        content: '' !important;
        position: absolute !important;
        top: -5px !important;
        right: 16px !important;
        width: 10px !important;
        height: 10px !important;
        background: #ffffff !important;
        border-left: 1px solid #e5e5ea !important;
        border-top: 1px solid #e5e5ea !important;
        transform: rotate(45deg) !important;
        display: block !important;
    }

    .header-profile-wrapper:hover .header-profile-dropdown {
        opacity: 1 !important;
        visibility: visible !important;
        transform: translateY(0) !important;
    }

    .header-profile-dropdown-item {
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
        padding: 11px 14px !important;
        border-radius: 6px !important;
        color: #6e6e73 !important;
        text-decoration: none !important;
        font-size: 12px !important;
        font-weight: 500 !important;
        letter-spacing: -0.01em !important;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
        cursor: pointer !important;
        background: none !important;
        border: none !important;
        width: 100% !important;
        text-align: left !important;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif !important;
        position: relative !important;
        margin: 0 !important;
        line-height: normal !important;
        box-shadow: none !important;
        text-transform: none !important;
    }

    .header-profile-dropdown-item::before {
        content: '' !important;
        position: absolute !important;
        left: 0 !important;
        top: 50% !important;
        transform: translateY(-50%) scaleY(0) !important;
        width: 2px !important;
        height: 60% !important;
        background: #0071e3 !important;
        border-radius: 1px !important;
        transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }

    .header-profile-dropdown-item:hover {
        color: #1d1d1f !important;
        background: #f0f0f2 !important;
        padding-left: 20px !important;
    }

    .header-profile-dropdown-item:hover::before {
        transform: translateY(-50%) scaleY(1) !important;
    }

    .header-profile-dropdown-item.header-danger::before {
        background: #ff3b30 !important;
    }

    .header-profile-dropdown-item.header-danger:hover {
        color: #ff3b30 !important;
        background: #fde8e7 !important;
    }

    .header-profile-dropdown-item i {
        font-size: 12px !important;
        width: 18px !important;
        text-align: center !important;
        color: #86868b !important;
        transition: color 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
        margin: 0 !important;
        line-height: 1 !important;
    }

    .header-profile-dropdown-item:hover i {
        color: #0071e3 !important;
    }

    .header-profile-dropdown-item.header-danger:hover i {
        color: #ff3b30 !important;
    }

    .header-profile-dropdown-divider {
        height: 1px !important;
        background: #e5e5ea !important;
        margin: 4px 8px !important;
        border: none !important;
    }

    /* Language */
    .header-lang-switch {
        display: flex;
        align-items: center;
        gap: 0;
        padding: 3px;
        border-radius: var(--header-radius-sm);
        background: var(--header-surface-secondary);
        border: 1px solid var(--header-border);
    }

    .header-lang-btn {
        padding: 5px 12px;
        border-radius: 4px;
        font-size: 10px;
        font-weight: 600;
        letter-spacing: 0.02em;
        color: var(--header-text-tertiary);
        text-decoration: none;
        transition: all var(--header-transition);
        position: relative;
    }

    .header-lang-btn.active {
        background: var(--header-surface);
        color: var(--header-text);
        box-shadow: var(--header-shadow-sm);
    }

    .header-lang-btn:not(.active):hover {
        color: var(--header-text);
    }

    /* Mobile */
    .header-mobile-btn {
        display: none;
        width: 38px;
        height: 38px;
        border-radius: var(--header-radius-sm);
        border: 1px solid var(--header-border);
        background: var(--header-surface);
        cursor: pointer;
        color: var(--header-text);
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        transition: all var(--header-transition);
    }

    .header-mobile-btn:hover {
        background: var(--header-surface-secondary);
    }

    /* ============ INFO BAR ============ */
    .header-info-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0;
        border-top: 1px solid var(--header-border);
        background: var(--header-surface);
        color: var(--header-text);
        max-width: 1280px;
        margin: 0 auto;
        padding: 0 24px;
    }

    .header-info-item {
        display: flex;
        align-items: center;
        gap: 10px;
        flex: 1;
        justify-content: center;
        white-space: nowrap;
        padding: 8px 12px;
        position: relative;
    }

    .header-info-item:not(:last-child)::after {
        content: '';
        position: absolute;
        right: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 1px;
        height: 28px;
        background: var(--header-border);
    }

    .header-info-item .header-info-icon {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        background: var(--header-surface-secondary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        color: var(--header-text-secondary);
        flex-shrink: 0;
    }

    .header-info-item .header-info-label {
        font-size: 13px;
        color: var(--header-text-secondary);
        font-weight: 500;
        letter-spacing: -0.01em;
    }

    .header-info-item .header-info-value {
        font-size: 16px;
        font-weight: 600;
        color: var(--header-text);
        letter-spacing: -0.01em;
    }

    .header-info-item .header-info-value.header-mono {
        font-family: var(--header-font-mono);
        font-size: 15px;
        letter-spacing: 0.02em;
        color: var(--header-accent);
    }

    /* ============ RESPONSIVE ============ */
    @media (max-width: 1100px) {
        .header-nav {
            gap: 2px;
        }
        .header-nav-link {
            padding: 8px 12px;
            font-size: 13px;
        }
        .header-info-bar {
            padding: 0 16px;
        }
        .header-info-item {
            padding: 6px 8px;
            gap: 6px;
        }
        .header-info-item .header-info-label {
            font-size: 12px;
        }
        .header-info-item .header-info-value {
            font-size: 14px;
        }
    }

    @media (max-width: 960px) {
        .header-nav {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: var(--header-surface);
            border: 1px solid var(--header-border);
            border-radius: var(--header-radius-md);
            padding: 8px;
            z-index: 200;
            flex-direction: column;
            align-items: stretch;
            box-shadow: var(--header-shadow-lg);
            gap: 2px;
        }
        .header-nav.open {
            display: flex;
        }
        .header-mobile-btn {
            display: flex;
        }
        .header-dropdown {
            position: static;
            transform: none;
            opacity: 1;
            visibility: visible;
            box-shadow: none;
            border: none;
            padding-left: 16px;
            display: none;
            min-width: auto;
        }
        .header-dropdown::before {
            display: none;
        }
        .header-nav-item:hover .header-dropdown {
            display: block;
        }
        .header-info-bar {
            flex-wrap: wrap;
            gap: 4px;
            padding: 8px 16px;
        }
        .header-info-item {
            flex: 1 1 45%;
            justify-content: center;
            padding: 4px 6px;
        }
        .header-info-item:not(:last-child)::after {
            display: none;
        }
        .header-main {
            padding: 12px 16px;
        }
        .header-info-item .header-info-label {
            font-size: 11px;
        }
        .header-info-item .header-info-value {
            font-size: 13px;
        }
    }

    @media (max-width: 600px) {
        .header-main {
            padding: 10px 12px;
            gap: 8px;
        }
        .header-logo-sub {
            display: none;
        }
        .header-logo-name {
            font-size: 16px;
        }
        .header-user-name {
            display: none;
        }
        .header-lang-switch {
            display: none;
        }
        .header-info-item {
            flex: 1 1 100%;
            padding: 3px 4px;
            gap: 6px;
        }
        .header-info-item .header-info-label {
            font-size: 11px;
        }
        .header-info-item .header-info-value {
            font-size: 13px;
        }
        .header-info-item .header-info-value.header-mono {
            font-size: 12px;
        }
        .header-info-item .header-info-icon {
            width: 20px;
            height: 20px;
            font-size: 10px;
        }
        .header-profile-btn {
            padding: 2px 8px 2px 2px !important;
        }
        .header-profile-btn .header-user-avatar {
            width: 30px !important;
            height: 30px !important;
            font-size: 11px !important;
        }
    }
</style>

<script>
    // Clock
    function updateHeaderClock() {
        const now = new Date();
        const h = String(now.getHours()).padStart(2, '0');
        const m = String(now.getMinutes()).padStart(2, '0');
        const s = String(now.getSeconds()).padStart(2, '0');
        document.getElementById('headerClock').textContent = `${h}:${m}:${s}`;
    }
    updateHeaderClock();
    setInterval(updateHeaderClock, 1000);

    // Mobile menu
    const headerMobileBtn = document.getElementById('headerMobileBtn');
    const headerNav = document.getElementById('headerMainNav');

    if (headerMobileBtn && headerNav) {
        headerMobileBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            headerNav.classList.toggle('open');
        });

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.header-mobile-btn') && !e.target.closest('.header-nav')) {
                headerNav.classList.remove('open');
            }
        });

        window.addEventListener('resize', function() {
            if (window.innerWidth > 960) {
                headerNav.classList.remove('open');
            }
        });
    }
</script>