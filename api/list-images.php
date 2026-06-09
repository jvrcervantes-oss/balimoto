<?php
require_once 'config.php';
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store');

if (!($_SESSION['b2k_auth'] ?? false)) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

$files = [];
if (is_dir(IMAGES_DIR)) {
    foreach (glob(IMAGES_DIR . '*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}', GLOB_BRACE) as $path) {
        $files[] = [
            'name' => basename($path),
            'url'  => IMAGES_URL . basename($path),
            'size' => filesize($path)
        ];
    }
    usort($files, fn($a,$b) => $b['size'] <=> $a['size']);
}
echo json_encode(['ok' => true, 'files' => $files]);
