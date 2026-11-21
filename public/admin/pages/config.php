<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$host = 'mysql-8.4';
$username = 'root';
$password = '';
$dbname = 'face_recognition';
try {
    $conn = new mysqli($host, $username, $password, $dbname);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    error_log('Admin database connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Не удалось подключиться к базе данных.');
}
?>