<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . "/practice_data.php");

header('Content-Type: application/json');

if (!$isLoggedIn) {
    echo json_encode(['status' => 'error', 'message' => 'Не авторизован']);
    exit;
}

$moduleId = isset($_POST['module_id']) ? intval($_POST['module_id']) : 0;
$score    = isset($_POST['score'])     ? intval($_POST['score'])     : 0;

if ($moduleId > 0 && $userId > 0) {
    $stmt = $conn->prepare("
        INSERT INTO module_results (user_id, module_id, score) 
        VALUES (?, ?, ?) 
        ON DUPLICATE KEY UPDATE score = GREATEST(score, VALUES(score))
    ");
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
        exit;
    }
    $stmt->bind_param('iii', $userId, $moduleId, $score);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'score' => $score]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Ошибка записи в БД']);
    }
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Неверные данные']);
}
?>