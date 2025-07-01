<?php
session_start();

// === CORS ===
$origin = 'http://localhost:3000'; // адрес фронта
header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, X-CSRF-Token");
header("Access-Control-Allow-Methods: POST, OPTIONS");

// === Preflight ===
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// === Контент JSON ===
header("Content-Type: application/json");

// === Получаем JSON тело ===
$data = json_decode(file_get_contents('php://input'), true);

// === Проверка наличия ключей ===
if (
    !isset($data['login']) ||
    !isset($data['password']) ||
    !isset($data['csrf_token'])
) {
    echo json_encode([
        "success" => false,
        "error" => "Не хватает данных"
    ]);
    exit;
}

// === Проверка CSRF ===
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

    if (!$user) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "error" => "Неверный логин или пароль"
        ]);
        exit;
    }

    if (!password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "error" => "Неверный логин или пароль"
        ]);
        exit;
    }

    unset($user['password_hash']);
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
