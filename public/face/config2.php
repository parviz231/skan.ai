<?php
$host = 'mysql-8.4';
$username = 'root';
$password = '';
$dbname = 'face_recognition';
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) { 
    die('Ошибка подключения к БД: '. $conn->connect_error);
}
?>