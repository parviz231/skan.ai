<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Папка database рядом с папкой api
$dbDir = __DIR__ . '/../database';
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0777, true);
}
$dbFile = $dbDir . '/vision_table.sqlite';

try {
    $pdo = new PDO("sqlite:" . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Автоматически создаем таблицы SQLite при первом запуске
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS restaurant_tables (
            id INTEGER PRIMARY KEY,
            name TEXT NOT NULL,
            seats INTEGER NOT NULL DEFAULT 4,
            pos_x REAL NOT NULL DEFAULT 0,
            pos_z REAL NOT NULL DEFAULT 0,
            status TEXT NOT NULL DEFAULT 'FREE',
            guests_count INTEGER NOT NULL DEFAULT 0,
            wait_seconds INTEGER NOT NULL DEFAULT 0,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS table_events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            table_id INTEGER NOT NULL,
            old_status TEXT NULL,
            new_status TEXT NOT NULL,
            guests_count INTEGER DEFAULT 0,
            duration_prev_state_sec INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (table_id) REFERENCES restaurant_tables(id) ON DELETE CASCADE
        );
    ");

    // Заполняем 8 начальных столиков, если база еще пустая
    $count = $pdo->query("SELECT COUNT(*) FROM restaurant_tables")->fetchColumn();
    if ($count == 0) {
        $pdo->exec("
            INSERT INTO restaurant_tables (id, name, seats, pos_x, pos_z, status) VALUES
            (1, 'Столик №1', 4, -6.0, -4.0, 'FREE'),
            (2, 'Столик №2', 4, -2.0, -4.0, 'FREE'),
            (3, 'Столик №3', 2,  2.0, -4.0, 'FREE'),
            (4, 'Столик №4', 4,  6.0, -4.0, 'FREE'),
            (5, 'Столик №5', 6, -6.0,  2.5, 'FREE'),
            (6, 'Столик №6', 4, -2.0,  2.5, 'FREE'),
            (7, 'Столик №7', 4,  2.0,  2.5, 'FREE'),
            (8, 'Столик №8', 2,  6.0,  2.5, 'FREE');
        ");
    }
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка SQLite: ' . $e->getMessage()]);
    exit;
}