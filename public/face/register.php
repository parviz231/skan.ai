<?php
session_start();

require_once("config2.php");

$error = "";
$success = "";

if (isset($_POST['register'])) {
    
    if ($conn->connect_error) {
        die("Ошибка подключения: " . $conn->connect_error);
    }

    $surname = trim($_POST['surname']);
    $name = trim($_POST['name']);
    $patronymic = trim($_POST['patronymic']);
    $gender = $_POST['gender'];
    $birth = $_POST['birth'];
    $grade = (int)$_POST['grade'];
    $class = $_POST['class'];
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($surname) || empty($name) || empty($username) || empty($password) || empty($confirm_password) || empty($birth) || empty($gender) || empty($grade) || empty($class)) {
        $error = "Все обязательные поля должны быть заполнены!";
    } elseif (strlen($username) < 3) {
        $error = "Логин должен содержать минимум 3 символа!";
    } elseif (strlen($password) < 6) {
        $error = "Пароль должен содержать минимум 6 символов!";
    } elseif ($password !== $confirm_password) {
        $error = "Пароли не совпадают!";
    } else {
        
        $check_stmt = $conn->prepare("SELECT user_id FROM students WHERE nickname = ? LIMIT 1");
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "Пользователь с таким логином уже существует!";
        } else {
            $conn->begin_transaction();
            
            try {
                $insert_user_stmt = $conn->prepare("
                    INSERT INTO users (surname, name, patronymic, gender, birth, grade, class, address, phone) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $address = !empty($address) ? $address : null;
                $phone = !empty($phone) ? $phone : null;
                
                $insert_user_stmt->bind_param(
                    "sssssisss",
                    $surname,
                    $name,
                    $patronymic,
                    $gender,
                    $birth,
                    $grade,
                    $class,
                    $address,
                    $phone
                );
                
                if (!$insert_user_stmt->execute()) {
                    throw new Exception("Ошибка при создании профиля: " . $conn->error);
                }
                
                $new_user_id = $conn->insert_id;
                $insert_user_stmt->close();
                
                $insert_stmt = $conn->prepare("INSERT INTO students (user_id, nickname, password) VALUES (?, ?, ?)");
                $insert_stmt->bind_param("iss", $new_user_id, $username, $password);
                
                if (!$insert_stmt->execute()) {
                    throw new Exception("Ошибка при регистрации: " . $conn->error);
                }
                
                $insert_stmt->close();
                
                $conn->commit();
                
                $success = "Регистрация успешно завершена!";
                $_SESSION['username1'] = $username;
                $_SESSION['id1'] = $new_user_id;
                
                unset($_POST);
                
                header("refresh:2;url=profile.php");
                
            } catch (Exception $e) {
                $conn->rollback();
                $error = $e->getMessage();
            }
        }
        $check_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация — СКАН</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        /* ===== Палитра как в header.php ===== */
        :root {
            --bg-page: #f5f5f7;
            --surface: #ffffff;
            --surface-secondary: #f0f0f2;
            --text: #1d1d1f;
            --text-secondary: #6e6e73;
            --text-tertiary: #86868b;
            --border: #e5e5ea;
            --border-strong: #d2d2d7;
            --accent: #0071e3;
            --accent-hover: #0077ed;
            --accent-light: #e8f4fd;
            --success: #34c759;
            --success-light: #e8f8ed;
            --danger: #ff3b30;
            --danger-light: #fde8e7;
            --warning: #ff9500;
            --radius-sm: 6px;
            --radius-md: 8px;
            --radius-lg: 12px;
            --radius-xl: 16px;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 12px 40px rgba(0, 0, 0, 0.1);
            --transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
            --header-height: 114px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            min-height: 100%;
        }

        body {
            font-family: var(--font-sans);
            background: var(--bg-page);
            color: var(--text);
            -webkit-font-smoothing: antialiased;
        }

        /* ===== Обёртка ===== */
        .register-wrapper {
            display: flex;
            align-items: flex-start;
            justify-content: center;
            width: 100%;
            min-height: calc(100vh - var(--header-height));
            padding: 40px 20px;
        }

        .register-container {
            width: 820px;
            max-width: 100%;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            padding: 40px 44px 34px;
            box-shadow: var(--shadow-md);
            animation: cardIn 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes cardIn {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ===== Шапка карточки ===== */
        .register-header {
            text-align: center;
            margin-bottom: 32px;
            padding-bottom: 24px;
            border-bottom: 1px solid var(--border);
        }

        .register-header .icon {
            width: 64px;
            height: 64px;
            border-radius: var(--radius-lg);
            background: var(--accent);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 18px;
            font-size: 26px;
            box-shadow: 0 8px 24px rgba(0, 113, 227, 0.25);
        }

        .register-header h1 {
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.03em;
            color: var(--text);
            margin-bottom: 6px;
        }

        .register-header p {
            color: var(--text-secondary);
            font-size: 14px;
        }

        /* ===== Секции ===== */
        .form-section {
            margin-bottom: 26px;
        }

        .form-section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
            letter-spacing: -0.01em;
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border);
        }

        .form-section-title .section-icon {
            width: 26px;
            height: 26px;
            border-radius: var(--radius-sm);
            background: var(--surface-secondary);
            color: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            flex-shrink: 0;
        }

        .form-section-title .badge {
            font-size: 10px;
            font-weight: 600;
            padding: 3px 9px;
            border-radius: var(--radius-full);
            margin-left: auto;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .badge-required {
            background: var(--danger-light);
            color: var(--danger);
        }

        .badge-optional {
            background: var(--surface-secondary);
            color: var(--text-tertiary);
        }

        /* ===== Поля ===== */
        .input-group {
            margin-bottom: 14px;
        }

        .input-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 7px;
            letter-spacing: -0.01em;
        }

        .input-group label .required {
            color: var(--danger);
            margin-left: 2px;
        }

        .input-group label .optional {
            color: var(--text-tertiary);
            font-weight: 400;
            font-size: 11px;
        }

        .register-container input,
        .register-container select {
            width: 100%;
            height: 46px;
            padding: 0 14px;
            border: 1px solid var(--border-strong);
            border-radius: var(--radius-md);
            font-size: 14px;
            font-family: var(--font-sans);
            font-weight: 500;
            background: var(--surface);
            color: var(--text);
            transition: all var(--transition);
            outline: none;
        }

        .register-container input::placeholder {
            color: var(--text-tertiary);
            font-weight: 400;
        }

        .register-container select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 12 12'%3E%3Cpath fill='%2386868b' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            cursor: pointer;
            padding-right: 38px;
        }

        .register-container input:hover,
        .register-container select:hover {
            border-color: var(--text-tertiary);
        }

        .register-container input:focus,
        .register-container select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-light);
        }

        /* Дата рождения */
        .register-container input[type="date"] {
            cursor: pointer;
        }

        /* ===== Сетка 2 колонки ===== */
        .form-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .form-grid-2 .input-group {
            margin-bottom: 0;
        }

        /* ===== Пароль ===== */
        .password-strength {
            margin-top: 8px;
            height: 4px;
            border-radius: 4px;
            background: var(--surface-secondary);
            overflow: hidden;
        }

        .password-strength .bar {
            height: 100%;
            width: 0%;
            border-radius: 4px;
            transition: width 0.4s ease, background 0.4s ease;
        }

        .password-strength .bar.weak   { width: 33%;  background: var(--danger); }
        .password-strength .bar.medium { width: 66%;  background: var(--warning); }
        .password-strength .bar.strong { width: 100%; background: var(--success); }

        .password-hint {
            font-size: 11.5px;
            color: var(--text-tertiary);
            margin-top: 6px;
            display: none;
            font-weight: 500;
        }

        .password-hint.show {
            display: block;
            animation: fadeIn 0.25s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }

        /* ===== Сообщения ===== */
        .error,
        .success {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 11px 14px;
            border-radius: var(--radius-md);
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 16px;
            animation: pop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .error {
            background: var(--danger-light);
            border: 1px solid rgba(255, 59, 48, 0.25);
            color: var(--danger);
        }

        .success {
            background: var(--success-light);
            border: 1px solid rgba(52, 199, 89, 0.25);
            color: #1a8a3e;
        }

        .error i,
        .success i {
            font-size: 14px;
            flex-shrink: 0;
        }

        .success small {
            display: block;
            font-weight: 400;
            color: #1a8a3e;
            opacity: 0.85;
            margin-top: 2px;
        }

        @keyframes pop {
            from { opacity: 0; transform: scale(0.95); }
            to   { opacity: 1; transform: scale(1); }
        }

        /* ===== Кнопка ===== */
        .btn-submit {
            width: 100%;
            height: 50px;
            margin-top: 4px;
            border: none;
            border-radius: var(--radius-md);
            background: var(--accent);
            color: #fff;
            font-family: var(--font-sans);
            font-size: 15px;
            font-weight: 600;
            letter-spacing: -0.01em;
            cursor: pointer;
            transition: all var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
        }

        .btn-submit i {
            font-size: 14px;
            transition: transform var(--transition);
        }

        .btn-submit:hover {
            background: var(--accent-hover);
            box-shadow: 0 8px 20px rgba(0, 113, 227, 0.28);
        }

        .btn-submit:hover i {
            transform: translateX(3px);
        }

        .btn-submit:active {
            transform: scale(0.99);
        }

        /* ===== Ссылки ===== */
        .links {
            margin-top: 18px;
            padding-top: 18px;
            border-top: 1px solid var(--border);
            text-align: center;
            font-size: 13px;
        }

        .links a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            transition: all var(--transition);
        }

        .links a i {
            font-size: 12px;
        }

        .links a:hover {
            color: var(--accent-hover);
            text-decoration: underline;
        }

        /* ===== Адаптив ===== */
        @media (max-width: 700px) {
            :root {
                --header-height: 180px;
            }

            .register-wrapper {
                padding: 24px 14px;
            }

            .register-container {
                padding: 28px 22px 24px;
                border-radius: var(--radius-lg);
            }

            .register-header .icon {
                width: 56px;
                height: 56px;
                font-size: 22px;
            }

            .register-header h1 {
                font-size: 20px;
            }

            .form-grid-2 {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .form-grid-2 .input-group {
                margin-bottom: 14px;
            }

            .register-container input,
            .register-container select {
                height: 44px;
                font-size: 13.5px;
            }

            .btn-submit {
                height: 46px;
                font-size: 14px;
            }
        }

        @media (max-width: 480px) {
            .register-container {
                padding: 22px 16px 20px;
            }

            .register-header h1 {
                font-size: 19px;
            }

            .form-section-title {
                font-size: 12px;
            }

            .input-group label {
                font-size: 11.5px;
            }

            .register-container input,
            .register-container select {
                height: 42px;
                font-size: 13px;
            }
        }
    </style>
</head>

<body>

<!-- ===== ШАПКА САЙТА ===== -->
<?php require_once("header.php"); ?>

<!-- ===== РЕГИСТРАЦИЯ ===== -->
<div class="register-wrapper">

    <div class="register-container">

        <!-- Заголовок -->
        <div class="register-header">
            <div class="icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <h1>Регистрация</h1>
            <p>Заполните форму для создания аккаунта в системе СКАН</p>
        </div>

        <form action="register.php" method="post">

            <!-- ========== СЕКЦИЯ 1: Данные для входа ========== -->
            <div class="form-section">
                <div class="form-section-title">
                    <span class="section-icon"><i class="fas fa-key"></i></span>
                    Данные для входа
                    <span class="badge badge-required">Обязательно</span>
                </div>

                <div class="input-group">
                    <label for="username">Логин <span class="required">*</span></label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        placeholder="Введите логин (минимум 3 символа)"
                        value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
                        required
                        minlength="3"
                    >
                </div>

                <?php if ($error == "Пользователь с таким логином уже существует!" || $error == "Логин должен содержать минимум 3 символа!"): ?>
                    <div class="error">
                        <i class="fas fa-circle-exclamation"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <div class="input-group">
                    <label for="password">Пароль <span class="required">*</span></label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Введите пароль (минимум 6 символов)"
                        required
                        minlength="6"
                        onkeyup="checkPasswordStrength(this.value)"
                    >
                    <div class="password-strength">
                        <div class="bar" id="strengthBar"></div>
                    </div>
                    <div class="password-hint" id="passwordHint">
                        <span id="strengthText">Слабый пароль</span>
                    </div>
                </div>

                <?php if ($error == "Пароль должен содержать минимум 6 символов!"): ?>
                    <div class="error">
                        <i class="fas fa-circle-exclamation"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <div class="input-group">
                    <label for="confirm_password">Подтверждение пароля <span class="required">*</span></label>
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Повторите пароль"
                        required
                    >
                </div>

                <?php if ($error == "Пароли не совпадают!"): ?>
                    <div class="error">
                        <i class="fas fa-circle-exclamation"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ========== СЕКЦИЯ 2: Личные данные ========== -->
            <div class="form-section">
                <div class="form-section-title">
                    <span class="section-icon"><i class="fas fa-id-card"></i></span>
                    Личные данные
                    <span class="badge badge-required">Обязательно</span>
                </div>

                <div class="input-group">
                    <label for="surname">Фамилия <span class="required">*</span></label>
                    <input
                        type="text"
                        id="surname"
                        name="surname"
                        placeholder="Введите фамилию"
                        value="<?= isset($_POST['surname']) ? htmlspecialchars($_POST['surname']) : '' ?>"
                        required
                    >
                </div>

                <div class="input-group">
                    <label for="name">Имя <span class="required">*</span></label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        placeholder="Введите имя"
                        value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>"
                        required
                    >
                </div>

                <div class="input-group">
                    <label for="patronymic">Отчество <span class="optional">(необязательно)</span></label>
                    <input
                        type="text"
                        id="patronymic"
                        name="patronymic"
                        placeholder="Введите отчество"
                        value="<?= isset($_POST['patronymic']) ? htmlspecialchars($_POST['patronymic']) : '' ?>"
                    >
                </div>

                <div class="form-grid-2">
                    <div class="input-group">
                        <label for="gender">Пол <span class="required">*</span></label>
                        <select id="gender" name="gender" required>
                            <option value="">Выберите пол</option>
                            <option value="male" <?= isset($_POST['gender']) && $_POST['gender'] == 'male' ? 'selected' : '' ?>>Мужской</option>
                            <option value="female" <?= isset($_POST['gender']) && $_POST['gender'] == 'female' ? 'selected' : '' ?>>Женский</option>
                        </select>
                    </div>

                    <div class="input-group">
                        <label for="birth">Дата рождения <span class="required">*</span></label>
                        <input
                            type="date"
                            id="birth"
                            name="birth"
                            value="<?= isset($_POST['birth']) ? htmlspecialchars($_POST['birth']) : '' ?>"
                            required
                        >
                    </div>
                </div>
            </div>

            <!-- ========== СЕКЦИЯ 3: Учебная информация ========== -->
            <div class="form-section">
                <div class="form-section-title">
                    <span class="section-icon"><i class="fas fa-graduation-cap"></i></span>
                    Учебная информация
                    <span class="badge badge-required">Обязательно</span>
                </div>

                <div class="form-grid-2">
                    <div class="input-group">
                        <label for="grade">Класс <span class="required">*</span></label>
                        <select id="grade" name="grade" required>
                            <option value="">Выберите класс</option>
                            <?php for($i = 5; $i <= 11; $i++): ?>
                                <option value="<?= $i ?>" <?= isset($_POST['grade']) && $_POST['grade'] == $i ? 'selected' : '' ?>><?= $i ?> класс</option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="input-group">
                        <label for="class">Буква класса <span class="required">*</span></label>
                        <select id="class" name="class" required>
                            <option value="">Выберите букву</option>
                            <?php foreach(['A', 'Б', 'В'] as $letter): ?>
                                <option value="<?= $letter ?>" <?= isset($_POST['class']) && $_POST['class'] == $letter ? 'selected' : '' ?>>«<?= $letter ?>»</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- ========== СЕКЦИЯ 4: Контактные данные ========== -->
            <div class="form-section">
                <div class="form-section-title">
                    <span class="section-icon"><i class="fas fa-address-card"></i></span>
                    Контактные данные
                    <span class="badge badge-optional">Необязательно</span>
                </div>

                <div class="input-group">
                    <label for="address">Адрес проживания <span class="optional">(необязательно)</span></label>
                    <input
                        type="text"
                        id="address"
                        name="address"
                        placeholder="Введите адрес"
                        value="<?= isset($_POST['address']) ? htmlspecialchars($_POST['address']) : '' ?>"
                    >
                </div>

                <div class="input-group">
                    <label for="phone">Номер телефона <span class="required">*</span></label>
                    <input
                        type="text"
                        id="phone"
                        name="phone"
                        placeholder="Введите номер телефона"
                        value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>"
                    >
                </div>
            </div>

            <!-- Общая ошибка (например, ошибка БД) -->
            <?php if (!empty($error) && $error != "Все обязательные поля должны быть заполнены!" && 
                      $error != "Пользователь с таким логином уже существует!" && 
                      $error != "Логин должен содержать минимум 3 символа!" && 
                      $error != "Пароль должен содержать минимум 6 символов!" && 
                      $error != "Пароли не совпадают!"): ?>
                <div class="error">
                    <i class="fas fa-circle-exclamation"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Успех -->
            <?php if (!empty($success)): ?>
                <div class="success">
                    <i class="fas fa-circle-check"></i>
                    <div>
                        <?= htmlspecialchars($success) ?>
                        <small>Перенаправление на профиль...</small>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Кнопка -->
            <button type="submit" name="register" class="btn-submit">
                <i class="fas fa-user-plus"></i>
                Зарегистрироваться
            </button>

            <!-- Ссылка -->
            <div class="links">
                <a href="login.php">
                    <i class="fas fa-arrow-right-to-bracket"></i>
                    Уже есть аккаунт? Войти
                </a>
            </div>

        </form>

    </div>

</div>

<script>
    function checkPasswordStrength(password) {
        const bar = document.getElementById('strengthBar');
        const hint = document.getElementById('passwordHint');
        const text = document.getElementById('strengthText');
        
        if (password.length === 0) {
            bar.className = 'bar';
            hint.classList.remove('show');
            return;
        }
        
        hint.classList.add('show');
        
        let strength = 0;
        if (password.length >= 6) strength++;
        if (password.length >= 8) strength++;
        if (password.length >= 10) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^a-zA-Z0-9]/.test(password)) strength++;
        
        if (strength <= 2) {
            bar.className = 'bar weak';
            text.textContent = 'Слабый пароль';
            text.style.color = '#ff3b30';
        } else if (strength <= 4) {
            bar.className = 'bar medium';
            text.textContent = 'Средний пароль';
            text.style.color = '#ff9500';
        } else {
            bar.className = 'bar strong';
            text.textContent = 'Сильный пароль';
            text.style.color = '#34c759';
        }
    }
</script>

</body>
</html>