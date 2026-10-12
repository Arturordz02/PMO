<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\EmailOutbox;
use App\Core\Idempotency;
use App\Core\Security;
use App\Models\ContactModel;

/**
 * PMO SOLUTIONS - Controlador de Contacto y Asesoría (ContactController)
 */
class ContactController extends Controller {

    /**
     * Muestra la vista del formulario de contacto
     */
    public function index(): void {
        $this->render('contacto', [
            'pageTitle'       => 'Contacto & Asesoría Corporativa | PMO Solutions',
            'metaDescription' => 'Ponte en contacto con PMO Solutions. Asesoría técnica, consultas sobre cursos in-house, peritajes, capacitaciones y servicios de consultoría en ingeniería.',
            'activeNav'       => 'contacto'
        ]);
    }

    /**
     * Procesa la solicitud de envío de contacto (AJAX / POST)
     */
    public function submit(): void {
        Security::initHeaders($this->config['security']['allowed_origins'] ?? []);
        Security::requireMethod('POST');

        // 1. Rate Limiting por IP y endpoint ('contact') inmediatamente después del método HTTP
        $securityConfig = $this->config['security'] ?? [];
        if (!empty($securityConfig['rate_limit_enabled'])) {
            if (!Security::checkRateLimit('contact')) {
                $this->json(
                    false,
                    'Has superado el límite de solicitudes permitidas. Por favor, espera unos minutos o contáctanos directamente por WhatsApp.',
                    [],
                    429
                );
            }
        }

        // 2. Obtener datos de entrada (lectura única del cuerpo)
        $input = Security::getRequestData(65536, 5);

        if (Security::hasPayloadTooLarge()) {
            $this->json(false, 'El cuerpo de la solicitud excede el tamaño máximo permitido.', [], 413);
        }

        // 3. Validación de Origen y Protección CSRF obligatoria
        Csrf::requireValidRequest($input, $this->config['security']['allowed_origins'] ?? []);

        // 4. Exigir y validar clave de idempotencia
        $idempKey = Idempotency::requireKey();

        // 5. Validar y sanitizar datos de entrada
        $contactModel = new ContactModel();
        $validation = $contactModel->validateAndSanitize($input);

        if (!empty($validation['errors'])) {
            $this->json(
                false,
                'Por favor verifica los campos obligatorios del formulario.',
                ['errors' => $validation['errors']],
                422
            );
        }

        $data = $validation['data'];
        $currentFingerprint = Idempotency::computeFingerprint($data);

        // 6. Verificar Caché L1 con validación de huella (Anti-Conflict 409)
        $cached = Idempotency::getStoredResponse('contact', $idempKey, $currentFingerprint);
        if (is_array($cached)) {
            if (!empty($cached['conflict'])) {
                Idempotency::sendConflict();
            }
            $this->json(
                $cached['success'] ?? true,
                $cached['message'] ?? '',
                $cached['data'] ?? [],
                $cached['status'] ?? 200
            );
        }

        // 5. Verificar si ya existe en BD para esta clave de idempotencia
        $existingDb = $contactModel->findByIdempotencyKey($idempKey);
        if ($existingDb !== null) {
            // Verificar si el payload coincide mediante huella criptográfica (Anti-Conflict 409)
            $storedFp = $existingDb['request_fingerprint'] ?? null;
            if ($storedFp !== null && !hash_equals($storedFp, $currentFingerprint)) {
                Idempotency::sendConflict();
            }

            $successMsg = '¡Gracias por comunicarte con PMO Solutions! Tu mensaje ha sido recibido con éxito. Uno de nuestros directores o asesores técnicos se pondrá en contacto a la brevedad.';
            $resData = ['nombre' => $existingDb['nombre']];

            Idempotency::storeResponse('contact', $idempKey, $currentFingerprint, [
                'success' => true,
                'message' => $successMsg,
                'data'    => $resData,
                'status'  => 200
            ]);

            $this->json(true, $successMsg, $resData, 200);
        }

        // 6. Verificar si ya existe en fallback por archivos
        $safeKey = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $idempKey);
        $filePath = dirname(__DIR__, 2) . "/storage/outbox/contact_{$safeKey}.json";
        if (file_exists($filePath) || file_exists($filePath . '.processing')) {
            $checkPath = file_exists($filePath) ? $filePath : $filePath . '.processing';
            $fileRaw = @file_get_contents($checkPath);
            $fileData = $fileRaw ? json_decode($fileRaw, true) : null;

            if (is_array($fileData)) {
                $fileFp = $fileData['payload_fingerprint'] ?? null;
                if ($fileFp !== null && !hash_equals($fileFp, $currentFingerprint)) {
                    Idempotency::sendConflict();
                }
                $nombre = $fileData['form_data']['nombre'] ?? $data['nombre'];
            } else {
                $nombre = $data['nombre'];
            }

            $successMsg = '¡Gracias por comunicarte con PMO Solutions! Tu mensaje ha sido recibido con éxito. Uno de nuestros directores o asesores técnicos se pondrá en contacto a la brevedad.';
            $resData = ['nombre' => $nombre];

            Idempotency::storeResponse('contact', $idempKey, $currentFingerprint, [
                'success' => true,
                'message' => $successMsg,
                'data'    => $resData,
                'status'  => 200
            ]);

            $this->json(true, $successMsg, $resData, 200);
        }

