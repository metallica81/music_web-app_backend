<?php
$host = 'localhost';
$dbname = 'metallzh_test';
$username = 'metallzh_test';
$password = 'Zhopakota337!';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Ошибка подключения к БД: " . $e->getMessage()
    ]);
    exit;
}
?>