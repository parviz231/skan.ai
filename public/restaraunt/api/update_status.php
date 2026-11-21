<?php
require_once __DIR__ . '/db.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['table_id']) || !isset($input['status'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Неверные параметры: нужны table_id и status']);
    exit;
}

$tableId = (int)$input['table_id'];
$newStatus = strtoupper(trim($input['status']));
$guestsCount = isset($input['guests_count']) ? (int)$input['guests_count'] : 0;
$waitSeconds = isset($input['wait_seconds']) ? (int)$input['wait_seconds'] : 0;

$allowedStatuses = ['FREE', 'WAITING', 'SERVED', 'AWAY', 'DIRTY'];
if (!in_array($newStatus, $allowedStatuses)) {
    http_response_code(400);
    echo json_encode(['error' => 'Недопустимый статус: ' . $newStatus]);
    exit;
}

// Получаем текущее состояние стола
$stmt = $pdo->prepare("SELECT status, updated_at FROM restaurant_tables WHERE id = ?");
$stmt->execute([$tableId]);
$current = $stmt->fetch();

if (!$current) {
    http_response_code(404);
    echo json_encode(['error' => 'Столик не найден']);
    exit;
}

$oldStatus = $current['status'];
$durationSec = max(0, time() - strtotime($current['updated_at']));

// Обновляем текущее состояние стола
$updateStmt = $pdo->prepare("
    UPDATE restaurant_tables 
    SET status = ?, guests_count = ?, wait_seconds = ?, updated_at = CURRENT_TIMESTAMP 
    WHERE id = ?
");
$updateStmt->execute([$newStatus, $guestsCount, $waitSeconds, $tableId]);

// Если статус реально изменился — пишем в журнал событий (Event Sourcing)
if ($oldStatus !== $newStatus) {
    $eventStmt = $pdo->prepare("
        INSERT INTO table_events (table_id, old_status, new_status, guests_count, duration_prev_state_sec)
        VALUES (?, ?, ?, ?, ?)
    ");
    $eventStmt->execute([$tableId, $oldStatus, $newStatus, $guestsCount, $durationSec]);
}

echo json_encode([
    'success' => true,
    'table_id' => $tableId,
    'old_status' => $oldStatus,
    'new_status' => $newStatus
]);