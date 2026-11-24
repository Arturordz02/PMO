<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Security;
use App\Core\SmtpMailer;
use App\Models\CourseModel;

/**
 * PMO SOLUTIONS - Controlador de Inscripción y Pago Niubiz (PaymentController)
 *
 * Responsabilidad:
 * - Gestionar el checkout e información de pago manual vía Niubiz por clase para los 7 cursos autorizados.
 * - Tabla de precios fija e inmutable (Clase 1 a 8).
 * - Selección libre de clase sin cálculos de divisas en cliente ni desbloqueos secuenciales.
 * - Registro seguro del comprobante post-pago con CSRF, sanitización, rate limiting y prevención de datos bancarios.
 * - Notificación por correo a service@pmo-solutions.com con aviso explícito de verificación manual.
 */
class PaymentController extends Controller {

    /**
     * Catálogo exclusivo de los 7 cursos autorizados para el flujo Niubiz
     */
    public const ALLOWED_COURSES = [
        'nec4' => 'Contratos NEC4: ECC y Servicios Profesionales',
        'riesgos-tecnicos' => 'Gestión de Riesgos Técnicos en la Construcción con el Nuevo Estándar del PMI® 2024',
        'dab-jrd' => 'Juntas de Resolución de Disputas & DAB-JRD',
        'vdc-bim' => 'Virtual Design and Construction (VDC) & BIM',
        'primavera-p6' => 'Oracle Primavera P6 & Tableros Power BI',
        'direccion-calidad-enfoque-pmi' => 'Dirección de la Calidad con el Enfoque PMI® para la Construcción',
        'control-de-calidad-en-tuneleria' => 'Control de Calidad en Tunelería',
    ];

    /**
     * Mapeo de alias para redirección o consolidación transparente
     */
    public const SLUG_ALIASES = [
        'contrato-nec4' => 'nec4',
        'junta-resolucion-disputas-dab' => 'dab-jrd',
        'vdc' => 'vdc-bim',
        'control-proyectos-primavera-p6' => 'primavera-p6',
    ];

    /**
     * Tabla oficial de precios fijos por clase (Clase 1 a Clase 8)
     */
    public const CLASS_PRICES = [
        1 => ['pen' => 10, 'usd' => 3],
        2 => ['pen' => 15, 'usd' => 5],
        3 => ['pen' => 25, 'usd' => 8],
        4 => ['pen' => 35, 'usd' => 11],
        5 => ['pen' => 45, 'usd' => 14],
        6 => ['pen' => 50, 'usd' => 16],
        7 => ['pen' => 55, 'usd' => 17],
        8 => ['pen' => 65, 'usd' => 20],
    ];

    /**
     * Enlace oficial de Niubiz PagoLink
     */
    public const NIUBIZ_URL = 'https://pagolink.niubiz.com.pe/pagoseguro/PMOSOLUTIONS/2331259';

    /**
     * Destinatario oficial de notificaciones
     */
    public const NOTIFICATION_EMAIL = 'service@pmo-solutions.com';

    /**
     * Mock handler para pruebas automatizadas
     */
    public static ?\Closure $submitMock = null;

    /**
     * Verifica si un slug corresponde estrictamente a uno de los 7 cursos autorizados
     */
    public static function isAllowedCourse(string $slug): bool {
        return isset(self::ALLOWED_COURSES[trim($slug)]);
    }

    /**
     * Devuelve el slug canónico autorizado o null si no está permitido
     */
    public static function canonicalizeSlug(string $slug): ?string {
        $clean = strtolower(trim($slug));
        if (isset(self::ALLOWED_COURSES[$clean])) {
            return $clean;
        }
        if (isset(self::SLUG_ALIASES[$clean])) {
            $mapped = self::SLUG_ALIASES[$clean];
            if (isset(self::ALLOWED_COURSES[$mapped])) {
                return $mapped;
            }
        }
        return null;
    }

    /**
     * Obtiene los precios fijos de una clase
     */
    public static function getClassPrices(int $clase): ?array {
        return self::CLASS_PRICES[$clase] ?? null;
    }

