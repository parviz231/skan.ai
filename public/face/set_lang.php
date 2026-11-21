<?php
session_start();

$lang = $_POST['lang'] ?? 'ru';
$allowed_langs = ['ru', 'tj'];

if (in_array($lang, $allowed_langs)) {
    $_SESSION['lang'] = $lang;
    setcookie('lang', $lang, time() + 31536000, '/');
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}