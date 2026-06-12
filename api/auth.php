<?php
require_once 'config.php';
require_once 'throttle.php';
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

    // Mutación de estado (crea sesión) → exigir mismo origen + limitar fuerza bruta.
    b2k_require_same_origin();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (b2k_throttle_blocked("login_$ip", 10, 600)) {
        http_response_code(429);
        echo json_encode(['ok' => false, 'error' => 'Too many attempts. Try again later.']);
        exit;
    }

    $password = $body['password'] ?? '';
    if (password_verify($password, ADMIN_PASS_HASH)) {
        b2k_throttle_clear("login_$ip");
        $_SESSION['b2k_auth'] = true;
        session_regenerate_id(true);
        echo json_encode(['ok' => true]);
    } else {
        b2k_throttle_register("login_$ip", 600);
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Invalid password']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
