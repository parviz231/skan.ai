</div> <!-- закрываем content-wrapper -->

<!-- ===== TOAST ===== -->
<div class="toast-container">
    <div class="toast" id="launchToast">
        <div class="toast-icon">
            <i class="fas fa-check"></i>
        </div>
        <div class="toast-content">
            <div class="title">Сервер запущен!</div>
            <div class="desc">Все модули активны и готовы к работе</div>
        </div>
        <button class="toast-close" onclick="closeLaunchToast()">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>

<script>
// ===== Toggle Sidebar =====
const menuToggle = document.getElementById('menuToggle');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');

function toggleSidebar() {
    sidebar.classList.toggle('open');
    overlay.classList.toggle('active');
    document.body.style.overflow = sidebar.classList.contains('open') ? 'hidden' : '';
}

menuToggle.addEventListener('click', toggleSidebar);
overlay.addEventListener('click', toggleSidebar);

// ===== Close sidebar on link click (mobile) =====
document.querySelectorAll('.sidebar .nav-item').forEach(link => {
    link.addEventListener('click', () => {
        if (window.innerWidth <= 992) {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
});

// ===== Fullscreen =====
function toggleFullscreen() {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().catch(() => {});
    } else {
        if (document.exitFullscreen) document.exitFullscreen();
    }
}

// ============================================================
// ===== ФУНКЦИЯ RunServer() - КРАСИВАЯ И СТИЛЬНАЯ =====
// ============================================================
let isRunning = false;

async function RunServer() {
    if (isRunning) return;
    isRunning = true;
    
    const btn = document.getElementById('launchBtn');
    const icon = btn.querySelector('i');
    const text = btn.querySelector('span');
    
    // Меняем состояние кнопки
    icon.className = 'fas fa-spinner spinner';
    text.textContent = 'Запуск...';
    btn.style.cursor = 'default';
    btn.style.opacity = '0.8';
    
    try {
        // Запрос к серверу
        const response = await fetch('http://project.local/public/admin/Face_/startcamera.php');
        const data = await response.json();
        
        // Успешный запуск
        icon.className = 'fas fa-check';
        text.textContent = 'Запущено!';
        btn.classList.add('success');
        btn.style.opacity = '1';
        btn.style.cursor = 'default';
        
        // Показываем уведомление
        const toast = document.getElementById('launchToast');
        toast.querySelector('.title').textContent = '✅ ' + (data.message || 'Сервер запущен!');
        toast.classList.add('show');
        
        setTimeout(() => {
            toast.classList.remove('show');
        }, 5000);
        
        // Возвращаем в исходное состояние через 2.5 сек
        setTimeout(() => {
            icon.className = 'fas fa-play';
            text.textContent = 'Запустить';
            btn.classList.remove('success');
            btn.style.cursor = 'pointer';
            btn.style.opacity = '1';
            isRunning = false;
        }, 2500);
        
    } catch (error) {
        // Ошибка
        icon.className = 'fas fa-times';
        text.textContent = 'Ошибка';
        btn.style.background = '#e74c3c';
        btn.style.opacity = '1';
        
        const toast = document.getElementById('launchToast');
        toast.querySelector('.title').textContent = '❌ Ошибка запуска';
        toast.querySelector('.desc').textContent = 'Проверьте подключение к серверу';
        toast.classList.add('show');
        
        setTimeout(() => {
            toast.classList.remove('show');
        }, 5000);
        
        setTimeout(() => {
            icon.className = 'fas fa-play';
            text.textContent = 'Запустить';
            btn.style.background = '';
            btn.style.cursor = 'pointer';
            btn.style.opacity = '1';
            isRunning = false;
        }, 3000);
    }
}

// ===== Close Toast =====
function closeLaunchToast() {
    document.getElementById('launchToast').classList.remove('show');
}

// ===== Close on resize =====
window.addEventListener('resize', () => {
    if (window.innerWidth > 992) {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }
});

// ===== ESC to close sidebar =====
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        if (sidebar.classList.contains('open')) toggleSidebar();
        closeLaunchToast();
    }
});

// ===== Keyboard shortcut: Ctrl+Shift+L =====
document.addEventListener('keydown', (e) => {
    if (e.ctrlKey && e.shiftKey && (e.key === 'l' || e.key === 'L')) {
        e.preventDefault();
        RunServer();
    }
});
</script>

<script src="js/bootstrap.bundle.js"></script>
</body>
</html>