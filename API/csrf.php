<?php
session_start();

// Генерация токена
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(64));
}

// Установка куки
setcookie('PHPSESSID', session_id(), [
    'path' => '/',
    'domain' => 'localhost',
    'httponly' => true,
    'samesite' => 'Lax'
]);

// CORS
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

// Лог
file_put_contents(__DIR__ . '/debug.log', "---\n", FILE_APPEND);
file_put_contents(__DIR__ . '/debug.log', "PHPSESSID (csrf): " . session_id() . "\n", FILE_APPEND);
file_put_contents(__DIR__ . '/debug.log', "Сгенерированный CSRF: " . $_SESSION['csrf_token'] . "\n", FILE_APPEND);

// Ответ
echo json_encode([
    'success' => true,
    'csrf_token' => $_SESSION['csrf_token']
]);
?>