<?php
header("Content-Type: application/json");


ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';

// Получаем данные
$data = json_decode(file_get_contents('php://input'), true);


if (!isset($data['username'], $data['email'], $data['password'])) {
    echo json_encode([
        "success" => false,
        "error" => "Не хватает данных"
    ]);
    exit;
}

$username = trim($data['username']);
$email = filter_var(trim($data['email']), FILTER_VALIDATE_EMAIL);
$password = $data['password'];


if (empty($username)) {
    echo json_encode([
        "success" => false,
        "error" => "Имя пользователя не может быть пустым"
    ]);
    exit;
}

if (!$email) {
    echo json_encode([
        "success" => false,
        "error" => "Неверный формат email"
    ]);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode([
        "success" => false,
        "error" => "Пароль должен содержать минимум 6 символов"
    ]);
    exit;
}


$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
$stmt->execute([$email, $username]);

if ($stmt->fetch()) {
    echo json_encode([
        "success" => false,
        "error" => "Пользователь с таким email или логином уже существует"
    ]);
    exit;
}


$hash = password_hash($password, PASSWORD_DEFAULT);


$stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
if ($stmt->execute([$username, $email, $hash])) {
    echo json_encode([
        "success" => true,
        "message" => "Регистрация успешна"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "error" => "Ошибка при регистрации"
    ]);
}
?>