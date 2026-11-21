<?php
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id = isset($_POST['id']) && is_scalar($_POST['id']) ? filter_var((string)$_POST['id'], FILTER_VALIDATE_INT) : false;
$token = $_POST['csrf_token'] ?? '';
$sessionToken = $_SESSION['teacher_csrf_token'] ?? '';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || $id === false || $id === null || $id < 1 ||
    !is_string($token) || !is_string($sessionToken) || $sessionToken === '' || !hash_equals($sessionToken, $token)) {
    echo '<div class="alert alert-danger" role="alert">Запрос на удаление недействителен. <a href="?page=11">Вернуться к списку</a></div>';
    return;
}

try {
    $scheduleStmt = $conn->prepare('SELECT COUNT(*) AS total FROM schedule WHERE teacher_id = ?');
    $scheduleStmt->bind_param('i', $id);
    $scheduleStmt->execute();
    $scheduleCount = (int)$scheduleStmt->get_result()->fetch_assoc()['total'];
    $scheduleStmt->close();

    if ($scheduleCount > 0) {
        echo '<div class="alert alert-warning" role="alert">Нельзя удалить учителя: он назначен в расписании. Сначала переназначьте уроки. <a href="?page=11">Вернуться к списку</a></div>';
        return;
    }

    $stmt = $conn->prepare('DELETE FROM teachers WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $deleted = $stmt->affected_rows > 0;
    $stmt->close();

    if (!$deleted) {
        echo '<div class="alert alert-warning" role="alert">Учитель не найден. <a href="?page=11">Вернуться к списку</a></div>';
        return;
    }

    echo '<script>window.location.replace("?page=11&deleted=1");</script>';
} catch (mysqli_sql_exception $error) {
    error_log('Teacher delete failed: ' . $error->getMessage());
    echo '<div class="alert alert-danger" role="alert">Не удалось удалить учителя. Проверьте расписание и повторите попытку. <a href="?page=11">Вернуться к списку</a></div>';
}
