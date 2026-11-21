<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once("config2.php");

// Если пользователь уже вошел, перенаправляем его
if (isset($_SESSION['id1'])) {
    header("Location: profile.php");
    exit;
}
if (isset($_SESSION['is_teacher'])) {
    header("Location: teacher.php");
    exit;
}

$error = "";
$activeRole = 'student';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($conn->connect_error) {
        die("Ошибка подключения: " . $conn->connect_error);
    }

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? 'student';
    $activeRole = $role;

    if (empty($username) || empty($password)) {
        $error = "Заполните все поля";
    } else {
        if ($role === 'student') {
            // ВХОД УЧЕНИКА
            $stmt = $conn->prepare("SELECT user_id, nickname, password FROM students WHERE nickname = ? LIMIT 1");
            if (!$stmt) die("Ошибка SQL: " . $conn->error);
            
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                if ($password === $row['password']) {
                    $_SESSION['username1'] = $row['nickname'];
                    $_SESSION['id1'] = (int)$row['user_id'];
                    $stmt->close();
                    header("Location: profile.php");
                    exit;
                } else {
                    $error = "Неправильный пароль";
                }
            } else {
                $error = "Такого ученика не существует!";
            }
            $stmt->close();

        } elseif ($role === 'teacher') {
            // ВХОД УЧИТЕЛЯ
            $stmt = $conn->prepare("SELECT id, login, password, name FROM teachers WHERE login = ? LIMIT 1");
            if (!$stmt) die("Ошибка SQL: " . $conn->error);
            
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                if ($password === $row['password']) {
                    $_SESSION['is_teacher'] = true;
                    $_SESSION['teacher_id'] = $row['id'];
                    $_SESSION['teacher_name'] = $row['name'];
                    $_SESSION['teacher_login'] = $row['login'];
                    $stmt->close();
                    header("Location: teacher.php");
                    exit;
                } else {
                    $error = "Неправильный пароль";
                }
            } else {
                $error = "Учитель с таким логином не найден!";
            }
            $stmt->close();
        }
    }
}

// ПОДКЛЮЧАЕМ ШАПКУ САЙТА
require_once("header.php"); 
?>

