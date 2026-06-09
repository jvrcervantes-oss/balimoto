<?php
require_once 'config.php';
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode(['ok' => isset($_SESSION['b2k_auth'])]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];

    if (($body['action'] ?? '') === 'logout') {
        session_destroy();
        echo json_encode(['ok' => true]);
        exit;
    }

    $password = $body['password'] ?? '';
    if (password_verify($password, ADMIN_PASS_HASH)) {
        $_SESSION['b2k_auth'] = true;
        session_regenerate_id(true);
        echo json_encode(['ok' => true]);
    } else {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Invalid password']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