        // 7. Persistencia y entrega multi-canal resiliente
        $pdo = Database::getConnection();
        $dbSaved = false;
        $contactId = null;

        if ($pdo) {
            $saveRes = $contactModel->saveWithIdempotency($idempKey, $data, $currentFingerprint);
            if ($saveRes === 'duplicate') {
                // Carrera concurrente: consultar registro ganador
                $winner = $contactModel->findByIdempotencyKey($idempKey);
                if ($winner !== null) {
                    $winnerFp = $winner['request_fingerprint'] ?? null;
                    if ($winnerFp !== null && !hash_equals($winnerFp, $currentFingerprint)) {
                        Idempotency::sendConflict();
                    }
                }

                $nombreWinner = $winner['nombre'] ?? $data['nombre'];
                $successMsg = '¡Gracias por comunicarte con PMO Solutions! Tu mensaje ha sido recibido con éxito. Uno de nuestros directores o asesores técnicos se pondrá en contacto a la brevedad.';
                $resData = ['nombre' => $nombreWinner];

                Idempotency::storeResponse('contact', $idempKey, $currentFingerprint, [
                    'success' => true,
                    'message' => $successMsg,
                    'data'    => $resData,
                    'status'  => 200
                ]);

                $this->json(true, $successMsg, $resData, 200);
            } elseif ($saveRes !== false) {
                $dbSaved = true;
                $contactId = (int)$saveRes;
            }
        }

        $emailSent = false;
        $outboxQueued = false;

        if ($dbSaved) {
            // Guardado en BD: intentar envío inmediato por SMTP
            $emailSent = $contactModel->sendEmail($data);
            if (!$emailSent) {
                // Encolar en outbox MySQL si falló el envío inmediato
                $outboxQueued = EmailOutbox::enqueueContactEmail($pdo, $idempKey, $contactId, $data, $currentFingerprint);
            }
        } else {
            // Base de datos no disponible: intentar envío SMTP directo
            $emailSent = $contactModel->sendEmail($data);

            if (!$emailSent) {
                // Encolar en archivo local SOLAMENTE si el envío SMTP falló
                $outboxQueued = EmailOutbox::enqueueContactEmail(null, $idempKey, null, $data, $currentFingerprint);
            }
        }

        // Si fallaron absolutamente todos los canales (sin BD, sin SMTP y sin poder escribir en outbox)
        if (!$dbSaved && !$emailSent && !$outboxQueued) {
            $this->json(
                false,
                'No fue posible registrar su solicitud debido a un problema técnico temporal. Por favor, intente nuevamente o contáctenos por WhatsApp.',
                [],
                503
            );
        }

        // Éxito: confirmación limpia al usuario sin exponer banderas de infraestructura
        $successMsg = '¡Gracias por comunicarte con PMO Solutions! Tu mensaje ha sido recibido con éxito. Uno de nuestros directores o asesores técnicos se pondrá en contacto a la brevedad.';
        $publicData = ['nombre' => $data['nombre']];

        Idempotency::storeResponse('contact', $idempKey, $currentFingerprint, [
            'success' => true,
            'message' => $successMsg,
            'data'    => $publicData,
            'status'  => 200
        ]);

        $this->json(true, $successMsg, $publicData, 200);
    }
}
