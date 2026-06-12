<?php
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'config.php') {
    header('HTTP/1.0 403 Forbidden'); exit;
}

define('B2K_ADMIN', true);

// ── Sesión endurecida (debe fijarse ANTES de session_start en los endpoints) ──
// Secure: solo viaja por HTTPS · HttpOnly: no accesible desde JS · SameSite=Strict: anti-CSRF
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Strict',
]);

// ── Contraseña de admin ───────────────────────────────────────────────────────
// NO se siembra ningún default. Si no hay .passhash configurado, el panel no
// funciona (503) en vez de quedar abierto con una contraseña conocida.
// Alta inicial: generar el hash en el servidor y escribirlo en api/.passhash:
//   php -r "file_put_contents('.passhash', password_hash('TU_CLAVE', PASSWORD_BCRYPT));"
$hashFile = __DIR__ . DIRECTORY_SEPARATOR . '.passhash';
if (!file_exists($hashFile) || trim((string)@file_get_contents($hashFile)) === '') {
    http_response_code(503);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'Admin not configured']);
    exit;
}
define('ADMIN_PASS_HASH', trim(file_get_contents($hashFile)));

define('DATA_FILE',    dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data.json');
define('IMAGES_DIR',   dirname(__DIR__) . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR);
define('IMAGES_URL',   'images/');
define('MAX_UPLOAD_MB', 8);

// ── Anti-CSRF: exige que las peticiones que mutan estado vengan del propio sitio ──
function b2k_require_same_origin() {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $origin  = $_SERVER['HTTP_ORIGIN'] ?? '';
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $ok = false;
    foreach ([$origin, $referer] as $src) {
        if ($src === '') continue;
        $h = parse_url($src, PHP_URL_HOST);
        if ($h !== null && $host !== '' && strcasecmp($h, $host) === 0) { $ok = true; break; }
    }
    // Sin Origin ni Referer no podemos verificar el origen → rechazar.
    if (!$ok) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => 'Bad origin']);
        exit;
    }
}
