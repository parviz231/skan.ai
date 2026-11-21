<aside class="main-sidebar">
    <!-- Brand -->
    <div class="brand">
        <div class="brand-icon">
            <i class="fas fa-graduation-cap"></i>
        </div>
        <div class="brand-text">
            <span class="brand-title">СКАН</span>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- User -->
        <div class="user-panel">
            <div class="user-avatar">
                <?php 
                    $initial = 'Р';
                    echo $initial;
                ?>
            </div>
            <div class="user-info">
                <div class="user-name">Раупов Парвиз</div>
            </div>
        </div>

        <!-- Search -->
        <div class="search-wrap">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Поиск...">
        </div>

        <!-- Menu -->
        <nav>
            <ul class="menu">
                <!-- Ученики -->
                <li class="menu-item <?= (in_array($_GET['page'] ?? '', [1, 3, 18])) ? 'open' : '' ?>">
                    <a href="#" class="menu-link">
                        <i class="fas fa-users"></i>
                        <span>Ученики</span>
                        <i class="fas fa-chevron-down arrow"></i>
                    </a>
                    <ul class="submenu">
                        <li><a href="?page=1" class="<?= ($_GET['page'] ?? '') == '1' ? 'active' : '' ?>"><i class="fas fa-plus"></i> Добавить</a></li>
                        <li><a href="?page=3" class="<?= ($_GET['page'] ?? '') == '3' ? 'active' : '' ?>"><i class="fas fa-list"></i> Список</a></li>
                        <li><a href="?page=18" class="<?= ($_GET['page'] ?? '') == '18' ? 'active' : '' ?>"><i class="fas fa-chart-simple"></i> Рейтинг</a></li>
                    </ul>
                </li>

                <!-- Достижения -->
                <li class="menu-item <?= (in_array($_GET['page'] ?? '', [19, 20])) ? 'open' : '' ?>">
                    <a href="#" class="menu-link">
                        <i class="fas fa-trophy"></i>
                        <span>Достижения</span>
                        <i class="fas fa-chevron-down arrow"></i>
                    </a>
                    <ul class="submenu">
                        <li><a href="?page=19" class="<?= ($_GET['page'] ?? '') == '19' ? 'active' : '' ?>"><i class="fas fa-plus"></i> Добавить</a></li>
                        <li><a href="?page=20" class="<?= ($_GET['page'] ?? '') == '20' ? 'active' : '' ?>"><i class="fas fa-list"></i> Список</a></li>
                    </ul>
                </li>

                <!-- Журналы -->
                <li class="menu-item <?= (in_array($_GET['page'] ?? '', [7, 8])) ? 'open' : '' ?>">
                    <a href="#" class="menu-link">
                        <i class="fas fa-book"></i>
                        <span>Журналы</span>
                        <i class="fas fa-chevron-down arrow"></i>
                    </a>
                    <ul class="submenu">
                        <li><a href="?page=7" class="<?= ($_GET['page'] ?? '') == '7' ? 'active' : '' ?>"><i class="fas fa-clipboard-check"></i> Посещаемость</a></li>
                        <li><a href="?page=8" class="<?= ($_GET['page'] ?? '') == '8' ? 'active' : '' ?>"><i class="fas fa-pen"></i> Оценки</a></li>
                    </ul>
                </li>

                <!-- Учителя -->
                <li class="menu-item <?= (in_array($_GET['page'] ?? '', [9, 11])) ? 'open' : '' ?>">
                    <a href="#" class="menu-link">
                        <i class="fas fa-chalkboard-user"></i>
                        <span>Учителя</span>
                        <i class="fas fa-chevron-down arrow"></i>
                    </a>
                    <ul class="submenu">
                        <li><a href="?page=9" class="<?= ($_GET['page'] ?? '') == '9' ? 'active' : '' ?>"><i class="fas fa-plus"></i> Добавить</a></li>
                        <li><a href="?page=11" class="<?= ($_GET['page'] ?? '') == '11' ? 'active' : '' ?>"><i class="fas fa-list"></i> Список</a></li>
                    </ul>
                </li>

                <!-- Расписание -->
                <li class="menu-item <?= (in_array($_GET['page'] ?? '', [15, 16, 17])) ? 'open' : '' ?>">
                    <a href="#" class="menu-link">
                        <i class="fas fa-calendar"></i>
                        <span>Расписание</span>
                        <i class="fas fa-chevron-down arrow"></i>
                    </a>
                    <ul class="submenu">
                        <li><a href="?page=15" class="<?= ($_GET['page'] ?? '') == '15' ? 'active' : '' ?>"><i class="fas fa-plus"></i> Добавить</a></li>
                        <li><a href="?page=16" class="<?= ($_GET['page'] ?? '') == '16' ? 'active' : '' ?>"><i class="fas fa-pen"></i> Изменить</a></li>
                        <li><a href="?page=17" class="<?= ($_GET['page'] ?? '') == '17' ? 'active' : '' ?>"><i class="fas fa-eye"></i> Вывод</a></li>
                    </ul>
                </li>
            </ul>
        </nav>

        <!-- Footer -->
        <div class="sidebar-footer">
            <a href="logout.php" class="logout">
                <i class="fas fa-right-from-bracket"></i>
                <span>Выход</span>
            </a>
        </div>
    </div>