    /**
     * Valida si un valor contiene una estructura real de tarjeta de crédito/débito (PCI-DSS)
     * evitando falsos positivos con DNIs (8 dígitos), teléfonos (9 dígitos), carné de extranjería,
     * pasaportes alfanuméricos, referencias Niubiz o correos con números.
     */
    /**
     * Valida si un valor contiene una estructura real de tarjeta de crédito/débito (PCI-DSS).
     * Requiere simultáneamente:
     * 1. Longitud bancaria válida (13 a 19 dígitos).
     * 2. Prefijo IIN/BIN reconocido según red emisora (Visa, Mastercard, Amex, Diners, Discover).
     * 3. Algoritmo de Luhn (módulo 10) estrictamente válido.
     *
     * Evita falsos positivos con DNIs (8 dígitos), teléfonos (9 dígitos), carné de extranjería,
     * pasaportes alfanuméricos, referencias Niubiz o correos con números.
     */
    public static function isCreditCardNumber(string $value): bool {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return false;
        }

        // Excluir código de comercio oficial de PMO Solutions en referencias
        if (str_contains($trimmed, '2331259')) {
            return false;
        }

        // Buscar secuencias candidatas de 13 a 19 dígitos (aisladas o separadas por espacios/guiones)
        if (preg_match_all('/\b(?:\d[ -]*?){13,19}\b/', $trimmed, $matches)) {
            foreach ($matches[0] as $candidate) {
                $digits = preg_replace('/\D/', '', $candidate);
                $len = strlen($digits);

                // 1. Longitud bancaria válida
                if ($len < 13 || $len > 19) {
                    continue;
                }

                // 2. Prefijo IIN/BIN reconocido según red emisora y longitud estándar
                $firstOne   = (int)substr($digits, 0, 1);
                $firstTwo   = (int)substr($digits, 0, 2);
                $firstThree = (int)substr($digits, 0, 3);
                $firstFour  = (int)substr($digits, 0, 4);
                $firstSix   = (int)substr($digits, 0, 6);

                $isValidBin = false;

                // Visa: empieza con 4 (13 o 16 dígitos)
                if ($firstOne === 4 && ($len === 13 || $len === 16)) {
                    $isValidBin = true;
                }
                // Mastercard: 51-55 o 2221-2720 (16 dígitos)
                elseif ((($firstTwo >= 51 && $firstTwo <= 55) || ($firstFour >= 2221 && $firstFour <= 2720)) && $len === 16) {
                    $isValidBin = true;
                }
                // American Express: 34 o 37 (15 dígitos)
                elseif (($firstTwo === 34 || $firstTwo === 37) && $len === 15) {
                    $isValidBin = true;
                }
                // Diners Club: 36, 38 o 300-305 (14 dígitos)
                elseif (($firstTwo === 36 || $firstTwo === 38 || ($firstThree >= 300 && $firstThree <= 305)) && $len === 14) {
                    $isValidBin = true;
                }
                // Discover: 6011, 622126-622925, 644-649, 65 (16 dígitos)
                elseif (($firstFour === 6011 || ($firstSix >= 622126 && $firstSix <= 622925) || ($firstThree >= 644 && $firstThree <= 649) || $firstTwo === 65) && $len === 16) {
                    $isValidBin = true;
                }

                if (!$isValidBin) {
                    continue;
                }

                // 3. Algoritmo de Luhn (módulo 10) estrictamente requerido
                $sum = 0;
                $alt = false;
                for ($i = $len - 1; $i >= 0; $i--) {
                    $n = (int)$digits[$i];
                    if ($alt) {
                        $n *= 2;
                        if ($n > 9) {
                            $n -= 9;
                        }
                    }
                    $sum += $n;
                    $alt = !$alt;
                }

                if ($sum % 10 === 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Muestra la pantalla de selección de clase y pago Niubiz
     * GET /pagar-curso?curso={slug}&clase={1..8}
     */
    public function showCheckout(): void {
        $rawSlug = (string)($_GET['curso'] ?? '');
        $canonicalSlug = self::canonicalizeSlug($rawSlug);

        if ($canonicalSlug === null) {
            $this->redirect('/capacitaciones');
            return;
        }

        $courseModel = new CourseModel();
        $course = $courseModel->getBySlug($canonicalSlug);

        if (!$course) {
            $this->redirect('/capacitaciones');
            return;
        }

        // Validación estricta de la clase: solo enteros del 1 al 8, default Clase 1
        $rawClass = $_GET['clase'] ?? 1;
        $validatedClass = filter_var($rawClass, FILTER_VALIDATE_INT);
        if ($validatedClass === false || $validatedClass < 1 || $validatedClass > 8) {
            $selectedClass = 1;
        } else {
            $selectedClass = $validatedClass;
        }

        // Resolver montos siempre desde la tabla inmutable CLASS_PRICES (ignorar cualquier monto en query)
        $currentPrice = self::CLASS_PRICES[$selectedClass];

        $this->render('pagar-curso', [
            'pageTitle'       => 'Inscripción y Pago por Clase | ' . ($course['title'] ?? 'Capacitación'),
            'metaDescription' => 'Inscripción y pago por clase para ' . ($course['title'] ?? '') . ' vía Niubiz. PMO Solutions.',
            'activeNav'       => 'capacitaciones',
            'course'          => $course,
            'courseSlug'      => $canonicalSlug,
            'selectedClass'   => $selectedClass,
            'initialClass'    => $selectedClass,
            'currentPrice'    => $currentPrice,
            'classPrices'     => self::CLASS_PRICES,
            'niubizUrl'       => self::NIUBIZ_URL,
            'allowedCourses'  => self::ALLOWED_COURSES,
        ]);
    }

    /**
     * Muestra el formulario de registro post-pago
     * GET /registro-pago?curso={slug}&clase={clase}
     */
    public function showRegister(): void {
        $rawSlug = (string)($_GET['curso'] ?? '');
        $canonicalSlug = self::canonicalizeSlug($rawSlug);

        if ($canonicalSlug === null) {
            $this->redirect('/capacitaciones');
            return;
        }

        $clase = (int)($_GET['clase'] ?? 1);
        if ($clase < 1 || $clase > 8) {
            $clase = 1;
        }

        $courseModel = new CourseModel();
        $course = $courseModel->getBySlug($canonicalSlug);

        if (!$course) {
            $this->redirect('/capacitaciones');
            return;
        }

        $prices = self::CLASS_PRICES[$clase];

        $this->render('registro-pago', [
            'pageTitle'       => 'Registro de Inscripción y Pago | ' . ($course['title'] ?? 'Capacitación'),
            'metaDescription' => 'Completa tu registro tras realizar el pago de la Clase ' . $clase . ' vía Niubiz. PMO Solutions.',
            'activeNav'       => 'capacitaciones',
            'course'          => $course,
            'courseSlug'      => $canonicalSlug,
            'selectedClass'   => $clase,
            'classPrices'     => self::CLASS_PRICES,
            'currentPrices'   => $prices,
            'niubizUrl'       => self::NIUBIZ_URL,
        ]);
    }

    /**
     * Procesa el formulario de registro de pago (POST)
     * POST /registro-pago/submit
     */
    public function submit(): void {
        Security::initHeaders($this->config['security']['allowed_origins'] ?? []);
        Security::requireMethod('POST');

        // 1. Rate Limiting por IP
        $securityConfig = $this->config['security'] ?? [];
        if (!empty($securityConfig['rate_limit_enabled'])) {
            if (!Security::checkRateLimit('payment_register')) {
                $this->respondWithError(
                    'Has superado el límite de intentos permitidos. Por favor espera unos minutos o comunícate por WhatsApp.',
                    429
                );
                return;
            }
        }

        // 2. Obtener datos de la solicitud
        $input = Security::getRequestData(65536, 5);

        if (Security::hasPayloadTooLarge()) {
            $this->respondWithError('El cuerpo de la solicitud excede el tamaño máximo permitido.', 413);
            return;
        }

        // 3. Validación obligatoria de CSRF
        Csrf::requireValidRequest($input, $this->config['security']['allowed_origins'] ?? []);

        // 4. Honeypot anti-spam
        if (!empty($input['_pmo_verification_hp'])) {
            // Silenciosamente aceptar o responder éxito falso para engañar al bot
            $this->respondWithSuccess('Registro recibido correctamente.', 'nec4', 1);
            return;
        }

        // 5. Validar curso autorizado
        $rawCourse = (string)($input['curso'] ?? '');
        $canonicalSlug = self::canonicalizeSlug($rawCourse);
        if ($canonicalSlug === null) {
            $this->respondWithError('El curso seleccionado no está autorizado para este flujo de inscripción.', 422, [
                'curso' => 'Curso no autorizado o inválido.'
            ]);
            return;
        }

        // 6. Validar clase (1 a 8)
        $rawClass = $input['clase'] ?? null;
        $clase = filter_var($rawClass, FILTER_VALIDATE_INT);
        if ($clase === false || $clase < 1 || $clase > 8) {
            $this->respondWithError('Debe seleccionar una clase válida entre 1 y 8.', 422, [
                'clase' => 'Número de clase inválido. Las opciones válidas son de 1 a 8.'
            ]);
            return;
        }

        // 7. Determinación de precios estrictamente en el backend (ignorar montos del cliente)
        $officialPrices = self::CLASS_PRICES[$clase];
        $pen = $officialPrices['pen'];
        $usd = $officialPrices['usd'];

        // 8. Sanitización y validación de campos del usuario
        $rawNombres    = trim((string)($input['nombres'] ?? ''));
        $rawEmail      = trim((string)($input['email'] ?? ''));
        $rawTelefono   = trim((string)($input['telefono'] ?? ''));
        $rawDocumento  = trim((string)($input['documento'] ?? ''));
        $rawReferencia = trim((string)($input['referencia_niubiz'] ?? ''));
        $rawPolitica   = $input['politica_privacidad'] ?? null;

        $errors = [];

        // Nombres
        $nombres = Security::cleanString($rawNombres, 150);
        if (mb_strlen($nombres, 'UTF-8') < 3) {
            $errors['nombres'] = 'Ingrese sus nombres y apellidos completos (mínimo 3 caracteres).';
        }

        // Email
        $email = Security::cleanEmail($rawEmail);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Ingrese un correo electrónico válido.';
        }

        // Teléfono / WhatsApp
        $telefono = Security::cleanString($rawTelefono, 30);
        $cleanPhoneDigits = preg_replace('/\D/', '', $telefono);
        if (strlen($cleanPhoneDigits) < 6) {
            $errors['telefono'] = 'Ingrese un número telefónico o WhatsApp válido.';
        }

        // Documento de Identidad (DNI, CE, Pasaporte)
        $documento = Security::cleanString($rawDocumento, 30);
        if (mb_strlen($documento, 'UTF-8') < 4) {
            $errors['documento'] = 'Ingrese un número de documento de identidad válido (DNI, CE o Pasaporte).';
        }

        // Referencia Niubiz
        $referencia = Security::cleanString($rawReferencia, 100);
        if (mb_strlen($referencia, 'UTF-8') < 4) {
            $errors['referencia_niubiz'] = 'Ingrese el número de operación o referencia generado por Niubiz.';
        }

        // Verificación estricta PCI-DSS: Bloquear números con estructura real de tarjeta bancaria
        // Comprobar cada campo individualmente para evitar falsos positivos con DNI (8 dígitos) y teléfono (9 dígitos)
        foreach ([$nombres, $email, $telefono, $documento, $referencia] as $fieldValue) {
            if (self::isCreditCardNumber($fieldValue)) {
                $errors['seguridad'] = 'Por tu seguridad y normativa de protección de datos, este formulario NO solicita ni permite números de tarjeta de crédito/débito. Ingrese únicamente el número de operación o referencia proporcionado por Niubiz.';
                break;
            }
        }

        // Política de Privacidad
        if (empty($rawPolitica) || !in_array($rawPolitica, ['1', 'true', 'on', true, 1], true)) {
            $errors['politica_privacidad'] = 'Debe aceptar la Política de Privacidad para continuar.';
        }

        if (!empty($errors)) {
            $this->respondWithError('Por favor verifica los campos obligatorios del formulario.', 422, $errors);
            return;
        }

        // 9. Cargar datos del curso
        $courseModel = new CourseModel();
        $course = $courseModel->getBySlug($canonicalSlug);
        $courseTitle = $course['title'] ?? (self::ALLOWED_COURSES[$canonicalSlug] ?? 'Capacitación');

        // 10. Construir y enviar notificación por correo a service@pmo-solutions.com
        $clientIp = Security::getClientIp();
        $notificationData = [
            'course_title' => $courseTitle,
            'course_slug'  => $canonicalSlug,
            'clase'        => $clase,
            'monto_pen'    => $pen,
            'monto_usd'    => $usd,
            'nombres'      => $nombres,
            'email'        => $email,
            'telefono'     => $telefono,
            'documento'    => $documento,
            'referencia'   => $referencia,
            'ip'           => $clientIp,
            'fecha_hora'   => date('d/m/Y H:i:s') . ' (PET)',
        ];

        $subject = "[PMO Solutions - Pago Niubiz] Nueva inscripción: {$courseTitle} (Clase {$clase}) - {$nombres}";
        $htmlBody = self::buildNotificationHtml($notificationData);
        $textBody = self::buildNotificationText($notificationData);

        if (self::$submitMock !== null) {
            (self::$submitMock)($notificationData);
        } else {
            try {
                $mailer = new SmtpMailer();
                $mailer->send(
                    self::NOTIFICATION_EMAIL,
                    'PMO Solutions - Coordinación Académica',
                    $subject,
                    $htmlBody,
                    $textBody,
                    $email,
                    $nombres
                );
            } catch (\Throwable $e) {
                error_log("[PaymentController Notification Error] " . $e->getMessage());
            }
        }

        // 11. Responder éxito
        $successMessage = "¡Registro recibido con éxito! Hemos recibido tus datos para la Clase {$clase} de {$courseTitle}. El equipo de PMO Solutions verificará manualmente el pago en Niubiz y se comunicará contigo vía WhatsApp y correo electrónico para entregarte tus accesos.";

        $this->respondWithSuccess($successMessage, $canonicalSlug, $clase);
    }

    /**
     * Pantalla de confirmación de registro
     * GET /pago-exitoso?curso={slug}&clase={clase}
     */
    public function success(): void {
        $rawSlug = (string)($_GET['curso'] ?? '');
        $canonicalSlug = self::canonicalizeSlug($rawSlug);
        $clase = (int)($_GET['clase'] ?? 1);
        if ($clase < 1 || $clase > 8) {
            $clase = 1;
        }

        $courseModel = new CourseModel();
        $course = $canonicalSlug ? $courseModel->getBySlug($canonicalSlug) : null;
        $courseTitle = $course['title'] ?? (self::ALLOWED_COURSES[$canonicalSlug] ?? 'Curso');
        $prices = self::CLASS_PRICES[$clase] ?? self::CLASS_PRICES[1];

        $this->render('pago-exitoso', [
            'pageTitle'       => 'Registro Exitoso | PMO Solutions',
            'metaDescription' => 'Confirmación de recepción de registro de pago por clase. PMO Solutions.',
            'activeNav'       => 'capacitaciones',
            'courseTitle'     => $courseTitle,
            'courseSlug'      => $canonicalSlug ?? 'capacitaciones',
            'clase'           => $clase,
            'prices'          => $prices,
        ]);
    }

    /**
     * Construye el cuerpo HTML del correo de notificación
     */
    public static function buildNotificationHtml(array $d): string {
        $courseTitleSafe = htmlspecialchars((string)($d['course_title'] ?? ''), ENT_QUOTES, 'UTF-8');
        $claseSafe       = (int)($d['clase'] ?? 1);
        $penSafe         = (int)($d['monto_pen'] ?? 10);
        $usdSafe         = (int)($d['monto_usd'] ?? 3);
        $nombresSafe     = htmlspecialchars((string)($d['nombres'] ?? ''), ENT_QUOTES, 'UTF-8');
        $emailSafe       = htmlspecialchars((string)($d['email'] ?? ''), ENT_QUOTES, 'UTF-8');
        $telefonoSafe    = htmlspecialchars((string)($d['telefono'] ?? ''), ENT_QUOTES, 'UTF-8');
        $documentoSafe   = htmlspecialchars((string)($d['documento'] ?? ''), ENT_QUOTES, 'UTF-8');
        $referenciaSafe  = htmlspecialchars((string)($d['referencia'] ?? ''), ENT_QUOTES, 'UTF-8');
        $ipSafe          = htmlspecialchars((string)($d['ip'] ?? ''), ENT_QUOTES, 'UTF-8');
        $fechaHoraSafe   = htmlspecialchars((string)($d['fecha_hora'] ?? ''), ENT_QUOTES, 'UTF-8');
        $phoneDigits     = preg_replace('/\D/', '', $telefonoSafe);

        return "
<!DOCTYPE html>
<html lang='es'>
<head>
  <meta charset='UTF-8'>
  <style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f1f5f9; margin: 0; padding: 20px; color: #1e293b; }
    .container { max-width: 650px; background: #ffffff; margin: 0 auto; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
    .header { background: linear-gradient(135deg, #0A192F 0%, #1E3A8A 100%); color: #ffffff; padding: 28px 30px; text-align: center; }
    .header h1 { margin: 0; font-size: 20px; font-weight: 700; }
    .badge { background: #FFC107; color: #000000; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 800; display: inline-block; margin-top: 10px; }
    .notice-box { background: #fffbeb; border: 1px solid #fef3c7; color: #92400e; padding: 12px 16px; border-radius: 8px; margin: 20px 30px 0; font-size: 13px; font-weight: 600; }
    .content { padding: 25px 30px; }
    .field-row { border-bottom: 1px solid #e2e8f0; padding: 10px 0; }
    .field-label { font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; font-weight: 600; margin-bottom: 3px; }
    .field-value { font-size: 15px; font-weight: 600; color: #0f172a; }
    .footer { background: #0f172a; color: #94a3b8; text-align: center; padding: 18px; font-size: 12px; }
    .btn-action { display: inline-block; background: #25D366; color: #ffffff; padding: 10px 22px; border-radius: 6px; text-decoration: none; font-weight: 700; margin-top: 15px; }
  </style>
</head>
<body>
  <div class='container'>
    <div class='header'>
      <h1>NOTIFICACIÓN DE PAGO NIUBIZ POR CLASE</h1>
      <div class='badge'>CLASE {$claseSafe} | S/ {$penSafe} — US$ {$usdSafe}</div>
    </div>
    <div class='notice-box'>
      ⚠️ AVISO OPERATIVO: El pago quedará pendiente de verificación manual. Validar la referencia en la consola Niubiz antes de activar los accesos.
    </div>
    <div class='content'>
      <div class='field-row'>
        <div class='field-label'>Capacitación / Curso</div>
        <div class='field-value' style='color: #00509E;'>{$courseTitleSafe}</div>
      </div>
      <div class='field-row'>
        <div class='field-label'>Sesión Elegida</div>
        <div class='field-value'>Clase {$claseSafe} (Tarifa autorizada: S/ {$penSafe} — US$ {$usdSafe})</div>
      </div>
      <div class='field-row'>
        <div class='field-label'>Número de Operación / Referencia Niubiz</div>
        <div class='field-value' style='font-family: monospace; font-size: 16px; color: #b91c1c;'>{$referenciaSafe}</div>
      </div>
      <div class='field-row'>
        <div class='field-label'>Nombres y Apellidos del Alumno</div>
        <div class='field-value'>{$nombresSafe}</div>
      </div>
      <div class='field-row'>
        <div class='field-label'>Documento de Identidad</div>
        <div class='field-value'>{$documentoSafe}</div>
      </div>
      <div class='field-row'>
        <div class='field-label'>Correo Electrónico</div>
        <div class='field-value'><a href='mailto:{$emailSafe}' style='color: #00509E;'>{$emailSafe}</a></div>
      </div>
      <div class='field-row'>
        <div class='field-label'>Teléfono / WhatsApp</div>
        <div class='field-value'><a href='https://wa.me/{$phoneDigits}' style='color: #00509E;'>{$telefonoSafe}</a></div>
      </div>
      <div class='field-row'>
        <div class='field-label'>Dirección IP y Registro</div>
        <div class='field-value' style='font-size: 13px; color: #64748b;'>IP: {$ipSafe} | Fecha: {$fechaHoraSafe}</div>
      </div>
      <div style='text-align: center; margin-top: 20px;'>
        <a href='https://wa.me/{$phoneDigits}?text=Hola%20{$nombresSafe},%20te%20saludamos%20de%20PMO%20Solutions%20respecto%20a%20tu%20inscripci%C3%B3n%20en%20la%20Clase%20{$claseSafe}' class='btn-action' target='_blank'>
          Contactar al Alumno por WhatsApp
        </a>
      </div>
    </div>
    <div class='footer'>
      <p style='margin: 0;'>PMO Solutions — Sistema de Pagos e Inscripciones Niubiz.</p>
    </div>
  </div>
</body>
</html>";
    }

    /**
     * Construye el cuerpo de texto plano de la notificación
     */
    public static function buildNotificationText(array $d): string {
        return "NOTIFICACIÓN DE PAGO NIUBIZ POR CLASE\n"
             . "====================================\n"
             . "Curso: " . ($d['course_title'] ?? '') . "\n"
             . "Clase: Clase " . ($d['clase'] ?? 1) . "\n"
             . "Monto: S/ " . ($d['monto_pen'] ?? 10) . " — US$ " . ($d['monto_usd'] ?? 3) . "\n"
             . "Referencia Niubiz: " . ($d['referencia'] ?? '') . "\n"
             . "Alumno: " . ($d['nombres'] ?? '') . "\n"
             . "Documento: " . ($d['documento'] ?? '') . "\n"
             . "Email: " . ($d['email'] ?? '') . "\n"
             . "Teléfono: " . ($d['telefono'] ?? '') . "\n"
             . "IP: " . ($d['ip'] ?? '') . "\n"
             . "Fecha: " . ($d['fecha_hora'] ?? '') . "\n\n"
             . "El pago quedará pendiente de verificación manual por el equipo de PMO Solutions.";
    }

    /**
     * Emite respuesta de error HTTP adaptativa (JSON o Flash/Redirección)
     */
    private function respondWithError(string $message, int $statusCode, array $errors = []): void {
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
               || (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json'));

        if ($isAjax) {
            $this->json(false, $message, ['errors' => $errors], $statusCode);
            return;
        }

        // Renderizado directo con error en peticiones estándar
        http_response_code($statusCode);
        $courseSlug = self::canonicalizeSlug((string)($_POST['curso'] ?? '')) ?? 'nec4';
        $clase = (int)($_POST['clase'] ?? 1);
        $courseModel = new CourseModel();
        $course = $courseModel->getBySlug($courseSlug);

        $this->render('registro-pago', [
            'pageTitle'       => 'Registro de Inscripción y Pago | PMO Solutions',
            'metaDescription' => 'Registro de pago por clase. PMO Solutions.',
            'activeNav'       => 'capacitaciones',
            'course'          => $course,
            'courseSlug'      => $courseSlug,
            'selectedClass'   => $clase,
            'classPrices'     => self::CLASS_PRICES,
            'currentPrices'   => self::CLASS_PRICES[$clase] ?? self::CLASS_PRICES[1],
            'niubizUrl'       => self::NIUBIZ_URL,
            'formError'       => $message,
            'formErrors'      => $errors,
            'oldInput'        => $_POST,
        ]);
        exit();
    }

    /**
     * Emite respuesta de éxito HTTP adaptativa (JSON o Redirección)
     */
    private function respondWithSuccess(string $message, string $slug, int $clase): void {
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
               || (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json'));

        $targetUrl = '/pago-exitoso?curso=' . urlencode($slug) . '&clase=' . $clase;

        if ($isAjax) {
            $this->json(true, $message, [
                'curso'    => $slug,
                'clase'    => $clase,
                'redirect' => $targetUrl,
            ], 200);
            return;
        }

        $this->redirect($targetUrl);
    }
}
