<?php
session_start();

// Генерация токена
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(64));
}

// Установка куки с параметрами, которые позволяют работу с localhost:3000
setcookie('PHPSESSID', session_id(), [
    'path' => '/',
    'domain' => 'test-music-app.ru',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'None'
]);

// CORS
header('Access-Control-Allow-Origin: https://localhost:3000');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

// Лог
file_put_contents(__DIR__ . '/debug.log', "---\n", FILE_APPEND);
file_put_contents(__DIR__ . '/debug.log', "PHPSESSID (csrf): " . session_id() . "\n", FILE_APPEND);
file_put_contents(__DIR__ . '/debug.log', "Сгенерированный CSRF: " . $_SESSION['csrf_token'] . "\n", FILE_APPEND);

// Ответ
echo json_encode(['csrf_token' => $_SESSION['csrf_token']]);