</aside>
    <style>
    /* ===== SIDEBAR ===== */
    .main-sidebar {
        position: fixed;
        top: 0;
        left: 0;
        bottom: 0;
        width: 240px;
        background: #1a2332;
        z-index: 1000;
        display: flex;
        flex-direction: column;
        transition: transform 0.3s ease;
        box-shadow: 2px 0 12px rgba(0,0,0,0.08);
        overflow: hidden;
    }

    /* ===== BRAND ===== */
    .brand {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 20px;
        border-bottom: 1px solid rgba(255,255,255,0.05);
        flex-shrink: 0;
    }
    .brand-icon {
        width: 36px;
        height: 36px;
        background: rgba(74,144,217,0.15);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #4a90d9;
        font-size: 18px;
    }
    .brand-title {
        font-size: 18px;
        font-weight: 700;
        color: #fff;
        letter-spacing: -0.3px;
    }

    /* ===== SIDEBAR SCROLL ===== */
    .sidebar {
        flex: 1;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 8px 0 0;
        display: flex;
        flex-direction: column;
    }
    .sidebar::-webkit-scrollbar { width: 3px; }
    .sidebar::-webkit-scrollbar-track { background: transparent; }
    .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 4px; }

    /* ===== USER ===== */
    .user-panel {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 20px 12px;
        border-bottom: 1px solid rgba(255,255,255,0.05);
        flex-shrink: 0;
    }
    .user-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #4a90d9;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        font-weight: 600;
        color: #fff;
        flex-shrink: 0;
    }
    .user-name {
        font-size: 13px;
        font-weight: 500;
        color: #fff;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* ===== SEARCH ===== */
    .search-wrap {
        position: relative;
        padding: 10px 16px 14px;
        flex-shrink: 0;
    }
    .search-wrap i {
        position: absolute;
        left: 24px;
        top: 50%;
        transform: translateY(-50%);
        color: #5a6a7e;
        font-size: 13px;
    }
    .search-wrap input {
        width: 100%;
        padding: 6px 10px 6px 32px;
        border: 1px solid rgba(255,255,255,0.06);
        border-radius: 6px;
        background: rgba(255,255,255,0.04);
        color: #c8d0dc;
        font-size: 13px;
        outline: none;
        transition: 0.2s;
    }
    .search-wrap input::placeholder { color: #5a6a7e; }
    .search-wrap input:focus {
        background: rgba(255,255,255,0.07);
        border-color: rgba(74,144,217,0.3);
    }

    /* ===== MENU ===== */
    nav {
        padding: 0 10px;
        flex: 1;
    }
    .menu {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .menu-item { margin-bottom: 1px; }
    .menu-link {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 12px;
        border-radius: 8px;
        color: #8a9aaa;
        font-size: 13px;
        font-weight: 500;
        transition: 0.2s;
        cursor: pointer;
        text-decoration: none;
    }
    .menu-link:hover {
        background: rgba(255,255,255,0.05);
        color: #fff;
    }
    .menu-link i:first-child {
        width: 18px;
        font-size: 14px;
        text-align: center;
        color: #5a6a7e;
        flex-shrink: 0;
    }
    .menu-link span { flex: 1; }
    .menu-link .arrow {
        font-size: 10px;
        color: #5a6a7e;
        transition: transform 0.3s ease;
    }
    .menu-item.open > .menu-link { color: #fff; }
    .menu-item.open > .menu-link i:first-child { color: #4a90d9; }
    .menu-item.open > .menu-link .arrow { transform: rotate(180deg); }

    /* ===== SUBMENU ===== */
    .submenu {
        list-style: none;
        padding: 0;
        margin: 0 0 4px 0;
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease;
    }
    .menu-item.open .submenu { max-height: 300px; }
    .submenu li { padding: 0; }
    .submenu a {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 5px 12px 5px 44px;
        border-radius: 6px;
        color: #7a8a9a;
        font-size: 13px;
        text-decoration: none;
        transition: 0.2s;
    }
    .submenu a:hover {
        background: rgba(255,255,255,0.04);
        color: #fff;
    }
    .submenu a i {
        font-size: 12px;
        width: 16px;
        text-align: center;
        color: #5a6a7e;
    }
    .submenu a:hover i { color: #4a90d9; }
    .submenu a.active {
        color: #fff;
        background: rgba(74,144,217,0.12);
    }
    .submenu a.active i { color: #4a90d9; }

    /* ===== FOOTER ===== */
    .sidebar-footer {
        padding: 10px 16px 14px;
        border-top: 1px solid rgba(255,255,255,0.05);
        flex-shrink: 0;
        margin-top: auto;
    }
    .logout {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #e74c3c;
        font-size: 13px;
        font-weight: 500;
        padding: 6px 12px;
        border-radius: 8px;
        text-decoration: none;
        transition: 0.2s;
    }
    .logout:hover {
        background: rgba(231,76,60,0.12);
        color: #ff6b6b;
    }
    .logout i { font-size: 14px; }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 992px) {
        .main-sidebar { transform: translateX(-100%); }
        .main-sidebar.open { transform: translateX(0); }
    }
    @media (max-width: 576px) {
        .main-sidebar { width: 100%; max-width: 300px; }
        .brand { padding: 14px 16px; }
        .brand-title { font-size: 16px; }
        .user-panel { padding: 6px 16px 10px; }
        .search-wrap { padding: 8px 14px 10px; }
        .menu-link { padding: 7px 10px; font-size: 13px; }
        .submenu a { padding: 4px 10px 4px 38px; font-size: 12px; }
        .sidebar-footer { padding: 8px 14px 12px; }
    }
    </style>

    <script>
    // ===== Toggle submenu =====
    document.querySelectorAll('.menu-link').forEach(link => {
        link.addEventListener('click', function(e) {
            const parent = this.closest('.menu-item');
            const hasSub = parent.querySelector('.submenu');
            if (hasSub) {
                e.preventDefault();
                document.querySelectorAll('.menu-item.open').forEach(item => {
                    if (item !== parent) item.classList.remove('open');
                });
                parent.classList.toggle('open');
            }
        });
    });

    // ===== Search =====
    document.querySelector('.search-wrap input')?.addEventListener('input', function() {
        const q = this.value.toLowerCase().trim();
        document.querySelectorAll('.menu-item').forEach(item => {
            const text = item.textContent.toLowerCase();
            item.style.display = (q === '' || text.includes(q)) ? '' : 'none';
        });
    });

    // ===== Open active =====
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.submenu a.active').forEach(link => {
            const parent = link.closest('.menu-item');
            if (parent) parent.classList.add('open');
        });
    });
    </script>