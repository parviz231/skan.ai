<?php
// topic_view.php - Страница просмотра теории
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once("config2.php");

// Получаем ID темы из URL
$topic_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($topic_id === 0) {
    header("Location: practice.php");
    exit;
}

// Запрашиваем тему из базы
$stmt = $conn->prepare("
    SELECT t.*, s.name AS subject_name
    FROM topics t
    JOIN subjects s ON s.id = t.subject_id
    WHERE t.id = ? LIMIT 1
");
$stmt->bind_param("i", $topic_id);
$stmt->execute();
$result = $stmt->get_result();
$topic = $result->fetch_assoc();
$stmt->close();

if (!$topic) {
    die("Тема не найдена.");
}

// Проверим, есть ли у темы вопросы (чтобы спрятать кнопку, если их нет)
$stmt = $conn->prepare("
    SELECT COUNT(*) AS c
    FROM modules
    WHERE topic_id = ? AND module_type = 'test'
");
$stmt->bind_param("i", $topic_id);
$stmt->execute();
$countRow = $stmt->get_result()->fetch_assoc();
$stmt->close();
$hasQuestions = ((int)$countRow['c']) > 0;

// Подключаем шапку
require_once("practice_header.php");
?>

<!-- СТИЛИ ДЛЯ СТРАНИЦЫ ТЕОРИИ -->
<style>
    .lesson-wrapper {
        background-color: #f8fafc;
        min-height: calc(100vh - 180px);
        padding: 40px 20px;
        font-family: 'Inter', sans-serif;
    }

    .lesson-container {
        max-width: 800px;
        margin: 0 auto;
    }

    .breadcrumb {
        font-size: 14px;
        color: #64748b;
        margin-bottom: 20px;
    }

    .breadcrumb a {
        color: #0071e3;
        text-decoration: none;
        font-weight: 500;
    }

    .breadcrumb a:hover {
        text-decoration: underline;
    }

    .lesson-card {
        background: #ffffff;
        border-radius: 20px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        border: 1px solid #eef2f6;
        overflow: hidden;
    }

    .lesson-header {
        padding: 40px 40px 20px;
        border-bottom: 1px solid #f1f5f9;
    }

    .subject-tag {
        display: inline-block;
        background: #f3e8ff;
        color: #8b5cf6;
        padding: 6px 14px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: 16px;
    }

    .lesson-title {
        font-size: 32px;
        font-weight: 800;
        color: #1e293b;
        margin: 0;
        line-height: 1.3;
    }

    .lesson-content {
        padding: 40px;
        font-size: 16px;
        line-height: 1.8;
        color: #334155;
    }

    /* Стили для текста внутри конспекта */
    .lesson-content h3 {
        font-size: 20px;
        font-weight: 700;
        color: #0f172a;
        margin-top: 24px;
        margin-bottom: 12px;
    }

    .lesson-content p {
        margin-bottom: 16px;
    }

    .lesson-content ul {
        margin-bottom: 20px;
        padding-left: 20px;
    }

    .lesson-content li {
        margin-bottom: 8px;
    }

    .lesson-footer {
        padding: 20px 40px 40px;
        background: #f8fafc;
        border-top: 1px solid #f1f5f9;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .btn-test {
        background: #0071e3;
        color: #fff;
        text-decoration: none;
        padding: 14px 28px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 15px;
        transition: 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-test:hover {
        background: #0077ed;
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 113, 227, 0.2);
        color: #fff;
    }

    .btn-test-disabled {
        background: #cbd5e1;
        cursor: not-allowed;
        pointer-events: none;
    }

    .teacher-note {
        background: #fef2f2;
        color: #ef4444;
        padding: 12px 20px;
        border-radius: 12px;
        font-size: 14px;
        font-weight: 500;
        border: 1px solid #fecaca;
    }
</style>

<div class="lesson-wrapper">
    <div class="lesson-container">

        <!-- Хлебные крошки -->
        <div class="breadcrumb">
            <a href="practice.php"><i class="fas fa-arrow-left"></i> Назад к темам</a>
        </div>

        <!-- Карточка урока -->
        <div class="lesson-card">

            <div class="lesson-header">
                <div class="subject-tag"><?= htmlspecialchars($topic['subject_name']) ?></div>
                <h1 class="lesson-title"><?= htmlspecialchars($topic['title']) ?></h1>
            </div>

            <!-- ТЕКСТ ТЕОРИИ (выводится из базы) -->
            <div class="lesson-content">
                <?php if (!empty($topic['content'])): ?>
                    <?= $topic['content'] ?>
                <?php else: ?>
                    <p class="text-muted"><i>Теория для этой темы пока не добавлена. Пожалуйста, переходите к тесту или спросите у учителя.</i></p>
                <?php endif; ?>
            </div>

            <!-- Подвал с кнопкой -->
            <div class="lesson-footer">
                <?php if (isset($_SESSION['is_teacher']) && $_SESSION['is_teacher'] === true): ?>
                    <!-- Учитель видит заглушку -->
                    <div class="teacher-note">
                        <i class="fas fa-info-circle"></i> Вы в режиме учителя. Прохождение теста недоступно.
                    </div>
                    <a href="practice.php" style="color: #64748b; text-decoration: none; font-weight: 500;">Вернуться</a>
                <?php else: ?>
                    <!-- Ученик видит кнопку "Пройти тест" -->
                    <?php if ($hasQuestions): ?>
                        <a href="module.php?id=<?= (int)$topic['id'] ?>" class="btn-test">
                            Перейти к тесту <i class="fas fa-arrow-right"></i>
                        </a>
                    <?php else: ?>
                        <span class="btn-test btn-test-disabled">
                            Вопросы пока не добавлены
                        </span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

        </div>

    </div>
</div>

<?php
// require_once("practice_footer.php");
?>
</body>
</html>