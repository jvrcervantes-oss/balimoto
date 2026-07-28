<?php
// ── Bali Moto Adventures — Stripe Checkout Session ────────────────
// La clave secreta vive en /private/b2k-config.php, FUERA de public_html.
$config_path = __DIR__ . '/private/b2k-config.php';
if (!file_exists($config_path)) {
    http_response_code(500);
    exit('Server configuration error. Contact the site administrator.');
}
require_once $config_path;
// private/b2k-config.php define: STRIPE_SECRET, SUCCESS_URL, CANCEL_URL

// Si CANCEL_URL ya trae query propia, el separador no puede ser '?': quedaria
// ...?booking=cancel?error=xxx y el motivo del rechazo se pierde dentro del valor.
function cancel_to($code) {
    $sep = strpos(CANCEL_URL, '?') === false ? '?' : '&';
    header('Location: ' . CANCEL_URL . $sep . 'error=' . rawurlencode($code), true, 303);
    exit;
}

define('DEPOSIT_USD', 500);                          // depósito de RESERVA, por persona
define('DEPOSIT_USD_CENTS', DEPOSIT_USD * 100);
define('MAX_RIDERS', 12);

// ── Validar parámetros ─────────────────────────────────────────────
// `riders` es OBLIGATORIO y se rechaza si no cuadra, no se rellena ni se recorta:
// - sin el, un GET suelto a /checkout creaba una sesion de Stripe LIVE con 1 rider;
// - recortando a MAX_RIDERS en silencio, el cliente pagaria por 12 mientras reserva
//   para 15 y el operador recibiria la reserva entera (misma clase de fuga que el
//   `?days=` de Sumba). Un importe callado que no coincide con lo reservado es el bug.
$ridersRaw = (string)($_GET['riders'] ?? '');
if (!ctype_digit($ridersRaw)) cancel_to('riders');
$riders = (int) $ridersRaw;
if ($riders < 1 || $riders > MAX_RIDERS) cancel_to('riders');

$ref    = substr(preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['ref'] ?? 'B2K'), 0, 100);

$label = $riders . ' rider' . ($riders > 1 ? 's' : '') . ' × $' . number_format(DEPOSIT_USD) . ' deposit';

// ── Crear Checkout Session via Stripe API ──────────────────────────
$data = [
  'line_items[0][price_data][currency]'                  => 'usd',
  'line_items[0][price_data][unit_amount]'               => DEPOSIT_USD_CENTS,
  'line_items[0][price_data][product_data][name]'        => 'Bali Moto Adventures — Tour Deposit',
  'line_items[0][price_data][product_data][description]' => $label,
  'line_items[0][quantity]'                              => $riders,
  'mode'                                                 => 'payment',
  'success_url'                                          => SUCCESS_URL,
  'cancel_url'                                           => CANCEL_URL,
  'client_reference_id'                                  => $ref,
  'metadata[riders]'                                     => $riders,
  'metadata[total_usd]'                                  => $riders * DEPOSIT_USD,
];

$ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST           => true,
  CURLOPT_POSTFIELDS     => http_build_query($data),
  CURLOPT_HTTPHEADER     => [
    'Authorization: Bearer ' . STRIPE_SECRET,
    'Content-Type: application/x-www-form-urlencoded',
    'Stripe-Version: 2024-06-20',
  ],
]);

$body = curl_exec($ch);
$err  = curl_error($ch);
curl_close($ch);

if ($err) {
    header('Location: ' . CANCEL_URL . '?error=conexion', true, 303);
    exit;
}

$session = json_decode($body, true);

if (!empty($session['url'])) {
    header('Location: ' . $session['url'], true, 303);
    exit;
}

$code = $session['error']['code'] ?? 'unknown';
header('Location: ' . CANCEL_URL . '?error=' . rawurlencode($code), true, 303);
exit;
