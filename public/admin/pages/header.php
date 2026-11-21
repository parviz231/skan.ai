<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Электронный журнал</title>
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f0f4f8;
            color: #1a2332;
        }
        a { text-decoration: none; }

        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: #eef2f7; }
        ::-webkit-scrollbar-thumb { background: #c5cdd8; border-radius: 8px; }
        ::-webkit-scrollbar-thumb:hover { background: #a8b3c4; }

        /* ===== TOPBAR ===== */
        .topbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 64px;
            background: #ffffff;
            border-bottom: 1px solid #e8ecf1;
            display: flex;
            align-items: center;
            padding: 0 28px;
            z-index: 1000;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        }
        .topbar .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
            font-weight: 700;
            color: #1a2332;
        }
        .topbar .logo i {
            font-size: 24px;
            color: #4361ee;
        }
        .topbar .logo .gradient-text { color: #4361ee; }
        .topbar .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 20px;
            color: #4a5a6e;
            padding: 4px 10px;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.2s;
            margin-right: 10px;
        }
        .topbar .menu-toggle:hover { background: #f0f2f5; }

        .topbar .right {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .topbar .right .btn-icon {
            background: none;
            border: none;
            color: #7f8c8d;
            font-size: 16px;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .topbar .right .btn-icon:hover { background: #f0f2f5; color: #4361ee; }

        /* ===== КНОПКА ЗАПУСТИТЬ (КРАСИВАЯ) ===== */
        .btn-launch {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
            border: none;
            padding: 8px 22px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(102,126,234,0.35);
            position: relative;
            overflow: hidden;
        }
        .btn-launch:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(102,126,234,0.5);
        }
        .btn-launch:active { transform: scale(0.95); }
        .btn-launch i { font-size: 15px; }
        .btn-launch .spinner {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .btn-launch .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            transform: scale(0);
            animation: ripple-anim 0.6s linear;
            pointer-events: none;
        }
        @keyframes ripple-anim {
            to { transform: scale(4); opacity: 0; }
        }
        .btn-launch.success {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            box-shadow: 0 4px 15px rgba(46,204,113,0.4);
        }

        .topbar .right .user {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 3px 12px 3px 3px;
            border-radius: 30px;
            cursor: default;
            transition: 0.2s;
        }
        .topbar .right .user:hover { background: #f0f2f5; }
        .topbar .right .user .avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 13px;
        }
        .topbar .right .user .name { font-size: 13px; font-weight: 500; color: #1a2332; }

        /* ===== SIDEBAR ===== */
        .sidebar {
            position: fixed;
            top: 64px;
            left: 0;
            bottom: 0;
            width: 240px;
            background: #black;
            border-right: 1px solid #e8ecf1;
            overflow-y: auto;
            z-index: 999;
            transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 16px 0 20px;
        }
        .sidebar::-webkit-scrollbar { width: 3px; }
        .sidebar::-webkit-scrollbar-thumb { background: #dce3ed; border-radius: 4px; }

        .sidebar .section { padding: 0 12px; }
        .sidebar .section-title {
            font-size: 10px;
            font-weight: 600;
            color: #a8b3c4;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            padding: 12px 12px 6px;
        }
        .sidebar .nav-item {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            border-radius: 8px;
            color: #4a5a6e;
            font-size: 13px;
            font-weight: 500;
            transition: 0.2s;
            gap: 10px;
            margin-bottom: 1px;
        }
        .sidebar .nav-item:hover {
            background: #f0f4f8;
            color: #1a2332;
        }
        .sidebar .nav-item.active {
            background: #eef3ff;
            color: #4361ee;
        }
        .sidebar .nav-item i {
            width: 18px;
            font-size: 14px;
            text-align: center;
            color: #8a9bb0;
            transition: 0.2s;
        }
        .sidebar .nav-item:hover i { color: #4361ee; }
        .sidebar .nav-item.active i { color: #4361ee; }
        .sidebar .nav-item .badge {
            margin-left: auto;
            background: #4361ee;
            color: #fff;
            font-size: 10px;
            padding: 1px 10px;
            border-radius: 20px;
            font-weight: 600;
        }
        .sidebar .divider {
            height: 1px;
            background: #eef2f7;
            margin: 10px 14px;
        }
        .sidebar .logout { color: #e74c3c; }
        .sidebar .logout:hover {
            background: #fde8e8;
            color: #c0392b;
        }
        .sidebar .logout i { color: #e74c3c; }

        /* ===== CONTENT ===== */
        .content-wrapper {
            margin-left: 240px;
            padding-top: 64px;
            min-height: 100vh;
            background: #f0f4f8;
        }
        .content {
            padding: 20px 24px 30px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 64px;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.3);
            z-index: 998;
            opacity: 0;
            transition: 0.3s;
        }
        .sidebar-overlay.active {
            display: block;
            opacity: 1;
        }

        /* ===== TOAST ===== */
        .toast-container {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 9999;
        }
        .toast {
            background: #1a2332;
            color: #fff;
            padding: 14px 20px;
            border-radius: 12px;
            box-shadow: 0 12px 40px rgba(0,0,0,0.25);
            transform: translateY(80px);
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            max-width: 380px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }
        .toast .toast-icon {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #27ae60;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }
        .toast .toast-content { flex: 1; }
        .toast .toast-content .title { font-weight: 600; font-size: 14px; }
        .toast .toast-content .desc { font-size: 12px; color: #8a9bb0; margin-top: 1px; }
        .toast .toast-close {
            background: none;
            border: none;
            color: #5a6a7e;
            font-size: 16px;
            cursor: pointer;
            padding: 2px;
        }
        .toast .toast-close:hover { color: #fff; }

        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); box-shadow: 2px 0 20px rgba(0,0,0,0.06); }
            .topbar .menu-toggle { display: block; }
            .content-wrapper { margin-left: 0; }
            .content { padding: 16px 16px 24px; }
        }
        @media (max-width: 768px) {
            .topbar { padding: 0 16px; }
            .topbar .logo span { display: none; }
            .topbar .right .user .name { display: none; }
            .btn-launch span { display: none; }
            .btn-launch { padding: 7px 12px; }
            .sidebar { width: 260px; }
            .toast { margin: 0 12px; max-width: calc(100% - 24px); }
        }
        @media (max-width: 480px) {
            .topbar .right .user { padding: 3px; }
            .topbar .right .btn-icon { width: 32px; height: 32px; font-size: 14px; }
            .toast { padding: 12px 16px; }
            .toast .toast-icon { width: 32px; height: 32px; font-size: 14px; }
        }
    </style>
</head>
<body>

<!-- ===== TOPBAR ===== -->
<header class="topbar">
    <button class="menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>
    <div class="logo">
        <i class="fas fa-graduation-cap"></i>
        <span>Эл. <span class="gradient-text">журнал</span></span>
    </div>
    <div class="right">
        <button class="btn-icon" onclick="toggleFullscreen()" title="Полный экран">
            <i class="fas fa-expand"></i>
        </button>
           
        <!-- ===== КНОПКА ЗАПУСТИТЬ (КРАСИВАЯ) ===== -->
        <a onclick="RunServer()" class="btn-launch" id="launchBtn">
            <i class="fas fa-play"></i>
            <span>Запустить</span>
        </a>
        
        <div class="user">
            <div class="avatar">А</div>
            <span class="name">Администратор</span>
        </div>
    </div>
</header>

<!-- ===== OVERLAY ===== -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ===== SIDEBAR ===== -->
<aside class="sidebar" id="sidebar">
    <div class="section">
        <div class="section-title">Управление</div>
        <a href="?page=1" class="nav-item <?= ($_GET['page'] ?? '') == '1' ? 'active' : '' ?>">
            <i class="fas fa-user-plus"></i> Добавить ученика
        </a>
        <a href="?page=3" class="nav-item <?= ($_GET['page'] ?? '') == '3' ? 'active' : '' ?>">
            <i class="fas fa-users"></i> Список учеников
        </a>
        <a href="?page=8" class="nav-item <?= ($_GET['page'] ?? '') == '8' ? 'active' : '' ?>">
            <i class="fas fa-book"></i> Журнал оценок
        </a>
        <a href="?page=7" class="nav-item <?= ($_GET['page'] ?? '') == '7' ? 'active' : '' ?>">
            <i class="fas fa-clipboard-check"></i> Посещаемость
        </a>
        <a href="?page=9" class="nav-item <?= ($_GET['page'] ?? '') == '9' ? 'active' : '' ?>">
            <i class="fas fa-chalkboard-teacher"></i> Учителя
        </a>
    </div>

    <div class="divider"></div>

    <div class="section">
        <div class="section-title">Аналитика</div>
        <a href="?page=18" class="nav-item <?= ($_GET['page'] ?? '') == '18' ? 'active' : '' ?>">
            <i class="fas fa-chart-line"></i> Рейтинг учеников
            <span class="badge">NEW</span>
        </a>
    </div>

    <div class="divider"></div>

    <div class="section">
        <a href="logout.php" class="nav-item logout">
            <i class="fas fa-sign-out-alt"></i> Выход
        </a>
    </div>
</aside>
<script>
/* ============================================================
   RUN SERVER
============================================================ */
window.RunServer = async function(ev) {
    const btn = document.getElementById('launchBtn');
    if (!btn || btn.classList.contains('loading')) return;

    // Ripple-эффект
    if (ev && ev.clientX !== undefined) {
        const r = document.createElement('span');
        r.className = 'ripple';
        const rect = btn.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        r.style.width = r.style.height = size + 'px';
        r.style.left = (ev.clientX - rect.left - size / 2) + 'px';
        r.style.top  = (ev.clientY - rect.top  - size / 2) + 'px';
        btn.appendChild(r);
        setTimeout(() => r.remove(), 600);
    }

    // Loading
    btn.classList.add('loading');
    const icon = btn.querySelector('i');
    const text = btn.querySelector('span');
    const oldIcon = icon ? icon.className : '';
    const oldText = text ? text.textContent : '';
    if (icon) icon.className = 'fas fa-circle-notch spinner';
    if (text) text.textContent = 'Запуск…';

    try {
        const res = await fetch('startcamera.php', {
            method: 'POST',
            cache: 'no-store',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        const raw = await res.text();
        console.log('[RunServer] HTTP', res.status, 'Body:', raw);

        let data;
        try {
            data = JSON.parse(raw);
        } catch (err) {
            throw new Error('Сервер вернул не JSON: ' + raw.slice(0, 100));
        }

        if (data.ok) {
            if (icon) icon.className = 'fas fa-check';
            if (text) text.textContent = 'Готово';
            btn.classList.add('success');
            showToast('Система запущена', 'Камера и сервер работают');
        } else {
            throw new Error(data.error || 'Не удалось запустить');
        }
    } catch (e) {
        console.error('[RunServer] Ошибка:', e);
        if (icon) icon.className = 'fas fa-triangle-exclamation';
        if (text) text.textContent = 'Ошибка';
        showToast('Ошибка запуска', e.message, 'error');
    } finally {
        btn.classList.remove('loading');
        setTimeout(() => {
            if (icon) icon.className = oldIcon || 'fas fa-play';
            if (text) text.textContent = oldText || 'Запустить';
            btn.classList.remove('success');
        }, 2500);
    }
};

/* TOAST */
window.showToast = function(title, desc, type = 'ok') {
    let cont = document.querySelector('.toast-container');
    if (!cont) {
        cont = document.createElement('div');
        cont.className = 'toast-container';
        document.body.appendChild(cont);
    }
    const t = document.createElement('div');
    t.className = 'toast';
    const color = type === 'error' ? '#e74c3c' : '#27ae60';
    const ico   = type === 'error' ? 'fa-triangle-exclamation' : 'fa-check';
    t.innerHTML = `
        <div class="toast-icon" style="background:${color}"><i class="fas ${ico}"></i></div>
        <div class="toast-content">
            <div class="title">${title}</div>
            <div class="desc">${desc || ''}</div>
        </div>
        <button class="toast-close" aria-label="Закрыть">&times;</button>
    `;
    cont.appendChild(t);
    requestAnimationFrame(() => t.classList.add('show'));
    const close = () => { t.classList.remove('show'); setTimeout(() => t.remove(), 400); };
    t.querySelector('.toast-close').onclick = close;
    setTimeout(close, 4000);
};

if (typeof window.toggleFullscreen !== 'function') {
    window.toggleFullscreen = function() {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen().catch(() => {});
        } else {
            document.exitFullscreen().catch(() => {});
        }
    };
}
</script>