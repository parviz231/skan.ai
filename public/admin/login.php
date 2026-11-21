<?php
session_start();
if(isset($_SESSION['uid']) && !empty($_SESSION['uid']))
{
    //header("Location:index.php");
}

require_once("../config2.php");
if (isset($_POST['signIn'])){
    $login = $_POST['login'];
    $password = $_POST['password'];
    
    $sql = "SELECT * from register WHERE login = '$login' AND `password`= ('$password')";
    $result = mysqli_query($conn, $sql);
    if (mysqli_num_rows($result) > 0){
        $row = mysqli_fetch_assoc($result);
        $_SESSION['uid'] = $row['id'];
        $_SESSION['username'] = $row['name'];
        $_SESSION['surname'] = $row['surname'];
        echo "<script>window.location.href='index.php';</script>";
    } else {
        $error = "Неверный логин или пароль";
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Вход в систему</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-light: #818cf8;
            --primary-dark: #3730a3;
            --secondary: #7c3aed;
            --success: #10b981;
            --danger: #ef4444;
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
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 20px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #4f46e5 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        
        /* Декоративные круги */
        body::before,
        body::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            animation: float 10s ease-in-out infinite;
        }
        
        body::before {
            width: 400px;
            height: 400px;
            top: -100px;
            left: -100px;
        }
        
        body::after {
            width: 300px;
            height: 300px;
            bottom: -50px;
            right: -50px;
            animation-delay: 3s;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-20px) scale(1.1); }
        }
        
        .login-container {
            width: 100%;
            max-width: 420px;
            position: relative;
            z-index: 1;
            animation: slideInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }
        
        @keyframes slideInUp {
            from { opacity: 0; transform: translateY(40px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            border-radius: var(--radius-xl);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            padding: 40px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .logo {
            text-align: center;
            margin-bottom: 35px;
        }
        
        .logo .icon-wrapper {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 30px;
            color: white;
            box-shadow: 0 10px 25px rgba(79, 70, 229, 0.4);
            animation: iconPulse 2s ease-in-out infinite;
        }
        
        @keyframes iconPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .logo h1 {
            font-size: 24px;
            font-weight: 800;
            color: var(--text-primary);
            letter-spacing: -0.5px;
            margin-bottom: 5px;
        }
        
        .logo p {
            font-size: 14px;
            color: var(--text-secondary);
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-wrapper .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 16px;
            pointer-events: none;
            transition: var(--transition);
        }
        
        .input-wrapper input {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: 15px;
            background: #fafafa;
            transition: var(--transition);
            color: var(--text-primary);
            font-family: 'Inter', sans-serif;
            height: 50px;
        }
        
        .input-wrapper input:hover {
            border-color: #d1d5db;
            background: #fff;
        }
        
        .input-wrapper input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
            background: #fff;
        }
        
        .input-wrapper input:focus + .input-icon,
        .input-wrapper input:focus ~ .input-icon {
            color: var(--primary);
        }
        
        .input-wrapper input::placeholder {
            color: #b0b0b0;
        }
        
        .btn-login {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: var(--radius-md);
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: #fff;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            height: 50px;
            position: relative;
            overflow: hidden;
        }
        
        .btn-login::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .btn-login:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .btn-login:hover {
            box-shadow: 0 8px 25px rgba(79, 70, 229, 0.5);
            transform: translateY(-2px);
        }
        
        .btn-login:active {
            transform: scale(0.97);
        }
        
        .alert {
            padding: 14px 18px;
            border-radius: var(--radius-md);
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            animation: shake 0.5s ease;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #fef2f2, #fee2e2);
            border: 1px solid #fca5a5;
            color: #991b1b;
        }
        
        .links {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            font-size: 13px;
        }
        
        .links a {
            color: var(--text-secondary);
            text-decoration: none;
            transition: var(--transition);
            font-weight: 500;
        }
        
        .links a:hover {
            color: var(--primary);
        }
        
        .divider {
            height: 1px;
            background: var(--border-color);
            margin: 20px 0;
        }
        
        .footer-text {
            text-align: center;
            font-size: 12px;
            color: var(--text-light);
            margin-top: 20px;
        }
        
        @media (max-width: 480px) {
            .login-card {
                padding: 25px 20px;
                border-radius: var(--radius-lg);
            }
            
            .logo .icon-wrapper {
                width: 60px;
                height: 60px;
                font-size: 25px;
                border-radius: 16px;
            }
            
            .logo h1 {
                font-size: 20px;
            }
            
            .input-wrapper input {
                height: 45px;
                font-size: 14px;
            }
            
            .btn-login {
                height: 45px;
                font-size: 14px;
            }
            
            .links {
                flex-direction: column;
                gap: 10px;
                align-items: center;
            }
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-card">
        <!-- Логотип -->
        <div class="logo">
            <div class="icon-wrapper">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <h1>Электронный журнал</h1>
            <p>Войдите в свою учетную запись</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= $error ?></span>
            </div>
        <?php endif; ?>

        <!-- Форма входа -->
        <form action="" method="post">
            <div class="form-group">
                <label>Логин</label>
                <div class="input-wrapper">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" name="login" placeholder="Введите логин" required>
                </div>
            </div>

            <div class="form-group">
                <label>Пароль</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" name="password" placeholder="Введите пароль" required>
                </div>
            </div>

            <button type="submit" name="signIn" class="btn-login">
                <i class="fas fa-sign-in-alt"></i>
                Войти
            </button>
        </form>

        <div class="divider"></div>

        <div class="links">
            <a href="forgot-password.html">
                <i class="fas fa-key"></i>
                Забыли пароль?
            </a>
            <a href="register.php">
                <i class="fas fa-user-plus"></i>
                Регистрация
            </a>
        </div>

        <div class="footer-text">
            © <?= date('Y') ?> Электронный журнал. Все права защищены.
        </div>
    </div>
</div>

</body>
</html>