<!-- Стили для страницы логина -->
<style>
    :root {
        --login-primary: #0071e3;
        --login-primary-hover: #0077ed;
        --login-teacher: #8b5cf6;
        --login-teacher-hover: #7c3aed;
        --login-text: #1d1d1f;
        --login-text-sec: #86868b;
        --login-bg: #f5f5f7;
        --login-border: rgba(0, 0, 0, 0.08);
        --login-radius: 20px;
    }

    .login-wrapper {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        background: var(--login-bg);
        min-height: calc(100vh - 180px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
        position: relative;
        overflow: hidden;
    }

    /* Красивые градиентные шары на фоне */
    .bg-shape {
        position: absolute;
        border-radius: 50%;
        filter: blur(80px);
        z-index: 0;
        opacity: 0.6;
        animation: floatShape 15s infinite alternate ease-in-out;
    }
    .shape-1 { width: 400px; height: 400px; background: rgba(0, 113, 227, 0.15); top: -100px; left: -100px; }
    .shape-2 { width: 350px; height: 350px; background: rgba(139, 92, 246, 0.15); bottom: -50px; right: -50px; animation-delay: -5s; }
    .shape-3 { width: 250px; height: 250px; background: rgba(236, 72, 153, 0.1); top: 40%; left: 60%; animation-delay: -10s; }

    @keyframes floatShape {
        0% { transform: translate(0, 0) scale(1); }
        100% { transform: translate(40px, 60px) scale(1.1); }
    }

    /* Стеклянная карточка */
    .login-card {
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.8);
        border-radius: 28px;
        box-shadow: 0 24px 60px rgba(0, 0, 0, 0.08), inset 0 1px 0 rgba(255, 255, 255, 0.5);
        width: 100%;
        max-width: 440px;
        padding: 48px;
        position: relative;
        z-index: 1;
        transition: transform 0.3s ease;
    }

    .login-card:hover {
        transform: translateY(-2px);
    }

    .login-icon-wrapper {
        width: 72px;
        height: 72px;
        border-radius: 20px;
        background: linear-gradient(135deg, #e0f2fe, #f0f9ff);
        color: var(--login-primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 30px;
        margin: 0 auto 24px;
        box-shadow: 0 8px 16px rgba(0, 113, 227, 0.1);
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .login-icon-wrapper.teacher {
        background: linear-gradient(135deg, #ede9fe, #f5f3ff);
        color: var(--login-teacher);
        box-shadow: 0 8px 16px rgba(139, 92, 246, 0.1);
    }

    .login-card:hover .login-icon-wrapper {
        transform: scale(1.05) rotate(-5deg);
    }

    .login-title {
        font-size: 26px;
        font-weight: 800;
        text-align: center;
        color: var(--login-text);
        margin-bottom: 8px;
        letter-spacing: -0.03em;
    }

    .login-subtitle {
        font-size: 14px;
        color: var(--login-text-sec);
        text-align: center;
        margin-bottom: 32px;
    }

    /* Переключатель ролей (Segmented Control) */
    .role-switch {
        display: flex;
        background: rgba(0, 0, 0, 0.04);
        border-radius: 16px;
        padding: 4px;
        margin-bottom: 28px;
        position: relative;
    }

    /* Плавный ползунок под кнопками */
    .role-switch::after {
        content: '';
        position: absolute;
        top: 4px;
        left: 4px;
        width: calc(50% - 4px);
        height: calc(100% - 8px);
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 0;
    }

    .role-switch.teacher-active::after {
        transform: translateX(100%);
    }

    .role-btn {
        flex: 1;
        text-align: center;
        padding: 10px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 600;
        color: var(--login-text-sec);
        cursor: pointer;
        transition: color 0.3s ease;
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .role-btn.active {
        color: var(--login-text);
    }

    .role-btn input {
        display: none;
    }

    /* Поля ввода */
    .input-group {
        position: relative;
        margin-bottom: 20px;
        transition: transform 0.2s ease;
    }

    .input-group:focus-within {
        transform: translateY(-1px);
    }

    .input-icon {
        position: absolute;
        left: 18px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--login-text-sec);
        font-size: 15px;
        transition: color 0.3s ease;
        z-index: 2;
    }

    .input-group:focus-within .input-icon {
        color: var(--login-primary);
    }

    .form-input {
        width: 100%;
        padding: 16px 16px 16px 48px;
        border-radius: 14px;
        border: 1.5px solid var(--login-border);
        background: rgba(255, 255, 255, 0.8);
        font-size: 14px;
        color: var(--login-text);
        font-family: 'Inter', sans-serif;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
    }

    .form-input:focus {
        outline: none;
        border-color: var(--login-primary);
        background: #ffffff;
        box-shadow: 0 4px 12px rgba(0, 113, 227, 0.15), 0 0 0 4px rgba(0, 113, 227, 0.1);
    }

    .form-input::placeholder {
        color: #a1a1a6;
        font-weight: 400;
    }

    /* Кнопка */
    .btn-submit {
        width: 100%;
        padding: 16px;
        border-radius: 14px;
        border: none;
        background: var(--login-primary);
        color: #ffffff;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        margin-top: 8px;
        margin-bottom: 24px;
        box-shadow: 0 6px 20px rgba(0, 113, 227, 0.25);
        position: relative;
        overflow: hidden;
    }

    .btn-submit:hover {
        background: var(--login-primary-hover);
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(0, 113, 227, 0.35);
    }

    .btn-submit:active {
        transform: translateY(0);
    }

    .btn-submit.teacher-mode {
        background: var(--login-teacher);
        box-shadow: 0 6px 20px rgba(139, 92, 246, 0.25);
    }
    
    .btn-submit.teacher-mode:hover {
        background: var(--login-teacher-hover);
        box-shadow: 0 10px 25px rgba(139, 92, 246, 0.35);
    }

    /* Ссылки */
    .login-links {
        text-align: center;
        font-size: 13px;
    }

    .login-links a {
        color: var(--login-primary);
        text-decoration: none;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 12px;
        transition: all 0.2s ease;
    }

    .login-links a:hover {
        color: var(--login-primary-hover);
        text-decoration: underline;
    }

    .login-links a.secondary-link {
        color: var(--login-text-sec);
        font-weight: 500;
        font-size: 12px;
        display: block;
    }

    .login-links a.secondary-link:hover {
        color: var(--login-text);
    }

    /* Ошибка */
    .error-msg {
        background: rgba(255, 59, 48, 0.08);
        color: #ff3b30;
        border: 1px solid rgba(255, 59, 48, 0.2);
        padding: 14px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 600;
        text-align: center;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        animation: shake 0.4s ease-in-out;
    }

    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-4px); }
        75% { transform: translateX(4px); }
    }

    .login-footer {
        margin-top: 28px;
        padding-top: 20px;
        border-top: 1px solid rgba(0, 0, 0, 0.06);
        text-align: center;
        font-size: 11px;
        color: #a1a1a6;
        font-family: 'JetBrains Mono', monospace;
        letter-spacing: 0.02em;
    }

    /* Адаптивность */
    @media (max-width: 480px) {
        .login-card { padding: 32px 24px; }
        .login-title { font-size: 22px; }
        .role-btn { font-size: 12px; padding: 8px; }
    }
</style>

<div class="login-wrapper">
    <!-- Фоновые шары -->
    <div class="bg-shape shape-1"></div>
    <div class="bg-shape shape-2"></div>
    <div class="bg-shape shape-3"></div>

    <div class="login-card">
        
        <!-- Иконка -->
        <div id="mainIcon" class="login-icon-wrapper <?= $activeRole === 'teacher' ? 'teacher' : '' ?>">
            <i class="fas fa-<?= $activeRole === 'teacher' ? 'chalkboard-user' : 'graduation-cap' ?>"></i>
        </div>

        <!-- Заголовки -->
        <h1 id="mainTitle" class="login-title"><?= $activeRole === 'teacher' ? 'Режим учителя' : 'Добро пожаловать' ?></h1>
        <p id="mainSubtitle" class="login-subtitle"><?= $activeRole === 'teacher' ? 'Доступ к управлению и статистике' : 'Войдите в свой аккаунт СКАН' ?></p>

        <form action="login.php" method="post" id="loginForm">

            <!-- Переключатель ролей -->
            <div class="role-switch <?= $activeRole === 'teacher' ? 'teacher-active' : '' ?>" id="roleSwitch">
                <label class="role-btn <?= $activeRole === 'student' ? 'active' : '' ?>" id="btn-student">
                    <input type="radio" name="role" value="student" <?= $activeRole === 'student' ? 'checked' : '' ?> onchange="switchRole('student')">
                    <i class="fas fa-user-graduate"></i> Ученик
                </label>
                <label class="role-btn <?= $activeRole === 'teacher' ? 'active' : '' ?>" id="btn-teacher">
                    <input type="radio" name="role" value="teacher" <?= $activeRole === 'teacher' ? 'checked' : '' ?> onchange="switchRole('teacher')">
                    <i class="fas fa-chalkboard-user"></i> Учитель
                </label>
            </div>

            <!-- Ошибка -->
            <?php if (!empty($error)): ?>
                <div class="error-msg">
                    <i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Поля -->
            <div class="input-group">
                <i class="fas fa-user input-icon"></i>
                <input type="text" name="username" required placeholder="Логин" value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" class="form-input">
            </div>
            
            <div class="input-group" style="margin-bottom: 24px;">
                <i class="fas fa-lock input-icon"></i>
                <input type="password" name="password" id="passInput" required placeholder="Пароль" class="form-input" style="padding-right: 48px;">
                <button type="button" onclick="togglePassword()" style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--login-text-sec); cursor: pointer; font-size: 14px; z-index: 2;">
                    <i class="fas fa-eye" id="eyeIcon"></i>
                </button>
            </div>

            <!-- Кнопка входа -->
            <button type="submit" id="submitBtn" class="btn-submit <?= $activeRole === 'teacher' ? 'teacher-mode' : '' ?>">
                <i class="fas fa-arrow-right-to-bracket"></i> Войти
            </button>

            <!-- Ссылки -->
            <div class="login-links">
                <a href="register.php" id="registerLink">
                    <i class="fas fa-user-plus"></i> Создать аккаунт ученика
                </a>
                <a href="#" class="secondary-link">Забыли пароль?</a>
            </div>

        </form>

        <!-- Футер -->
        <div class="login-footer">
            © <?= date('Y') ?> СКАН — Интеллектуальная платформа
        </div>

    </div>
