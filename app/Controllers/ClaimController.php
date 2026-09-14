<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\EmailOutbox;
use App\Core\Idempotency;
use App\Core\Security;
use App\Models\ClaimModel;

/**
 * PMO SOLUTIONS - Controlador del Libro de Reclamaciones (ClaimController)
 */
class ClaimController extends Controller {

    /**
     * Muestra el formulario del Libro de Reclamaciones Virtual
     */
    public function index(): void {
        $this->render('libro-de-reclamaciones', [
            'pageTitle'       => 'Libro de Reclamaciones Virtual | PMO Solutions',
            'metaDescription' => 'Libro de Reclamaciones Virtual de PMO Solutions conforme a la Ley N° 29571 (Código de Protección y Defensa del Consumidor) y directivas de INDECOPI.',
            'activeNav'       => 'reclamos'
        ]);
    }

    /**
     * Procesa el registro de la Hoja de Reclamación (AJAX / POST)
     */
    public function submit(): void {
        Security::initHeaders($this->config['security']['allowed_origins'] ?? []);
        Security::requireMethod('POST');

        // 1. Rate Limiting por IP y endpoint ('claim') inmediatamente después del método HTTP
        $securityConfig = $this->config['security'] ?? [];
        if (!empty($securityConfig['rate_limit_enabled'])) {
            if (!Security::checkRateLimit('claim')) {
                $this->json(
                    false,
                    'Has superado el límite de solicitudes. Por favor espera unos minutos antes de volver a intentar.',
                    [],
                    429
                );
            }
        }

        // 2. Obtener payload (lectura única del cuerpo)
        $input = Security::getRequestData(65536, 5);

        if (Security::hasPayloadTooLarge()) {
            $this->json(false, 'El cuerpo de la solicitud excede el tamaño máximo permitido.', [], 413);
        }

        // 3. Validación de Origen y Protección CSRF obligatoria
        Csrf::requireValidRequest($input, $this->config['security']['allowed_origins'] ?? []);

        // 4. Exigir y validar clave de idempotencia
        $idempKey = Idempotency::requireKey();

        // 5. Validar y sanitizar payload
        $claimModel = new ClaimModel();
        $validation = $claimModel->validateAndSanitize($input);

        if (!empty($validation['errors'])) {
            $this->json(
                false,
                'Por favor completa todos los campos obligatorios del Libro de Reclamaciones.',
                ['errors' => $validation['errors']],
                422
            );
        }

        $data = $validation['data'];
        $currentFingerprint = Idempotency::computeFingerprint($data);

        // 6. Verificar si la respuesta ya está en Caché L1 con validación de huella (Anti-Conflict 409)
        $cached = Idempotency::getStoredResponse('claim', $idempKey, $currentFingerprint);
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
        $existing = $claimModel->findByIdempotencyKey($idempKey);
        if ($existing !== null) {
            // Verificar si el payload coincide mediante huella criptográfica (Anti-Conflict 409)
            $storedFp = $existing['request_fingerprint'] ?? null;
            if ($storedFp !== null && !hash_equals($storedFp, $currentFingerprint)) {
                Idempotency::sendConflict();
            }

            $existingData = [
                'codigo_reclamacion' => $existing['codigo_reclamacion'],
                'tipo_registro'      => $existing['tipo_registro'],
                'email'              => $existing['email']
            ];
            $msg = "Su Hoja de {$existing['tipo_registro']} ha sido registrada formalmente. Hemos generado la constancia digital con el código de seguimiento {$existing['codigo_reclamacion']}.";
            
            Idempotency::storeResponse('claim', $idempKey, $currentFingerprint, [
                'success' => true,
                'message' => $msg,
                'data'    => $existingData,
                'status'  => 200
            ]);

            $this->json(true, $msg, $existingData, 200);
        }

        // 6. Guardar en MySQL con outbox transaccional (Persistencia obligatoria)
        $saved = $claimModel->saveWithOutbox($idempKey, $data, $currentFingerprint);

        if ($saved === 'duplicate') {
            // Carrera concurrente: consultar registro ganador
            $winner = $claimModel->findByIdempotencyKey($idempKey);
            if ($winner !== null) {
                $winnerFp = $winner['request_fingerprint'] ?? null;
                if ($winnerFp !== null && !hash_equals($winnerFp, $currentFingerprint)) {
                    Idempotency::sendConflict();
                }
            }

            $winnerData = [
                'codigo_reclamacion' => $winner['codigo_reclamacion'] ?? $data['codigo_reclamacion'],
                'tipo_registro'      => $winner['tipo_registro'] ?? $data['tipo_registro'],
                'email'              => $winner['email'] ?? $data['email']
            ];
            $msg = "Su Hoja de {$winnerData['tipo_registro']} ha sido registrada formalmente. Hemos generado la constancia digital con el código de seguimiento {$winnerData['codigo_reclamacion']}.";

            Idempotency::storeResponse('claim', $idempKey, $currentFingerprint, [
                'success' => true,
                'message' => $msg,
                'data'    => $winnerData,
                'status'  => 200
            ]);

            $this->json(true, $msg, $winnerData, 200);
        }

        if ($saved === false) {
            $this->json(
                false,
                'El servicio del Libro de Reclamaciones no se encuentra disponible temporalmente. Por favor intenta nuevamente en unos minutos o contáctanos directamente.',
                [],
                503
            );
        }

        // 7. Intentar procesar envíos inmediatos de outbox
        try {
            EmailOutbox::processPending(2);
        } catch (\Throwable $e) {
            // El worker en cron reintentará el envío en segundo plano
        }

        $responseData = [
            'codigo_reclamacion' => $data['codigo_reclamacion'],
            'tipo_registro'      => $data['tipo_registro'],
            'email'              => $data['email']
        ];
        $msg = "Su Hoja de {$data['tipo_registro']} ha sido registrada formalmente. Hemos generado la constancia digital con el código de seguimiento {$data['codigo_reclamacion']}.";

        Idempotency::storeResponse('claim', $idempKey, $currentFingerprint, [
            'success' => true,
            'message' => $msg,
            'data'    => $responseData,
            'status'  => 200
        ]);

        $this->json(true, $msg, $responseData, 200);
    }
}
