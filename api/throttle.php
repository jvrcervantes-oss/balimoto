<?php
/**
 * Rate-limiter simple basado en ficheros, sin dependencias externas.
 * Las marcas de tiempo se guardan por clave en  private/ratelimit/  (gitignored, 403 web).
 * Uso:
 *   if (b2k_throttle_blocked("login_$ip", 10, 600)) { ... 429 ... }   // ¿bloqueado?
 *   b2k_throttle_register("login_$ip", 600);                          // registrar un intento
 */

function b2k_throttle_dir() {
    $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'ratelimit';
    if (!is_dir($dir)) @mkdir($dir, 0700, true);
    return $dir;
}

function b2k_throttle_file($key) {
    return b2k_throttle_dir() . DIRECTORY_SEPARATOR . preg_replace('/[^a-zA-Z0-9_.-]/', '_', $key) . '.json';
}

function b2k_throttle_read($key, $windowSecs) {
    $file = b2k_throttle_file($key);
    $hits = is_file($file) ? (json_decode((string)@file_get_contents($file), true) ?: []) : [];
    $cut  = time() - $windowSecs;
    return array_values(array_filter($hits, fn($t) => is_int($t) && $t > $cut));
}

/** Devuelve true si la clave ya alcanzó el máximo de intentos dentro de la ventana. */
function b2k_throttle_blocked($key, $maxHits, $windowSecs) {
    return count(b2k_throttle_read($key, $windowSecs)) >= $maxHits;
}

/** Registra un intento (marca de tiempo actual) y poda los que quedan fuera de ventana. */
function b2k_throttle_register($key, $windowSecs) {
    $hits   = b2k_throttle_read($key, $windowSecs);
    $hits[] = time();
    @file_put_contents(b2k_throttle_file($key), json_encode($hits), LOCK_EX);
}

/** Limpia el historial de una clave (p.ej. tras un login correcto). */
function b2k_throttle_clear($key) {
    $file = b2k_throttle_file($key);
    if (is_file($file)) @unlink($file);
}
