<?php
/**
 * PLANTILLA de configuración del lead magnet "7 Islands itinerary".
 *
 * PASOS:
 *   1. Copia este archivo a   ../private/itinerary-config.php   (en el servidor).
 *   2. Rellena 'smtp_pass' con la clave del correo (NUNCA la subas al repo).
 *   3. (Opcional) pega la URL del webhook de Google Apps Script en 'sheet_webhook'.
 *
 * La carpeta private/ está en .gitignore: las credenciales no salen del servidor.
 */

return [
    // --- SMTP Hostinger (puerto 465 SSL recomendado) ---
    'smtp_host'   => 'smtp.hostinger.com',
    'smtp_port'   => 465,
    'smtp_secure' => 'ssl',
    'smtp_user'   => 'ride@balimotoadventures.com',
    'smtp_pass'   => 'PON_AQUI_LA_CLAVE_DEL_CORREO',   // <-- rellenar en el servidor

    // --- Remitente ---
    'from_email'  => 'ride@balimotoadventures.com',
    'from_name'   => 'Bali Moto Adventures',

    // --- Aviso al dueño por cada lead (vacío = desactivado) ---
    'owner_notify' => '',   // p.ej. 'ride@balimotoadventures.com'

    // --- Descarga ---
    'pdf_url' => 'https://balimotoadventures.com/pdf/7Islands.pdf',

    // --- Google Sheet (webhook Apps Script, vacío = solo CSV) ---
    'sheet_webhook' => '',  // p.ej. 'https://script.google.com/macros/s/XXXX/exec'

    // --- Dónde se guardan los emails (CSV en private/) ---
    'subscribers_csv' => __DIR__ . '/itinerary_subscribers.csv',
];
