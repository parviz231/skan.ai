<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование ученика</title>
    <link rel="stylesheet" href="css/bootstrap.css">
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            background: #f5f6fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            margin: 0;
            padding: 15px;
        }
        .container-fluid {
            max-width: 800px;
            margin: 0 auto;
            padding: 0;
        }
        
        .card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            padding: 25px 30px;
            margin-bottom: 20px;
        }
        .card-header {
            padding: 0 0 18px 0;
            border-bottom: 2px solid #e8ecf1;
            margin-bottom: 22px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .card-header h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
            color: #2c3e50;
        }
        .card-header .back-link {
            font-size: 14px;
            color: #7f8c8d;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .card-header .back-link:hover {
            color: #2c3e50;
        }
        
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #34495e;
            margin-bottom: 5px;
        }
        .form-group label .required {
            color: #e74c3c;
            margin-left: 2px;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #dce1e8;
            border-radius: 4px;
            font-size: 14px;
            background: #fff;
            transition: 0.15s;
            height: 40px;
        }
        .form-group input:focus,
        .form-group select:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52,152,219,0.12);
        }
        .form-group input[readonly] {
            background: #f8f9fa;
            cursor: not-allowed;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }
        
        .btn-group-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        .btn {
            font-size: 14px;
            padding: 8px 28px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            transition: 0.15s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            font-weight: 500;
            height: 40px;
            line-height: 24px;
        }
        .btn-primary {
            background: #3498db;
            color: #fff;
        }
        .btn-primary:hover {
            background: #2980b9;
            color: #fff;
        }
        .btn-secondary {
            background: #95a5a6;
            color: #fff;
        }
        .btn-secondary:hover {
            background: #7f8c8d;
            color: #fff;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 18px;
            font-size: 14px;
        }
        .alert-danger {
            background: #fde8e8;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .required-star {
            color: #e74c3c;
            font-size: 16px;
        }
        
        @media (max-width: 768px) {
            body {
                padding: 8px;
            }
            .card {
                padding: 18px 16px;
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
                font-size: 18px;
            }
            .btn-group-actions {
                flex-direction: column;
            }
            .btn-group-actions .btn {
                width: 100%;
                text-align: center;
            }
        }
        
        @media (max-width: 480px) {
            .card {
                padding: 14px 12px;
            }
            .card-header h2 {
                font-size: 16px;
            }
            .form-group label {
                font-size: 13px;
            }
            .form-group input,
            .form-group select {
                font-size: 13px;
                height: 36px;
                padding: 6px 10px;
            }
        }
    </style>
</head>
<body>

<div class="container-fluid">

<?php
require_once('config.php');

// Получаем данные ученика
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id < 1) {
    echo '<div class="card"><div class="alert alert-danger">Неверный ID ученика.</div></div>';
    exit;
}

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

if (!$student) {
    echo '<div class="card"><div class="alert alert-danger">Ученик не найден.</div></div>';
    exit;
}
?>

<div class="card">
    <div class="card-header">
        <h2>Редактирование ученика</h2>
        <a href="?page=3" class="back-link">← Назад к списку</a>
    </div>

    <?php if (isset($_POST['edit'])): ?>
        <?php
        // Обработка формы
        $name = trim($_POST['name'] ?? '');
        $surname = trim($_POST['surname'] ?? '');
        $patronymic = trim($_POST['patronymic'] ?? '');
        $birthday = $_POST['birthday'] ?? '';
        $gender = $_POST['gender'] ?? '';
        $grade = (int)($_POST['grade'] ?? 0);
        $class = trim($_POST['class'] ?? '');

        if ($name === '' || $surname === '' || $birthday === '' || $gender === '' || $grade < 1 || $class === '') {
            echo '<div class="alert alert-danger">⚠️ Заполните все обязательные поля.</div>';
        } else {
            try {
                $stmt = $conn->prepare(
                    'UPDATE users SET name = ?, surname = ?, patronymic = ?, birth = ?, gender = ?, grade = ?, class = ? WHERE id = ?'
                );
                $stmt->bind_param('sssssisi', $name, $surname, $patronymic, $birthday, $gender, $grade, $class, $id);
                $stmt->execute();
                $stmt->close();
                echo '<div class="alert alert-success">✅ Данные ученика успешно обновлены.</div>';
                // Обновляем данные для отображения
                $student['name'] = $name;
                $student['surname'] = $surname;
                $student['patronymic'] = $patronymic;
                $student['birth'] = $birthday;
                $student['gender'] = $gender;
                $student['grade'] = $grade;
                $student['class'] = $class;
            } catch (Throwable $error) {
                echo '<div class="alert alert-danger">❌ Не удалось обновить данные ученика.</div>';
            }
        }
        ?>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="edit" value="1">
        <input type="hidden" name="id" value="<?= $id ?>">

        <div class="form-row">
            <div class="form-group">
                <label>Фамилия <span class="required-star">*</span></label>
                <input type="text" name="surname" value="<?= htmlspecialchars($student['surname']) ?>" required>
            </div>
            <div class="form-group">
                <label>Имя <span class="required-star">*</span></label>
                <input type="text" name="name" value="<?= htmlspecialchars($student['name']) ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label>Отчество</label>
            <input type="text" name="patronymic" value="<?= htmlspecialchars($student['patronymic'] ?? '') ?>">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Дата рождения <span class="required-star">*</span></label>
                <input type="date" name="birthday" value="<?= htmlspecialchars($student['birth']) ?>" required>
            </div>
            <div class="form-group">
                <label>Пол <span class="required-star">*</span></label>
                <select name="gender" required>
                    <option value="">Выберите</option>
                    <option value="male" <?= $student['gender'] == 'male' ? 'selected' : '' ?>>Мужской</option>
                    <option value="female" <?= $student['gender'] == 'female' ? 'selected' : '' ?>>Женский</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Класс <span class="required-star">*</span></label>
                <select name="grade" required>
                    <option value="">Выберите</option>
                    <?php for ($i = 5; $i <= 11; $i++): ?>
                        <option value="<?= $i ?>" <?= $student['grade'] == $i ? 'selected' : '' ?>><?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Группа <span class="required-star">*</span></label>
                <select name="class" required>
                    <option value="">Выберите</option>
                    <?php foreach (['А', 'Б', 'В'] as $g): ?>
                        <option value="<?= $g ?>" <?= $student['class'] == $g ? 'selected' : '' ?>><?= $g ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="btn-group-actions">
            <button type="submit" class="btn btn-primary">Сохранить изменения</button>
            <a href="?page=3" class="btn btn-secondary">Отмена</a>
        </div>
    </form>
</div>

</div>

<script src="js/bootstrap.bundle.js"></script>
</body>
</html>