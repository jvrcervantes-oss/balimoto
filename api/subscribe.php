<?php
/**
 * subscribe.php — Lead magnet "7 Islands itinerary"
 * Recibe email → (1) guarda en CSV, (2) push al Google Sheet (webhook opcional),
 * (3) envía email con LINK de descarga vía SMTP. Devuelve JSON {ok:true}.
 *
 * Config y secretos en  private/itinerary-config.php  (fuera del repo).
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function out($ok, $extra = []) {
    echo json_encode(array_merge(['ok' => $ok], $extra));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    out(false, ['error' => 'Method not allowed']);
}

$cfgFile = dirname(__DIR__) . '/private/itinerary-config.php';
if (!file_exists($cfgFile)) {
    http_response_code(500);
    out(false, ['error' => 'Server not configured']);
}
$cfg = require $cfgFile;

// --- Entrada ---
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) $data = $_POST;

$honeypot = trim($data['company'] ?? '');     // campo trampa: los humanos lo dejan vacío
$email    = trim(strtolower($data['email'] ?? ''));
$consent  = !empty($data['consent']);

// Bot detectado → fingimos éxito sin hacer nada
if ($honeypot !== '') out(true, ['skipped' => true]);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    out(false, ['error' => 'Please enter a valid email address.']);
}
if (!$consent) {
    http_response_code(422);
    out(false, ['error' => 'Please accept the privacy policy.']);
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200);
$ts = date('c');

// --- (1) Guardar en CSV ---
$csv = $cfg['subscribers_csv'] ?? (dirname(__DIR__) . '/private/itinerary_subscribers.csv');
$row = [$ts, $email, $ip, $ua, '7Islands'];
$line = '"' . implode('","', array_map(fn($v) => str_replace('"', '""', $v), $row)) . "\"\n";
@file_put_contents($csv, $line, FILE_APPEND | LOCK_EX);

// --- (2) Google Sheet (webhook Apps Script, opcional) ---
$logFile = dirname(__DIR__) . '/private/webhook.log';
if (!empty($cfg['sheet_webhook'])) {
    $payload = json_encode(['email' => $email, 'date' => $ts, 'source' => '7Islands itinerary', 'ip' => $ip]);
    $ch = curl_init($cfg['sheet_webhook']);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true, // Apps Script redirige a googleusercontent
    ]);
    $whResp = curl_exec($ch);
    $whCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $whErr  = curl_error($ch);
    curl_close($ch);
    // Log de diagnóstico (en private/, fuera del web root)
    @file_put_contents($logFile, "$ts | $email | http=$whCode | curl_err=$whErr | resp=" . substr((string)$whResp, 0, 300) . "\n", FILE_APPEND | LOCK_EX);
} else {
    @file_put_contents($logFile, "$ts | $email | SKIPPED: sheet_webhook vacío en config\n", FILE_APPEND | LOCK_EX);
}

// --- (3) Email con link de descarga ---
$pdfUrl  = $cfg['pdf_url'] ?? 'https://balimotoadventures.com/pdf/7Islands.pdf';
$subject = 'Your 7 Islands itinerary is here 🏍️';
$html = email_html($pdfUrl, $cfg);
list($sent, $resp) = smtp_send($cfg, $email, $subject, $html);

// Aviso al dueño (opcional)
if (!empty($cfg['owner_notify'])) {
    smtp_send($cfg, $cfg['owner_notify'], 'New itinerary lead: ' . $email,
        '<p>New subscriber for the 7 Islands itinerary:</p><p><strong>' . htmlspecialchars($email) . '</strong><br>' . htmlspecialchars($ts) . '</p>');
}

// El email puede fallar (SMTP) pero el lead ya está guardado y la descarga en página funciona igual
out(true, ['emailed' => $sent]);


/* ============================ Helpers ============================ */

