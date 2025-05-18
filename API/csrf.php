<?php
session_start();

$csrf_token = bin2hex(random_bytes(50));
$_SESSION['csrf_token'] = $csrf_token;

header("Content-Type: application/json");
echo json_encode([
    "success" => true,
    "csrf_token" => $csrf_token
]);
?>