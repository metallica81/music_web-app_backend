<?php
// Лог ошибок
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();

file_put_contents(__DIR__ . '/debug.log', "---\n", FILE_APPEND);
file_put_contents(__DIR__ . '/debug.log', "PHPSESSID (reg): " . session_id() . "\n", FILE_APPEND);
file_put_contents(__DIR__ . '/debug.log', "Заголовок X-CSRF-Token: " . ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? 'нет') . "\n", FILE_APPEND);
file_put_contents(__DIR__ . '/debug.log', "Сессия CSRF-токена: " . ($_SESSION['csrf_token'] ?? 'нет') . "\n", FILE_APPEND);

header('Access-Control-Allow-Origin: http://localhost:3000'); 
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$csrf_header = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
$csrf_session = $_SESSION['csrf_token'] ?? '';

if (!$csrf_header || $csrf_header !== $csrf_session) {
    echo json_encode([
        "success" => false,
        "error" => "Неверный CSRF-токен"
    ]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
file_put_contents(__DIR__ . '/debug.log', "JSON тело: " . file_get_contents('php://input') . "\n", FILE_APPEND);

$login = trim($data['login'] ?? '');
$mail = filter_var(trim($data['mail'] ?? ''), FILTER_VALIDATE_EMAIL);
$password = $data['password'] ?? '';

if (!$login || !$mail || strlen($password) < 8) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Все поля обязательны']);
    exit;
}

require_once '../API/config.php';

try {
    file_put_contents(__DIR__ . '/debug.log', "Проверяем занятость логина/почты...\n", FILE_APPEND);
    $stmt = $pdo->prepare("SELECT id FROM users WHERE login = ? OR mail = ?");
    $stmt->execute([$login, $mail]);

    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Логин или email заняты']);
        exit;
    }

    $hash = password_hash($password, PASSWORD_ARGON2ID);
    file_put_contents(__DIR__ . '/debug.log', "Хэш пароля создан\n", FILE_APPEND);

    $stmt = $pdo->prepare("INSERT INTO users (login, mail, password_hash) VALUES (?, ?, ?)");
    
    if ($stmt->execute([$login, $mail, $hash])) {
        unset($_SESSION['csrf_token']);

        echo json_encode([
            "success" => true,
            "message" => "Регистрация успешна!"
        ]);
    } else {
        throw new Exception("Ошибка добавления в БД");
    }
} catch (Exception $e) {
    http_response_code(500);
    file_put_contents(__DIR__ . '/debug.log', "Ошибка Exception: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode([
        "success" => false,
        "error" => "Ошибка сервера: " . $e->getMessage()
    ]);
}
