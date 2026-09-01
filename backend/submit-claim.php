<?php
/**
 * PMO SOLUTIONS - Endpoint: Registro del Libro de Reclamaciones Virtual
 * Ruta: backend/submit-claim.php
 * Conforme a la Ley N° 29571 y D.S. N° 011-2011-PCM (INDECOPI)
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
        Security::sendJson(false, 'Has alcanzado el límite de intentos permitidos. Por favor, espera unos minutos antes de volver a registrar una solicitud.', [], 429);
    }
}

// Obtener datos enviados
$input = Security::getRequestData();

// Validar campo Honeypot anti-spam
$honeypotField = $config['security']['honeypot_field'] ?? 'website_hp';
if (!Security::checkHoneypot($input, $honeypotField)) {
    // Simular éxito ante bots
    Security::sendJson(true, 'Hoja de reclamación procesada.', [
        'codigo_reclamacion' => 'REC-' . date('Y') . '-00000'
    ]);
}

// Validar campos obligatorios
$required = [
    'tipo_documento'      => 'Tipo de Documento',
    'numero_documento'    => 'Número de Documento',
    'nombre_completo'     => 'Nombres y Apellidos / Razón Social',
    'telefono'            => 'Teléfono / WhatsApp',
    'email'               => 'Correo Electrónico',
    'domicilio'           => 'Domicilio / Dirección',
    'tipo_servicio'       => 'Tipo de Contratación',
    'nombre_servicio'     => 'Nombre del Curso o Servicio',
    'tipo_registro'       => 'Tipo de Registro (Reclamo o Queja)',
    'detalle_reclamacion' => 'Detalle del Reclamo o Queja',
    'pedido_consumidor'   => 'Pedido Concreto del Consumidor'
];

$errors = Security::validateRequired($input, $required);

// Validar aceptación de la declaración jurada
if (empty($input['declaracion_jurada'])) {
    $errors['declaracion_jurada'] = 'Debe aceptar la declaración jurada y conformidad con los datos consignados.';
}

// Validar formato de email
$email = Security::cleanEmail($input['email'] ?? '');
if (!empty($input['email']) && !Security::validateEmail($email)) {
    $errors['email'] = 'El correo electrónico ingresado no tiene un formato válido.';
}

// Validar teléfono
$telefono = Security::cleanString($input['telefono'] ?? '', 30);
if (!empty($input['telefono']) && !Security::validatePhone($telefono)) {
    $errors['telefono'] = 'Por favor ingresa un número telefónico válido (mínimo 6 dígitos).';
}

if (!empty($errors)) {
    Security::sendJson(false, 'Por favor corrige los campos obligatorios antes de enviar la reclamación.', ['errors' => $errors], 400);
}

// Generar código único correlativo/institucional de reclamo
$codigoReclamacion = Security::generateClaimCode('REC');

// Sanitizar datos del reclamo
$cleanData = [
    'codigo_reclamacion'  => $codigoReclamacion,
    'tipo_documento'      => Security::cleanString($input['tipo_documento'] ?? 'DNI', 30),
    'numero_documento'    => Security::cleanString($input['numero_documento'] ?? '', 30),
    'nombre_completo'     => Security::cleanString($input['nombre_completo'] ?? '', 200),
    'telefono'            => $telefono,
    'email'               => $email,
    'domicilio'           => Security::cleanString($input['domicilio'] ?? '', 255),
    'tipo_servicio'       => Security::cleanString($input['tipo_servicio'] ?? 'Servicio de Capacitación / Curso', 100),
    'nombre_servicio'     => Security::cleanString($input['nombre_servicio'] ?? '', 255),
    'detalle_servicio'    => Security::cleanMultiline($input['detalle_servicio'] ?? '', 1000),
    'tipo_registro'       => in_array($input['tipo_registro'] ?? '', ['Reclamo', 'Queja'], true) ? $input['tipo_registro'] : 'Reclamo',
    'detalle_reclamacion' => Security::cleanMultiline($input['detalle_reclamacion'] ?? '', 4000),
    'pedido_consumidor'   => Security::cleanMultiline($input['pedido_consumidor'] ?? '', 2000),
    'declaracion_jurada'  => 1
];

$tipo = $cleanData['tipo_registro'];
$dbConfig = $config['database'] ?? [];
$dbEnabled = !empty($dbConfig['enabled']);
$mailer = new SmtpMailer($config['smtp'] ?? [], $config['app'] ?? []);

// =============================================================================
// CASO 1: Base de Datos MySQL Habilitada en config.php
// El reclamo se considera registrado formalmente SÓLO si se guarda en MySQL
// =============================================================================
if ($dbEnabled) {
    $pdo = Database::getConnection($dbConfig);

    if ($pdo === null) {
        $dbError = Database::getLastError();
        Security::sendJson(
            false,
            'No se pudo conectar a la base de datos MySQL para registrar la reclamación. Por favor verifique las credenciales del servidor o contacte al administrador.',
            ['db_error' => !empty($config['app']['debug']) ? $dbError : 'Error de conexión MySQL'],
            500
        );
    }

    $insertId = Database::saveClaim($pdo, $cleanData);

    if ($insertId === false) {
        $dbError = Database::getLastError();
        Security::sendJson(
            false,
            'Ocurrió un error al intentar registrar la reclamación en la base de datos MySQL. No se pudo completar el registro.',
            ['db_error' => !empty($config['app']['debug']) ? $dbError : 'Error en consulta SQL'],
            500
        );
    }

    // El reclamo quedó guardado de manera persistente en MySQL
    $adminEmailSent = $mailer->sendClaimAdminNotification($cleanData);
    $userEmailSent  = $mailer->sendClaimUserReceipt($cleanData);

    if ($userEmailSent) {
        $msg = "Su {$tipo} ha sido registrado formalmente en nuestro sistema con el código de seguimiento {$codigoReclamacion}. Se ha enviado una constancia detallada a su correo electrónico ({$cleanData['email']}). PMO Solutions responderá a su requerimiento en un plazo máximo de 15 días hábiles conforme a ley.";
    } else {
        $msg = "Su {$tipo} ha sido registrado formalmente en la base de datos con el código de seguimiento {$codigoReclamacion}. Sin embargo, no fue posible enviar la constancia por correo debido a una incidencia con el servidor de correo. Por favor guarde su código para el seguimiento respectivo.";
    }

    Security::sendJson(true, $msg, [
        'codigo_reclamacion' => $codigoReclamacion,
        'tipo_registro'      => $tipo,
        'nombre_completo'    => $cleanData['nombre_completo'],
        'email'              => $cleanData['email'],
        'db_saved'           => true,
        'email_sent'         => $userEmailSent,
        'fecha_registro'     => date('d/m/Y H:i:s')
    ]);
}

// =============================================================================
// CASO 2: Base de Datos MySQL Deshabilitada (Fase de Desarrollo / Sin Credenciales)
// No simulamos registro persistente; se intenta notificación por correo y se informa con precisión
// =============================================================================
$adminEmailSent = $mailer->sendClaimAdminNotification($cleanData);
$userEmailSent  = $mailer->sendClaimUserReceipt($cleanData);

if ($adminEmailSent || $userEmailSent) {
    $msg = "Su {$tipo} fue recibido y notificado vía correo electrónico con el código provisional {$codigoReclamacion}. (Aviso de configuración: La base de datos MySQL se encuentra actualmente deshabilitada en backend/config.php a la espera de credenciales reales).";

    Security::sendJson(true, $msg, [
        'codigo_reclamacion' => $codigoReclamacion,
        'tipo_registro'      => $tipo,
        'nombre_completo'    => $cleanData['nombre_completo'],
        'email'              => $cleanData['email'],
        'db_saved'           => false,
        'db_enabled'         => false,
        'email_sent'         => true,
        'fecha_registro'     => date('d/m/Y H:i:s')
    ]);
} else {
    // Si la BD está desactivada Y el correo falló, NO se simula ningún éxito
    $smtpError = $mailer->getLastError();
    $msg = "No se pudo registrar la reclamación: la base de datos MySQL está desactivada y el servidor de correo SMTP no pudo enviar la notificación. Para habilitar el registro definitivo, configure las credenciales en backend/config.php.";

    Security::sendJson(false, $msg, [
        'db_saved'   => false,
        'db_enabled' => false,
        'email_sent' => false,
        'smtp_error' => !empty($config['app']['debug']) ? $smtpError : null
    ], 503);
}
