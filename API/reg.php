<?php
session_start(); 
header("Content-Type: application/json; charset=UTF-8");

// Отключаем вывод ошибок
ini_set('display_errors', 0);
error_reporting(E_ALL);
date_default_timezone_set('UTC');

// Проверяем, есть ли CSRF-токен
if (!isset($_SERVER['HTTP_X_CSRF_TOKEN']) || !isset($_SESSION['csrf_token']) || $_SESSION['csrf_token'] !== $_SERVER['HTTP_X_CSRF_TOKEN']) {
    http_response_code(403);
    echo json_encode([
        "success" => false,
        "error" => "Неверный CSRF-токен"
    ]);
    exit;
}

// Получаем данные
$data = json_decode(file_get_contents('php://input'), true);

// Проверяем поля
if (!isset($data['login'], $data['mail'], $data['password'])) {
    echo json_encode([
        "success" => false,
        "error" => "Не хватает данных"
    ]);
    exit;
}

$login = trim(strip_tags($data['login']));
$mail = filter_var(trim(strip_tags($data['mail'])), FILTER_VALIDATE_EMAIL);
$password = $data['password'];

// Валидация логина
if (empty($login) || strlen($login) < 3 || strlen($login) > 32 || !preg_match('/^[a-zA-Z0-9_\-]+$/', $login)) {
    echo json_encode([
        "success" => false,
        "error" => "Имя должно быть от 3 до 32 символов и содержать только буквы, цифры, _ или -"
    ]);
    exit;
}

// Валидация email
if (!$mail) {
    echo json_encode([
        "success" => false,
        "error" => "Неверный формат email"
    ]);
    exit;
}

// Валидация пароля
if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
    echo json_encode([
        "success" => false,
        "error" => "Пароль должен быть не менее 8 символов и содержать буквы и цифры"
    ]);
    exit;
}

// Подключение к базе
require_once 'config.php';

try {
    // Проверка на существование пользователя
    $stmt = $pdo->prepare("SELECT id FROM users WHERE mail = ? OR login = ?");
    $stmt->execute([$mail, $login]);

    if ($stmt->fetch()) {
        echo json_encode([
            "success" => false,
            "error" => "Пользователь с таким email или логином уже существует"
        ]);
        exit;
    }

    // Хэшируем пароль
    $hash = password_hash($password, PASSWORD_ARGON2ID);

    // Регистрация пользователя
    $stmt = $pdo->prepare("INSERT INTO users (login, mail, password_hash) VALUES (?, ?, ?)");
    if ($stmt->execute([$login, $mail, $hash])) {
        unset($_SESSION['csrf_token']); // Удаляем после использования
        echo json_encode([
            "success" => true,
            "message" => "Регистрация успешна",
            "redirect" => "/login.html"
        ]);
    } else {
        throw new Exception("Ошибка при добавлении в БД");
    }
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => "Произошла ошибка на сервере"
    ]);
}
?>