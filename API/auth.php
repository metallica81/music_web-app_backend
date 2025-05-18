<?php
session_start();
header("Content-Type: application/json");

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['login'], $data['password'], $data['csrf_token'])) {
    echo json_encode([
        "success" => false,
        "error" => "Не хватает данных"
    ]);
    exit;
}

// Проверяем CSRF
if (!isset($_SESSION['csrf_token']) || $_SESSION['csrf_token'] !== $data['csrf_token']) {
    http_response_code(403);
    echo json_encode([
        "success" => false,
        "error" => "Неверный CSRF-токен"
    ]);
    exit;
}

$login = trim(strip_tags($data['login']));
$password = $data['password'];

require_once 'config.php';

try {
    // Ищем пользователя
    $stmt = $pdo->prepare("SELECT id, login, mail, password_hash FROM users WHERE login = ? OR mail = ?");
    $stmt->execute([$login, $login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "error" => "Неверный логин или пароль"
        ]);
        exit;
    }

    unset($user['password_hash']);
    
    // Убираем старый токен после использования
    unset($_SESSION['csrf_token']);

    echo json_encode([
        "success" => true,
        "message" => "Авторизация успешна",
        "user" => $user
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Ошибка сервера"
    ]);
}
?>