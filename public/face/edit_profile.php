<?php
session_start();
require_once("config2.php");

// Проверка авторизации
if (!isset($_SESSION['id1']) || empty($_SESSION['id1'])) {
    header("Location: login.php");
    exit;
}

$student_id = (int)$_SESSION['id1'];
$msg = "";
$error = "";

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $surname    = trim($_POST['surname'] ?? '');
    $name       = trim($_POST['name'] ?? '');
    $patronymic = trim($_POST['patronymic'] ?? '');
    $gender     = $_POST['gender'] ?? 'male';
    $birth      = $_POST['birth'] ?? '';
    $grade      = (int)($_POST['grade'] ?? 0);
    $class      = trim($_POST['class'] ?? '');
    $address    = trim($_POST['address'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $photo      = trim($_POST['photo'] ?? '');

    if (empty($surname) || empty($name)) {
        $error = "Имя и Фамилия обязательны для заполнения!";
    } else {
        // Обновляем данные в таблице users
        $stmt = $conn->prepare("
            UPDATE users 
            SET surname = ?, name = ?, patronymic = ?, gender = ?, birth = ?, grade = ?, class = ?, address = ?, phone = ?, photo = ?
            WHERE id = ?
        ");
        
        // ИСПРАВЛЕНА СТРОКА НИЖЕ: 11 букв для 11 переменных
        $stmt->bind_param("sssssissssi", $surname, $name, $patronymic, $gender, $birth, $grade, $class, $address, $phone, $photo, $student_id);
        
        if ($stmt->execute()) {
            $msg = "Данные успешно обновлены!";
        } else {
            $error = "Ошибка при обновлении: " . $conn->error;
        }
        $stmt->close();
    }
}

// Получаем текущие данные ученика
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$res = $stmt->get_result();
$student = $res->fetch_assoc();
$stmt->close();
$conn->close();

require_once("header.php");
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование профиля — СКАН</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        :root {
            --bg: #f0f2f5;
            --card: #ffffff;
            --text: #0a0e1a;
            --text-sec: #5a6a7e;
            --border: #e4e8f0;
            --accent: #4361ee;
            --accent-grad: linear-gradient(135deg, #4361ee, #7b8ff7);
            --danger: #ef4444;
            --success: #10b981;
            --radius: 12px;
            --shadow: 0 8px 40px rgba(67, 97, 238, 0.08);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }

        /* Header */
        .page-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
        }
        .page-header .icon-wrap {
            width: 50px; height: 50px;
            background: var(--accent-grad);
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 24px;
            box-shadow: 0 4px 15px rgba(67,97,238,0.3);
        }
        .page-header h1 {
            font-size: 26px; font-weight: 800;
            letter-spacing: -0.02em;
        }
        .page-header h1 span {
            background: var(--accent-grad);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Card */
        .card {
            background: var(--card);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            padding: 32px;
            animation: fadeUp 0.5s ease;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Alerts */
        .alert {
            padding: 14px 20px;
            border-radius: 10px;
            margin-bottom: 24px;
            font-size: 14px;
            font-weight: 600;
            display: flex; align-items: center; gap: 10px;
        }
        .alert-success { background: #d1fae5; color: #065f46; }
        .alert-error { background: #fee2e2; color: #991b1b; }

        /* Grid */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }
        .form-group {
            display: flex; flex-direction: column; gap: 8px;
        }
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        .form-group label {
            font-size: 13px; font-weight: 600;
            color: var(--text-sec);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .form-group label .req {
            color: var(--danger);
            margin-left: 4px;
        }
        .form-group input, .form-group select {
            padding: 12px 16px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 14px;
            font-family: inherit;
            background: #f8f9fc;
            color: var(--text);
            outline: none;
            transition: all 0.3s;
        }
        .form-group input:focus, .form-group select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(67,97,238,0.1);
            background: #fff;
        }

        /* Section Title */
        .section-title {
            font-size: 16px; font-weight: 700;
            color: var(--text);
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--border);
            display: flex; align-items: center; gap: 8px;
        }
        .section-title i {
            color: var(--accent);
        }

        /* Buttons */
        .actions {
            display: flex; gap: 12px; margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid var(--border);
        }
        .btn {
            padding: 12px 28px;
            border-radius: 10px;
            font-size: 15px; font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-family: inherit;
            display: inline-flex; align-items: center; gap: 8px;
            text-decoration: none;
        }
        .btn-save {
            background: var(--accent-grad);
            color: #fff;
            border: none;
            box-shadow: 0 4px 15px rgba(67,97,238,0.3);
        }
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(67,97,238,0.4);
        }
        .btn-cancel {
            background: #fff;
            color: var(--text);
            border: 1px solid var(--border);
        }
        .btn-cancel:hover {
            background: #f8f9fc;
            border-color: #d2d2d7;
        }

        @media (max-width: 600px) {
            .form-grid { grid-template-columns: 1fr; }
            .card { padding: 20px; }
            .page-header h1 { font-size: 22px; }
        }
    </style>
</head>
<body>

<div class="container">
    
    <div class="page-header">
        <span class="icon-wrap"><i class="fas fa-user-edit"></i></span>
        <h1>Редактирование <span>профиля</span></h1>
    </div>

    <div class="card">
        
        <?php if ($msg): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= $msg ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            
            <!-- Основная информация -->
            <div class="section-title"><i class="fas fa-user"></i> Основная информация</div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Фамилия <span class="req">*</span></label>
                    <input type="text" name="surname" value="<?= htmlspecialchars($student['surname'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Имя <span class="req">*</span></label>
                    <input type="text" name="name" value="<?= htmlspecialchars($student['name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Отчество</label>
                    <input type="text" name="patronymic" value="<?= htmlspecialchars($student['patronymic'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Пол</label>
                    <select name="gender">
                        <option value="male" <?= ($student['gender'] ?? '') == 'male' ? 'selected' : '' ?>>Мужской</option>
                        <option value="female" <?= ($student['gender'] ?? '') == 'female' ? 'selected' : '' ?>>Женский</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Дата рождения</label>
                    <input type="date" name="birth" value="<?= htmlspecialchars($student['birth'] ?? '') ?>">
                </div>
            </div>

            <!-- Учебная информация -->
            <div class="section-title"><i class="fas fa-graduation-cap"></i> Учебная информация</div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Класс (Цифра)</label>
                    <input type="number" name="grade" value="<?= htmlspecialchars($student['grade'] ?? '') ?>" min="1" max="11">
                </div>
                <div class="form-group">
                    <label>Буква класса</label>
                    <input type="text" name="class" value="<?= htmlspecialchars($student['class'] ?? '') ?>" maxlength="2">
                </div>
            </div>

            <!-- Контакты и фото -->
            <div class="section-title"><i class="fas fa-address-book"></i> Дополнительно</div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Номер телефона</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($student['phone'] ?? '') ?>" placeholder="+992XXXXXXXXX">
                </div>
                <div class="form-group">
                    <label>Имя файла фото</label>
                    <input type="text" name="photo" value="<?= htmlspecialchars($student['photo'] ?? '') ?>" placeholder="student_1.jpg">
                </div>
                <div class="form-group full-width">
                    <label>Адрес проживания</label>
                    <input type="text" name="address" value="<?= htmlspecialchars($student['address'] ?? '') ?>" placeholder="г. Душанбе, ул. ...">
                </div>
            </div>

            <!-- Кнопки -->
            <div class="actions">
                <button type="submit" class="btn btn-save">
                    <i class="fas fa-save"></i> Сохранить изменения
                </button>
                <a href="profile.php" class="btn btn-cancel">
                    <i class="fas fa-arrow-left"></i> Отмена
                </a>
            </div>

        </form>

    </div>

</div>

</body>
</html>

<?php require_once("js.php"); ?>