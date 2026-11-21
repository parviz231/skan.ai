<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$lang = $_SESSION['lang'] ?? 'ru';

require_once(__DIR__ . "/1.php");
require_once(__DIR__ . "/config2.php"); // Ваш файл подключения к БД

// Проверка авторизации ученика
$isLoggedIn = isset($_SESSION['id1']) && isset($_SESSION['username1']);
$userName   = $isLoggedIn ? $_SESSION['username1'] : null;
$userId     = $isLoggedIn ? $_SESSION['id1']       : null;

// Пути
$practiceBase = 'practice.php';
$arenaBase    = 'arena.php';
$labsBase     = 'labs.php';
$topicBase    = 'topic.php';
?>