</div>

<script>
    function togglePassword() {
        const pass = document.getElementById('passInput');
        const icon = document.getElementById('eyeIcon');
        if (pass.type === 'password') {
            pass.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            pass.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    function switchRole(role) {
        const btnStudent = document.getElementById('btn-student');
        const btnTeacher = document.getElementById('btn-teacher');
        const title = document.getElementById('mainTitle');
        const subtitle = document.getElementById('mainSubtitle');
        const icon = document.getElementById('mainIcon');
        const submitBtn = document.getElementById('submitBtn');
        const registerLink = document.getElementById('registerLink');
        const roleSwitch = document.getElementById('roleSwitch');

        if (role === 'teacher') {
            btnStudent.classList.remove('active');
            btnTeacher.classList.add('active');
            roleSwitch.classList.add('teacher-active');
            
            title.textContent = 'Режим учителя';
            subtitle.textContent = 'Доступ к управлению и статистике';
            icon.innerHTML = '<i class="fas fa-chalkboard-user"></i>';
            icon.classList.add('teacher');
            
            submitBtn.classList.add('teacher-mode');
            submitBtn.innerHTML = '<i class="fas fa-chalkboard-user"></i> Войти как учитель';
            
            registerLink.innerHTML = '<i class="fas fa-info-circle"></i> Аккаунты учителей создает администратор';
            registerLink.href = '#';
            registerLink.style.color = '#a1a1a6';
            registerLink.style.textDecoration = 'none';
        } else {
            btnTeacher.classList.remove('active');
            btnStudent.classList.add('active');
            roleSwitch.classList.remove('teacher-active');
            
            title.textContent = 'Добро пожаловать';
            subtitle.textContent = 'Войдите в свой аккаунт СКАН';
            icon.innerHTML = '<i class="fas fa-graduation-cap"></i>';
            icon.classList.remove('teacher');
            
            submitBtn.classList.remove('teacher-mode');
            submitBtn.innerHTML = '<i class="fas fa-arrow-right-to-bracket"></i> Войти';
            
            registerLink.innerHTML = '<i class="fas fa-user-plus"></i> Создать аккаунт ученика';
            registerLink.href = 'register.php';
            registerLink.style.color = 'var(--login-primary)';
        }
    }
</script>