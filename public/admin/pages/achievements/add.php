<?php
require_once("config.php");
date_default_timezone_set("Asia/Dushanbe");
$error1 = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $surname = mysqli_real_escape_string($conn, trim($_POST['surname']));
    $patronymic = mysqli_real_escape_string($conn, trim($_POST['patronymic']));
    $class = mysqli_real_escape_string($conn, trim($_POST['grade']));
    $competition = mysqli_real_escape_string($conn, trim($_POST['competition_name']));
    $type = mysqli_real_escape_string($conn, trim($_POST['competition_type']));
    $place = mysqli_real_escape_string($conn, trim($_POST['place']));

    if (!empty($_POST['school_year'])) {
        $school_year = mysqli_real_escape_string($conn, $_POST['school_year']);
    } else {
        $year_start = (date('n') >= 9) ? date('Y') : date('Y') - 1;
        $year_end = $year_start + 1;
        $school_year = "$year_start/$year_end";
    }

    $sql_student = "SELECT id FROM users 
                    WHERE surname='$surname' 
                      AND name='$name' 
                      AND patronymic='$patronymic' 
                      AND CONCAT(grade, class)='$class'";

    $res = mysqli_query($conn, $sql_student);

    if ($res && mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        $student_id = $row['id'];

        $sql_insert = "INSERT INTO achievements 
                        (student_id, competition_name, competition_type, place, school_year) 
                        VALUES ('$student_id', '$competition', '$type', '$place', '$school_year')";
        if (mysqli_query($conn, $sql_insert)) {
            echo "<script>window.location.href='?page=20';</script>";
            exit;
        } else {
            $error1 = "<p style='color:red;'>Ошибка при добавлении: " . mysqli_error($conn) . "</p>";
        }
    } else {
        $error1 = "<p style='color:red;'>❌ Ученик не найден! Проверьте ФИО и класс.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавление достижения</title>
    <link rel="stylesheet" href="css/bootstrap.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-light: #818cf8;
            --primary-dark: #3730a3;
            --secondary: #7c3aed;
            --success: #10b981;
            --success-dark: #059669;
            --danger: #ef4444;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --text-light: #9ca3af;
            --bg-main: #f9fafb;
            --bg-card: #ffffff;
            --border-color: #e5e7eb;
            --border-focus: #4f46e5;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --radius-sm: 6px;
            --radius-md: 8px;
            --radius-lg: 12px;
            --radius-xl: 16px;
            --transition-fast: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            background: linear-gradient(180deg, #f9fafb 0%, #f3f4f6 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            padding: 30px 20px;
            position: relative;
            overflow-x: hidden;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #4f46e5, #7c3aed, #10b981);
            z-index: 1000;
            animation: gradientShift 3s ease infinite;
            background-size: 200% 200%;
        }
        
        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        .container-fluid {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }
        
        .card {
            background: var(--bg-card);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            padding: 40px;
            margin-bottom: 30px;
            animation: slideInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
            border: 1px solid rgba(229, 231, 235, 0.5);
            position: relative;
            overflow: hidden;
        }
        
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #4f46e5, #7c3aed);
            opacity: 0;
            transition: var(--transition);
        }
        
        .card:hover::before {
            opacity: 1;
        }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(40px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .card-header {
            padding: 0 0 25px 0;
            border-bottom: 2px solid #f3f4f6;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .card-header h2 {
            margin: 0;
            font-size: 28px;
            font-weight: 800;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 15px;
            letter-spacing: -0.5px;
        }
        
        .card-header h2 .icon-wrapper {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
            animation: iconBounce 2s ease-in-out infinite;
        }
        
        @keyframes iconBounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }
        
        .back-link {
            font-size: 15px;
            color: var(--text-secondary);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            border-radius: 50px;
            transition: var(--transition);
            font-weight: 500;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
        }
        
        .back-link:hover {
            color: white;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            border-color: transparent;
            transform: translateX(-5px);
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
        }
        
        .form-group {
            margin-bottom: 25px;
            opacity: 0;
            animation: fadeInUp 0.5s ease forwards;
        }
        
        .form-group:nth-child(1) { animation-delay: 0.1s; }
        .form-group:nth-child(2) { animation-delay: 0.2s; }
        .form-group:nth-child(3) { animation-delay: 0.3s; }
        .form-group:nth-child(4) { animation-delay: 0.4s; }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 8px;
            letter-spacing: 1px;
            text-transform: uppercase;
            transition: var(--transition);
        }
        
        .form-group label .required {
            color: var(--danger);
            margin-left: 2px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
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
            z-index: 2;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: 15px;
            background: #fafafa;
            transition: var(--transition);
            height: 48px;
            color: var(--text-primary);
            font-family: 'Inter', sans-serif;
            font-weight: 500;
        }
        
        .form-group input:hover,
        .form-group select:hover {
            border-color: #d1d5db;
            background: #fff;
            transform: translateY(-1px);
            box-shadow: var(--shadow-sm);
        }
        
        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--border-focus);
            outline: none;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1), 0 4px 12px rgba(0,0,0,0.05);
            background: #fff;
            transform: translateY(-2px);
        }
        
        .form-group input:focus ~ .input-icon,
        .form-group select:focus ~ .input-icon {
            color: var(--primary);
            transform: translateY(-50%) scale(1.1);
        }
        
        .form-group input::placeholder {
            color: #b0b0b0;
            font-weight: 400;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }
        
        .btn-group-actions {
            display: flex;
            gap: 15px;
            margin-top: 35px;
            flex-wrap: wrap;
            opacity: 0;
            animation: fadeInUp 0.5s ease 0.5s forwards;
        }
        
        .btn {
            font-size: 15px;
            padding: 12px 30px;
            border-radius: var(--radius-md);
            border: none;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-weight: 600;
            height: 50px;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
            font-family: 'Inter', sans-serif;
        }
        
        .btn::before {
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
        
        .btn:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .btn:active {
            transform: scale(0.95);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: #fff;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }
        
        .btn-success:hover {
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
            transform: translateY(-2px);
            color: white;
        }
        
        .btn-secondary {
            background: #fff;
            color: var(--text-primary);
            border: 2px solid var(--border-color);
        }
        
        .btn-secondary:hover {
            background: #f9fafb;
            border-color: #d1d5db;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            color: var(--text-primary);
        }
        
        .alert {
            padding: 16px 20px;
            border-radius: var(--radius-md);
            margin-bottom: 25px;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: alertIn 0.5s cubic-bezier(0.16, 1, 0.3, 1);
            font-weight: 500;
        }
        
        @keyframes alertIn {
            from {
                opacity: 0;
                transform: translateY(-20px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #fef2f2, #fee2e2);
            border: 1px solid #fca5a5;
            color: #991b1b;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);
        }
        
        .alert i {
            font-size: 20px;
            flex-shrink: 0;
        }
        
        .form-group select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            padding-right: 40px;
            cursor: pointer;
        }
        
        @media (max-width: 768px) {
            body {
                padding: 20px 10px;
            }
            .card {
                padding: 30px 20px;
                border-radius: var(--radius-lg);
            }
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
            .card-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .card-header h2 {
                font-size: 24px;
            }
            .card-header h2 .icon-wrapper {
                width: 40px;
                height: 40px;
                font-size: 18px;
            }
            .btn-group-actions {
                flex-direction: column;
            }
            .btn-group-actions .btn {
                width: 100%;
            }
        }
        
        @media (max-width: 480px) {
            .card {
                padding: 25px 15px;
            }
            .card-header h2 {
                font-size: 20px;
            }
            .form-group label {
                font-size: 11px;
            }
            .form-group input,
            .form-group select {
                font-size: 14px;
                height: 44px;
                padding: 10px 12px 10px 40px;
            }
            .btn {
                height: 45px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>

<div class="container-fluid">

<?php if ($error1): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i>
        <span><?= $error1 ?></span>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2>
            <span class="icon-wrapper">
                <i class="fas fa-trophy"></i>
            </span>
            Добавление достижения
        </h2>
        <a href="?page=20" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Назад к списку
        </a>
    </div>

    <form method="POST" action="?page=19">
        <div class="form-row">
            <div class="form-group">
                <label>Фамилия <span class="required">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" name="surname" value="<?= htmlspecialchars($_POST['surname'] ?? '') ?>" required placeholder="Введите фамилию">
                </div>
            </div>
            <div class="form-group">
                <label>Имя <span class="required">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required placeholder="Введите имя">
                </div>
            </div>
        </div>

        <div class="form-group">
            <label>Отчество</label>
            <div class="input-wrapper">
                <i class="fas fa-user input-icon"></i>
                <input type="text" name="patronymic" value="<?= htmlspecialchars($_POST['patronymic'] ?? '') ?>" placeholder="Введите отчество">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Класс <span class="required">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-graduation-cap input-icon"></i>
                    <select name="grade" required>
                        <option value="">Выберите класс</option>
                        <?php
                        $selected_grade = $_POST['grade'] ?? '';
                        $letters = ['А', 'Б', 'В'];
                        for ($i = 5; $i <= 11; $i++) {
                            foreach ($letters as $letter) {
                                $val = $i . $letter;
                                $sel = ($val == $selected_grade) ? 'selected' : '';
                                echo "<option value='$val' $sel>$val</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Учебный год <span class="required">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-calendar input-icon"></i>
                    <select name="school_year" required>
                        <?php
                        $current_year = date('Y');
                        $selected_year = $_POST['school_year'] ?? '';
                        for ($i = $current_year - 2; $i <= $current_year + 1; $i++) {
                            $next = $i + 1;
                            $label = "$i/$next";
                            $sel = ($label == $selected_year) ? 'selected' : '';
                            echo "<option value='$label' $sel>$label</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label>Название соревнования <span class="required">*</span></label>
            <div class="input-wrapper">
                <i class="fas fa-medal input-icon"></i>
                <input type="text" name="competition_name" value="<?= htmlspecialchars($_POST['competition_name'] ?? '') ?>" required placeholder="Введите название соревнования">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Тип соревнования <span class="required">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-flag input-icon"></i>
                    <select name="competition_type" required>
                        <option value="">Выберите тип</option>
                        <?php
                        $types = ["школьные", "городские", "областные", "республиканские", "международные", "спортивные"];
                        $selected_type = $_POST['competition_type'] ?? '';
                        foreach ($types as $t) {
                            $sel = ($selected_type == $t) ? 'selected' : '';
                            echo "<option value='$t' $sel>" . ucfirst($t) . "</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Место <span class="required">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-award input-icon"></i>
                    <select name="place" required>
                        <option value="">Выберите место</option>
                        <?php
                        $places = ["1", "2", "3", "сертификат", "участие"];
                        $selected_place = $_POST['place'] ?? '';
                        foreach ($places as $p) {
                            $sel = ($selected_place == $p) ? 'selected' : '';
                            $label = ($p == "сертификат") ? "Сертификат" : (($p == "участие") ? "Участие" : $p . " место");
                            echo "<option value='$p' $sel>$label</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="btn-group-actions">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-plus"></i>
                Добавить достижение
            </button>
            <a href="?page=20" class="btn btn-secondary">
                <i class="fas fa-times"></i>
                Отмена
            </a>
        </div>
    </form>
</div>

</div>

<script src="js/bootstrap.bundle.js"></script>
</body>
</html>