function email_html($pdfUrl, $cfg) {
    $site = 'https://balimotoadventures.com';
    return '<!DOCTYPE html><html><body style="margin:0;background:#faf6ef;font-family:Arial,Helvetica,sans-serif;color:#1c2b1e">
<div style="max-width:560px;margin:0 auto;padding:32px 24px">
  <h1 style="font-size:24px;color:#1c2b1e;margin:0 0 8px">Your 7 Islands itinerary 🏍️</h1>
  <p style="font-size:15px;line-height:1.6;color:#3d5c42">Thanks for your interest in the <strong>7 Islands Hopping</strong> tour. Here is the full 13-day itinerary — day by day, route, highlights and what is included.</p>
  <p style="margin:28px 0">
    <a href="' . htmlspecialchars($pdfUrl) . '" style="background:#e8490a;color:#fff;text-decoration:none;padding:14px 28px;border-radius:100px;font-weight:bold;font-size:15px;display:inline-block">Download the itinerary (PDF)</a>
  </p>
  <p style="font-size:13px;color:#3d5c42;line-height:1.6">Any questions? Just reply to this email or message us on WhatsApp — we are happy to help you plan the ride.</p>
  <p style="font-size:13px;color:#3d5c42;margin-top:24px">Cheers,<br>The Bali Moto Adventures team</p>
  <hr style="border:none;border-top:1px solid #e3ddd0;margin:24px 0">
  <p style="font-size:11px;color:#9a9a8e">You received this because you requested the 7 Islands itinerary at <a href="' . $site . '" style="color:#9a9a8e">balimotoadventures.com</a>.</p>
</div></body></html>';
}

/**
 * Cliente SMTP minimalista sobre SSL (puerto 465). Suficiente para email transaccional.
 * Devuelve [bool ok, string respuesta].
 */
function smtp_send($cfg, $to, $subject, $html) {
    $host   = $cfg['smtp_host'] ?? 'smtp.hostinger.com';
    $port   = (int)($cfg['smtp_port'] ?? 465);
    $secure = $cfg['smtp_secure'] ?? 'ssl';
    $user   = $cfg['smtp_user'] ?? '';
    $pass   = $cfg['smtp_pass'] ?? '';
    $fromE  = $cfg['from_email'] ?? $user;
    $fromN  = $cfg['from_name'] ?? 'Bali Moto Adventures';

    $remote = ($secure === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
    $fp = @stream_socket_client($remote, $errno, $errstr, 20);
    if (!$fp) return [false, "connect: $errstr"];
    stream_set_timeout($fp, 20);

    $read = function () use ($fp) {
        $d = '';
        while ($line = fgets($fp, 515)) {
            $d .= $line;
            if (strlen($line) >= 4 && $line[3] === ' ') break;
        }
        return $d;
    };
    $cmd = function ($c) use ($fp, $read) { fwrite($fp, $c . "\r\n"); return $read(); };

    $read();                               // saludo 220
    $cmd('EHLO balimotoadventures.com');
    $cmd('AUTH LOGIN');
    $cmd(base64_encode($user));
    $a = $cmd(base64_encode($pass));
    if (strpos($a, '235') === false) { fclose($fp); return [false, 'auth: ' . trim($a)]; }

    $cmd('MAIL FROM:<' . $fromE . '>');
    $r = $cmd('RCPT TO:<' . $to . '>');
    if (strpos($r, '250') === false && strpos($r, '251') === false) { fclose($fp); return [false, 'rcpt: ' . trim($r)]; }
    $cmd('DATA');

    $headers  = 'From: ' . mb_encode_mimeheader($fromN) . ' <' . $fromE . ">\r\n";
    $headers .= 'To: <' . $to . ">\r\n";
    $headers .= 'Subject: ' . mb_encode_mimeheader($subject) . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "Content-Transfer-Encoding: 8bit\r\n";
    $headers .= 'Date: ' . date('r') . "\r\n";

    $body = preg_replace('/^\./m', '..', $html);          // dot-stuffing
    fwrite($fp, $headers . "\r\n" . $body . "\r\n.\r\n");
    $final = $read();
    $cmd('QUIT');
    fclose($fp);
    return [strpos($final, '250') !== false, trim($final)];
}
