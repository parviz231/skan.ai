<?php
require_once(__DIR__ . "/practice_data.php");

$labIds = [1, 4, 11, 12, 14, 15, 19, 22, 23, 26, 31, 37];
$totalLabs = count($labIds);

// Проверка, вошел ли учитель
$isTeacher = isset($_SESSION['is_teacher']) && $_SESSION['is_teacher'] === true;
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Практика — <?= $pageTitle ?? 'EduMind' ?> — СКАН</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'], mono: ['JetBrains Mono', 'monospace'] },
          colors: {
            brand: { 50: '#e8f4fd', 100: '#d0e8fb', 500: '#0071e3', 600: '#0077ed', 700: '#005bb5' },
            surface: { bg: '#f5f5f7', card: '#ffffff', alt: '#f0f0f2', border: '#e5e5ea', border2: '#d2d2d7' },
            ink: { 900: '#1d1d1f', 600: '#6e6e73', 400: '#86868b' },
            ok: '#34c759', bad: '#ff3b30', warn: '#ff9500',
          },
          borderRadius: { sm: '6px', md: '8px', lg: '12px', xl: '16px', '2xl': '20px', '3xl': '24px' },
          boxShadow: { sm: '0 1px 3px rgba(0,0,0,0.06)', md: '0 4px 16px rgba(0,0,0,0.06)', lg: '0 12px 40px rgba(0,0,0,0.10)' }
        }
      }
    }
  </script>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

  <style>
    :root {
      --bg: #f5f5f7; --surface: #ffffff; --surface-2: #f0f0f2;
      --text: #1d1d1f; --text-2: #6e6e73; --text-3: #86868b;
      --border: #e5e5ea; --border-2: #d2d2d7;
      --accent: #0071e3; --accent-2: #0077ed; --accent-lt: #e8f4fd;
      --ok: #34c759; --bad: #ff3b30; --warn: #ff9500;
      --radius-sm: 6px; --radius-md: 8px; --radius-lg: 12px; --radius-full: 9999px;
      --shadow-sm: 0 1px 3px rgba(0,0,0,0.04); --shadow-md: 0 4px 16px rgba(0,0,0,0.06);
      --transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }

    html, body { background: var(--bg); color: var(--text); font-family: 'Inter', sans-serif; overflow-x: hidden; min-height: 100vh; }
    .bg-blob { position: fixed; border-radius: 50%; filter: blur(120px); pointer-events: none; z-index: 0; opacity: 0.5; }

    /* --- HEADER --- */
    .app-header { background: rgba(255, 255, 255, 0.85); backdrop-filter: saturate(180%) blur(20px); -webkit-backdrop-filter: saturate(180%) blur(20px); border-bottom: 1px solid var(--border); }
    
    .nav-pill { display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: var(--radius-md); font-size: 13px; font-weight: 500; color: var(--text-2); transition: all var(--transition); text-decoration: none; border: 1px solid transparent; }
    .nav-pill:hover { color: var(--text); background: var(--surface-2); }
    .nav-pill.active { color: var(--text); background: var(--surface-2); font-weight: 600; }
    
    /* Кнопки в шапке */
    .header-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: var(--radius-md); font-size: 13px; font-weight: 600; text-decoration: none; transition: all var(--transition); cursor: pointer; }
    .header-btn-primary { background: var(--accent); color: #fff; }
    .header-btn-primary:hover { background: var(--accent-2); transform: translateY(-1px); box-shadow: var(--shadow-sm); }
    .header-btn-ghost { background: transparent; border: 1px solid var(--border-2); color: var(--text); }
    .header-btn-ghost:hover { background: var(--surface-2); border-color: var(--text-3); }
    .header-btn-teacher { background: #eef2ff; border: 1px solid #c7d2fe; color: #4f46e5; }
    .header-btn-teacher:hover { background: #e0e7ff; border-color: #a5b4fc; }

    /* Плавные анимации */
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    .animate-fade-up { animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) both; }
    .animate-fade-in { animation: fadeIn 0.8s ease-out both; }
    .delay-100 { animation-delay: 100ms; } .delay-200 { animation-delay: 200ms; }
    .delay-300 { animation-delay: 300ms; } .delay-400 { animation-delay: 400ms; }

    .card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); transition: box-shadow var(--transition), border-color var(--transition), transform var(--transition); }
    .card:hover { box-shadow: var(--shadow-md); border-color: var(--border-2); }
    .btn-primary { background: var(--accent); color: #fff; font-weight: 600; border-radius: var(--radius-md); transition: background var(--transition), transform var(--transition); }
    .btn-primary:hover { background: var(--accent-2); transform: translateY(-1px); }
    .btn-secondary { background: var(--surface); color: var(--text); border: 1px solid var(--border-2); font-weight: 500; border-radius: var(--radius-md); transition: background var(--transition), border-color var(--transition); }
    .btn-secondary:hover { background: var(--surface-2); border-color: var(--text-3); }
    .chip { background: var(--surface); color: var(--text-2); border: 1px solid var(--border); padding: 8px 16px; border-radius: var(--radius-full); font-size: 13px; font-weight: 500; transition: all var(--transition); }
    .chip:hover { background: var(--surface-2); color: var(--text); }
    .chip.active { background: var(--text); color: #fff; border-color: var(--text); }
    .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: var(--surface-2); border-radius: 99px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: var(--border-2); border-radius: 99px; }
    .cyber-gradient-text { background: linear-gradient(135deg, #0071e3 0%, #5e5ce6 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    .perspective-stage { perspective: 1400px; perspective-origin: 50% 50%; }
    .book-3d { transform-style: preserve-3d; transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.4s ease; }
  </style>
</head>
<body class="min-h-screen flex flex-col">

  <!-- Фоновые пятна -->
  <div class="bg-blob" style="top:-10%; left:5%; width:550px; height:550px; background:#d0e8fb;"></div>
  <div class="bg-blob" style="top:40%; right:0%; width:600px; height:600px; background:#e6e0fa;"></div>
  <div class="bg-blob" style="bottom:-10%; left:20%; width:500px; height:500px; background:#e8f8ed;"></div>

  <!-- ================= HEADER ================= -->
  <header class="app-header sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">

      <!-- Логотип -->
      <a href="<?= $practiceBase ?>" class="flex items-center gap-3 shrink-0">
        <div class="w-9 h-9 rounded-xl bg-ink-900 flex items-center justify-center text-white font-bold text-sm shadow-md">P</div>
        <div class="flex flex-col leading-tight">
          <span class="text-[15px] font-bold text-ink-900">Практика</span>
          <span class="text-[10px] uppercase tracking-wider text-ink-400 font-medium">СКАН · EduMind</span>
        </div>
      </a>

      <!-- Навигация -->
      <nav class="hidden md:flex items-center gap-1">
        <a href="<?= $practiceBase ?>" class="nav-pill <?= $activePage=='hub' ? 'active' : '' ?>">
          <i class="fa-solid fa-cubes text-[14px]"></i> <?= $texts[$lang]['practice_hub'] ?>
        </a>
        
        <?php if (!$isTeacher): ?>
          <!-- Арена задач видна ТОЛЬКО ученикам и гостям -->
          <a href="<?= $arenaBase ?>" class="nav-pill <?= $activePage=='arena' ? 'active' : '' ?>">
            <i class="fa-solid fa-gamepad text-[14px]"></i> <?= $texts[$lang]['practice_arena'] ?>
          </a>
        <?php endif; ?>

        <a href="<?= $labsBase ?>" class="nav-pill <?= $activePage=='labs' ? 'active' : '' ?>">
          <i class="fa-solid fa-flask text-[14px]"></i> <?= $texts[$lang]['practice_labs'] ?>
        </a>

        <!-- НОВАЯ ВКЛАДКА: РЕЙТИНГ -->
        <?php if (!$isTeacher): ?>
          <a href="leaderboard.php" class="nav-pill <?= $activePage=='leaderboard' ? 'active' : '' ?>">
            <i class="fa-solid fa-trophy text-[14px]"></i> Рейтинг
          </a>
        <?php endif; ?>
        
        <!-- Кнопка учителя (только для учителя) -->
        <?php if ($isTeacher): ?>
          <a href="teacher.php" class="nav-pill <?= $activePage=='teacher' ? 'active' : '' ?> header-btn-teacher ml-2">
            <i class="fa-solid fa-chalkboard-user"></i> Панель учителя
          </a>
        <?php endif; ?>
        
        <a href="index.php" class="nav-pill">
          <i class="fa-solid fa-house text-[14px]"></i> СКАН
        </a>
      </nav>

      <!-- Правая часть -->
      <div class="flex items-center gap-2 shrink-0">
        
        <!-- Портфель (скрываем у учителя) -->
        <?php if (!$isTeacher): ?>
          <button onclick="openBagModal()" class="header-btn header-btn-ghost">
            <i class="fa-solid fa-briefcase text-brand-500"></i>
            <span class="hidden lg:inline">Портфель</span>
            <span id="bag-count" class="w-5 h-5 rounded-full bg-brand-500 text-white text-[10px] flex items-center justify-center font-bold ml-1">0</span>
          </button>
        <?php endif; ?>

        <!-- Профиль -->
        <?php if ($isTeacher): ?>
          <a href="teacher_profile.php" class="flex items-center gap-2 pl-1 pr-3 py-1 rounded-full bg-indigo-50 border border-indigo-200 hover:border-indigo-300 transition">
            <span class="w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center text-xs font-bold shadow-sm">
              <i class="fa-solid fa-chalkboard-user"></i>
            </span>
            <span class="text-xs font-bold text-indigo-900 hidden sm:inline"><?= htmlspecialchars($_SESSION['teacher_name']) ?></span>
          </a>
        <?php elseif ($isLoggedIn): ?>
          <a href="profile.php" class="flex items-center gap-2 pl-1 pr-3 py-1 rounded-full bg-white border border-surface-border hover:border-surface-border2 transition">
            <span class="w-8 h-8 rounded-full bg-ink-900 text-white flex items-center justify-center text-xs font-bold"><?= mb_substr($userName, 0, 1) ?></span>
            <span class="text-xs font-medium text-ink-900 hidden sm:inline"><?= htmlspecialchars($userName) ?></span>
          </a>
        <?php else: ?>
          <a href="login.php" class="header-btn header-btn-primary">
            <i class="fa-solid fa-right-to-bracket"></i> Войти
          </a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <!-- Инфо-бар -->
  <div class="bg-white border-b border-surface-border">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-10 text-xs">
      <div class="flex items-center gap-2">
        <i class="fa-solid fa-circle-info text-ink-400"></i>
        <span class="text-ink-600">10 предметов · 100 учебников · <?= $totalLabs ?> лабораторий</span>
      </div>
      <div class="flex items-center gap-4">
        <span class="text-ink-600"><i class="fa-solid fa-star text-warn mr-1"></i><?= $isLoggedIn ? 'Вы вошли как '.htmlspecialchars($userName) : 'Гостевой режим' ?></span>
        <span class="text-ink-400"><i class="fa-regular fa-clock mr-1"></i><span id="headerClock">--:--:--</span></span>
      </div>
    </div>
  </div>