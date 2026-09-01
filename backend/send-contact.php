<?php
/**
 * PMO SOLUTIONS - Endpoint: Envío de Formulario de Contacto
 * Ruta: backend/send-contact.php
 */

define('PMO_APP_ACCESS', true);

// Cargar dependencias del backend
$config = require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Security.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/SmtpMailer.php';

// Inicializar cabeceras de seguridad y CORS
Security::initHeaders($config['security']['allowed_origins'] ?? []);

// Exigir método POST
Security::requireMethod('POST');

// Verificar límite de solicitudes por IP (Rate Limiting)
if (!empty($config['security']['rate_limit_enabled'])) {
    $maxReq = (int)($config['security']['rate_limit_requests'] ?? 10);
    $window = (int)($config['security']['rate_limit_window'] ?? 300);
    if (!Security::checkRateLimit($maxReq, $window)) {
        Security::sendJson(false, 'Has alcanzado el límite de intentos permitidos. Por favor, espera unos minutos antes de volver a intentar.', [], 429);
    }
}

// Obtener datos enviados
$input = Security::getRequestData();

// Validar campo Honeypot anti-spam
$honeypotField = $config['security']['honeypot_field'] ?? 'website_hp';
if (!Security::checkHoneypot($input, $honeypotField)) {
    // Simular éxito silencioso ante bots para no darles retroalimentación útil
    Security::sendJson(true, 'Mensaje recibido correctamente.');
}

// Validar campos obligatorios
$required = [
    'nombre'   => 'Nombre Completo',
    'telefono' => 'Teléfono / WhatsApp',
    'email'    => 'Correo Electrónico',
    'mensaje'  => 'Comentario o Consulta'
];

$errors = Security::validateRequired($input, $required);

// Validar formato de email
$email = Security::cleanEmail($input['email'] ?? '');
if (!empty($input['email']) && !Security::validateEmail($email)) {
    $errors['email'] = 'El correo electrónico ingresado no tiene un formato válido.';
}

// Validar formato de teléfono
$telefono = Security::cleanString($input['telefono'] ?? '', 30);
if (!empty($input['telefono']) && !Security::validatePhone($telefono)) {
    $errors['telefono'] = 'Por favor ingresa un número de teléfono válido (mínimo 6 dígitos).';
}

if (!empty($errors)) {
    Security::sendJson(false, 'Por favor corrige los campos marcados antes de continuar.', ['errors' => $errors], 400);
}

// Sanitizar datos recibidos
$cleanData = [
    'nombre'   => Security::cleanString($input['nombre'] ?? '', 150),
    'telefono' => $telefono,
    'email'    => $email,
    'servicio' => Security::cleanString($input['servicio'] ?? 'Capacitación Profesional', 150),
    'mensaje'  => Security::cleanMultiline($input['mensaje'] ?? '', 4000)
];

// 1. Guardar en Base de Datos MySQL (si está habilitada en config.php)
$dbSaved = false;
$dbConfig = $config['database'] ?? [];
if (!empty($dbConfig['enabled'])) {
    $pdo = Database::getConnection($dbConfig);
    if ($pdo !== null) {
        $insertId = Database::saveContact($pdo, $cleanData);
        $dbSaved = ($insertId !== false);
    }
}

// 2. Enviar correo de notificación mediante SMTP
$mailer = new SmtpMailer($config['smtp'] ?? [], $config['app'] ?? []);
$emailSent = $mailer->sendContactNotification($cleanData);

if ($emailSent || $dbSaved) {
    Security::sendJson(true, '¡Gracias por comunicarte con PMO Solutions! Tu mensaje ha sido recibido exitosamente. Un asesor se pondrá en contacto contigo a la brevedad.', [
        'email_sent' => $emailSent,
        'db_saved'   => $dbSaved
    ]);
} else {
    $errorDetail = $mailer->getLastError();
    $msg = 'No pudimos procesar tu mensaje en este momento debido a un problema técnico con el servidor de correo. Por favor, contáctanos directamente a comercial@pmo-solutions.com o por WhatsApp.';
    
    $extra = !empty($config['app']['debug']) ? ['debug_error' => $errorDetail] : [];
    Security::sendJson(false, $msg, $extra, 500);
}
