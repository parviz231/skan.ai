<?php
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['teacher_csrf_token']) || !is_string($_SESSION['teacher_csrf_token'])) {
    $_SESSION['teacher_csrf_token'] = bin2hex(random_bytes(32));
}

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id < 1) {
    echo "<div class='alert alert-danger'>Не указан корректный ID учителя. <a href='?page=11'>Вернуться к списку</a></div>";
    return;
}

$stmt = $conn->prepare(
    'SELECT t.*, s.name AS subject_name FROM teachers t LEFT JOIN subjects s ON s.id = t.subject_id WHERE t.id = ?'
);
$stmt->bind_param('i', $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$row) {
    echo "<div class='alert alert-danger'>Учитель не найден. <a href='?page=11'>Вернуться к списку</a></div>";
    return;
}

$subjects = [];
$subjectResult = $conn->query('SELECT id, name FROM subjects ORDER BY name');
while ($subjectRow = $subjectResult->fetch_assoc()) {
    $subjects[(int)$subjectRow['id']] = $subjectRow['name'];
}
?>

<style>
        .teacher-form-container {
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
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --radius-sm: 6px;
            --radius-md: 8px;
            --radius-lg: 12px;
            --radius-xl: 16px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        .teacher-form-container {
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
            background: linear-gradient(135deg, #f59e0b, #d97706);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
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
        
        .teacher-info {
            background: linear-gradient(135deg, #f9fafb, #f3f4f6);
            border: 2px solid #e5e7eb;
            border-radius: var(--radius-lg);
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            animation: fadeInUp 0.5s ease 0.1s forwards;
            opacity: 0;
        }
        
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
        
        .teacher-info .avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }
        
        .teacher-info .info-text {
            flex: 1;
        }
        
        .teacher-info .info-text strong {
            color: var(--text-primary);
            font-size: 16px;
            font-weight: 700;
        }
        
        .teacher-info .info-text span {
            color: var(--text-secondary);
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 25px;
            opacity: 0;
            animation: fadeInUp 0.5s ease forwards;
        }
        
        .form-group:nth-child(1) { animation-delay: 0.2s; }
        .form-group:nth-child(2) { animation-delay: 0.3s; }
        .form-group:nth-child(3) { animation-delay: 0.4s; }
        
        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 8px;
            letter-spacing: 1px;
            text-transform: uppercase;
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
            .teacher-info {
                flex-direction: column;
                align-items: flex-start;
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
            .teacher-info .avatar {
                width: 40px;
                height: 40px;
                font-size: 18px;
            }
        }
    </style>

<div class="teacher-form-container">

<div class="card">
    <div class="card-header">
        <h2>
            <span class="icon-wrapper">
                <i class="fas fa-user-edit"></i>
            </span>
            Редактирование учителя
        </h2>
        <a href="?page=11" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Назад к списку
        </a>
    </div>

    <div class="teacher-info">
        <div class="avatar">
            <?= htmlspecialchars(mb_substr($row['name'], 0, 1), ENT_QUOTES, 'UTF-8') ?>
        </div>
        <div class="info-text">
            <strong><?= htmlspecialchars($row['surname'] . ' ' . $row['name'] . ' ' . ($row['patronymic'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong><br>
            <span>ID: <?= (int)$row['id'] ?> | Предмет: <?= htmlspecialchars($row['subject_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </div>

    <form action="?page=13" method="post">
        <input type="hidden" name="id" value="<?= $row['id'] ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['teacher_csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

        <div class="form-row">
            <div class="form-group">
                <label>Фамилия <span class="required">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" name="surname" value="<?= htmlspecialchars($row['surname'], ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label>Имя <span class="required">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" name="name" value="<?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label>Отчество</label>
            <div class="input-wrapper">
                <i class="fas fa-user input-icon"></i>
                <input type="text" name="patronymic" value="<?= htmlspecialchars($row['patronymic'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Возраст</label>
                <div class="input-wrapper">
                    <i class="fas fa-birthday-cake input-icon"></i>
                    <input type="number" name="age" value="<?= htmlspecialchars((string)($row['age'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" min="18" max="80" placeholder="Например: 35">
                </div>
            </div>
            <div class="form-group">
                <label>Дата рождения <span class="required">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-calendar input-icon"></i>
                    <input type="date" name="birthday" value="<?= htmlspecialchars($row['birth'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Пол <span class="required">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-venus-mars input-icon"></i>
                    <select name="gender" required>
                        <option value="male" <?= ($row['gender'] == 'male') ? 'selected' : '' ?>>Мужской</option>
                        <option value="female" <?= ($row['gender'] == 'female') ? 'selected' : '' ?>>Женский</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Предмет <span class="required">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-book input-icon"></i>
                    <select name="subject_id" required>
                        <option value="">Выберите предмет</option>
                        <?php foreach ($subjects as $subjectId => $subjectName): ?>
                            <option value="<?= $subjectId ?>" <?= (int)$row['subject_id'] === $subjectId ? 'selected' : '' ?>><?= htmlspecialchars($subjectName, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label>Категория специальности <span class="required">*</span></label>
            <div class="input-wrapper">
                <i class="fas fa-star input-icon"></i>
                <select name="category_of_specialty" required>
                    <?php
                    $categories = ['1' => 'Первая', '2' => 'Вторая', 'highest' => 'Высшая'];
                    foreach ($categories as $key => $label):
                    ?>
                        <option value="<?= $key ?>" <?= (string)$row['category_of_specialty'] === (string)$key ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="btn-group-actions">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save"></i>
                Сохранить изменения
            </button>
            <a href="?page=11" class="btn btn-secondary">
                <i class="fas fa-times"></i>
                Отмена
            </a>
        </div>
    </form>
</div>

</div>
