<?php
namespace App\Models;

use App\Core\EmailOutbox;
use App\Core\Model;
use App\Core\Security;
use App\Core\SmtpMailer;
use PDO;
use PDOException;

/**
 * PMO SOLUTIONS - Modelo del Libro de Reclamaciones (ClaimModel)
 * 
 * Gestiona el cumplimiento de la Ley N° 29571 y D.S. N° 011-2011-PCM (INDECOPI).
 */
class ClaimModel extends Model {

    /**
     * Valida y sanitiza los campos de la Hoja de Reclamación
     */
    public function validateAndSanitize(array $input): array {
        $errors = [];

        // Validar Honeypot anti-spam
        if (!Security::checkHoneypot($input)) {
            $errors['bot'] = 'Acción no permitida.';
            return ['errors' => $errors, 'data' => []];
        }

        // 1. Validar tipos de datos escalares (rechazar arrays/objetos)
        $stringFields = [
            'tipo_documento', 'numero_documento', 'nombre_completo',
            'telefono', 'email', 'domicilio', 'tipo_servicio',
            'nombre_servicio', 'detalle_servicio', 'tipo_registro',
            'detalle_reclamacion', 'pedido_consumidor'
        ];
        foreach ($stringFields as $field) {
            if (isset($input[$field]) && !is_string($input[$field])) {
                $errors[$field] = "El campo '{$field}' debe ser una cadena de texto.";
            }
        }

        if (!empty($errors)) {
            return ['errors' => $errors, 'data' => []];
        }

        // 2. Validar Declaración Jurada obligatoria por ley contra valores permitidos
        $rawDj = $input['declaracion_jurada'] ?? null;
        $djValid = ($rawDj === 1 || $rawDj === true || $rawDj === '1' || $rawDj === 'true' || $rawDj === 'on');
        if (!$djValid || $rawDj === 'false' || $rawDj === '0' || $rawDj === 0 || $rawDj === false || is_array($rawDj) || is_object($rawDj)) {
            $errors['declaracion_jurada'] = 'Debe aceptar la declaración jurada con un valor válido para registrar la Hoja de Reclamación.';
        }

        // 3. Validar campos obligatorios
        $required = [
            'tipo_documento'      => 'Tipo de Documento',
            'numero_documento'    => 'Número de Documento',
            'nombre_completo'     => 'Nombres y Apellidos / Razón Social',
            'telefono'            => 'Teléfono / Celular',
            'email'               => 'Correo Electrónico',
            'domicilio'           => 'Domicilio',
            'tipo_servicio'       => 'Tipo de Contratación',
            'nombre_servicio'     => 'Nombre del Servicio o Capacitación',
            'tipo_registro'       => 'Tipo de Registro (Reclamo / Queja)',
            'detalle_reclamacion' => 'Detalle de la Reclamación',
            'pedido_consumidor'   => 'Pedido Concreto'
        ];

        $reqErrors = Security::validateRequired($input, $required);
        if (!empty($reqErrors)) {
            $errors = array_merge($errors, $reqErrors);
        }

        // 4. Validar lista blanca de Tipo de Documento
        $allowedDocTypes = ['DNI', 'Carnet de Extranjería', 'CE', 'RUC', 'Pasaporte'];
        $rawDocType = trim((string)($input['tipo_documento'] ?? ''));
        if (!empty($rawDocType) && !in_array($rawDocType, $allowedDocTypes, true)) {
            $errors['tipo_documento'] = 'El tipo de documento debe ser DNI, Carnet de Extranjería, CE, RUC o Pasaporte.';
        }

        // Normalizar CE hacia 'Carnet de Extranjería' para compatibilidad canónica
        $canonicalDocType = ($rawDocType === 'CE') ? 'Carnet de Extranjería' : $rawDocType;

        // 5. Validar lista blanca de Tipo de Registro
        $allowedRegTypes = ['Reclamo', 'Queja'];
        $rawRegType = trim((string)($input['tipo_registro'] ?? ''));
        if (!empty($rawRegType) && !in_array($rawRegType, $allowedRegTypes, true)) {
            $errors['tipo_registro'] = 'El tipo de registro debe ser Reclamo o Queja.';
        }

        $rawDocNum    = trim((string)($input['numero_documento'] ?? ''));
        $rawNombre    = trim((string)($input['nombre_completo'] ?? ''));
        $rawTelefono  = trim((string)($input['telefono'] ?? ''));
        $rawEmail     = trim((string)($input['email'] ?? ''));
        $rawDomicilio = trim((string)($input['domicilio'] ?? ''));
        $rawTipoServ  = trim((string)($input['tipo_servicio'] ?? 'Servicio de Capacitación / Curso'));
        $rawNomServ   = trim((string)($input['nombre_servicio'] ?? ''));
        $rawDetServ   = trim((string)($input['detalle_servicio'] ?? ''));
        $rawDetalle   = trim((string)($input['detalle_reclamacion'] ?? ''));
        $rawPedido    = trim((string)($input['pedido_consumidor'] ?? ''));

        // 6. Validar formato y longitud de Número de Documento según Tipo
        if ($rawDocNum !== '') {
            $effectiveDocType = in_array($canonicalDocType, ['DNI', 'Carnet de Extranjería', 'RUC', 'Pasaporte'], true) ? $canonicalDocType : 'DNI';
            if ($effectiveDocType === 'DNI') {
                if (!preg_match('/^\d{8}$/', $rawDocNum)) {
                    $errors['numero_documento'] = 'El DNI debe contener exactamente 8 dígitos numéricos.';
                }
            } elseif ($effectiveDocType === 'RUC') {
                if (!preg_match('/^\d{11}$/', $rawDocNum)) {
                    $errors['numero_documento'] = 'El RUC debe contener exactamente 11 dígitos numéricos.';
                }
            } else {
                if (!preg_match('/^[a-zA-Z0-9\-]{4,20}$/', $rawDocNum)) {
                    $errors['numero_documento'] = 'El documento debe contener entre 4 y 20 caracteres alfanuméricos.';
                }
            }
        }

        // 7. Validar longitudes mínimas y máximas sin recorte silencioso
        if ($rawNombre !== '') {
            if (mb_strlen($rawNombre, 'UTF-8') < 3) {
                $errors['nombre_completo'] = 'Nombres y Apellidos debe contener al menos 3 caracteres.';
            } elseif (mb_strlen($rawNombre, 'UTF-8') > 150) {
                $errors['nombre_completo'] = 'Nombres y Apellidos no debe exceder los 150 caracteres.';
            }
        }

        if ($rawTelefono !== '') {
            if (mb_strlen($rawTelefono, 'UTF-8') < 6 || !Security::validatePhone($rawTelefono)) {
                $errors['telefono'] = 'El número telefónico ingresado no es válido.';
            } elseif (mb_strlen($rawTelefono, 'UTF-8') > 25) {
                $errors['telefono'] = 'El número telefónico no debe exceder los 25 caracteres.';
            }
        }

        if ($rawEmail !== '') {
            if (!Security::validateEmail($rawEmail) || mb_strlen($rawEmail, 'UTF-8') < 5) {
                $errors['email'] = 'El formato del correo electrónico ingresado no es válido.';
            } elseif (mb_strlen($rawEmail, 'UTF-8') > 150) {
                $errors['email'] = 'El correo electrónico no debe exceder los 150 caracteres.';
            }
        }

        if ($rawDomicilio !== '') {
            if (mb_strlen($rawDomicilio, 'UTF-8') < 5) {
                $errors['domicilio'] = 'El domicilio debe contener al menos 5 caracteres.';
            } elseif (mb_strlen($rawDomicilio, 'UTF-8') > 255) {
                $errors['domicilio'] = 'El domicilio no debe exceder los 255 caracteres.';
            }
        }

        if ($rawTipoServ !== '' && mb_strlen($rawTipoServ, 'UTF-8') > 100) {
            $errors['tipo_servicio'] = 'El tipo de servicio no debe exceder los 100 caracteres.';
        }

        if ($rawNomServ !== '') {
            if (mb_strlen($rawNomServ, 'UTF-8') < 3) {
                $errors['nombre_servicio'] = 'El nombre del servicio debe contener al menos 3 caracteres.';
            } elseif (mb_strlen($rawNomServ, 'UTF-8') > 150) {
                $errors['nombre_servicio'] = 'El nombre del servicio no debe exceder los 150 caracteres.';
            }
        }

        if ($rawDetServ !== '' && mb_strlen($rawDetServ, 'UTF-8') > 255) {
            $errors['detalle_servicio'] = 'El detalle del servicio no debe exceder los 255 caracteres.';
        }

        if ($rawDetalle !== '') {
            if (mb_strlen($rawDetalle, 'UTF-8') < 10) {
                $errors['detalle_reclamacion'] = 'El detalle de la reclamación debe contener al menos 10 caracteres.';
            } elseif (mb_strlen($rawDetalle, 'UTF-8') > 3000) {
                $errors['detalle_reclamacion'] = 'El detalle de la reclamación no debe exceder los 3000 caracteres.';
            }
        }

        if ($rawPedido !== '') {
            if (mb_strlen($rawPedido, 'UTF-8') < 5) {
                $errors['pedido_consumidor'] = 'El pedido concreto debe contener al menos 5 caracteres.';
            } elseif (mb_strlen($rawPedido, 'UTF-8') > 3000) {
                $errors['pedido_consumidor'] = 'El pedido concreto no debe exceder los 3000 caracteres.';
            }
        }

        // 8. Sanitización
        $cleanData = [
            'codigo_reclamacion'  => Security::generateClaimCode('REC'),
            'tipo_documento'      => in_array($canonicalDocType, ['DNI', 'Carnet de Extranjería', 'RUC', 'Pasaporte'], true) ? $canonicalDocType : 'DNI',
            'numero_documento'    => Security::cleanString($rawDocNum, 30),
            'nombre_completo'     => Security::cleanString($rawNombre, 150),
            'telefono'            => Security::cleanString($rawTelefono, 30),
            'email'               => Security::cleanEmail($rawEmail),
            'domicilio'           => Security::cleanString($rawDomicilio, 255),
            'tipo_servicio'       => Security::cleanString($rawTipoServ, 100),
            'nombre_servicio'     => Security::cleanString($rawNomServ, 150),
            'detalle_servicio'    => Security::cleanString($rawDetServ, 255),
            'tipo_registro'       => in_array($rawRegType, $allowedRegTypes, true) ? $rawRegType : 'Reclamo',
            'detalle_reclamacion' => Security::cleanMultiline($rawDetalle, 3000),
            'pedido_consumidor'   => Security::cleanMultiline($rawPedido, 3000),
            'declaracion_jurada'  => $djValid ? 1 : 0
        ];

        return [
            'errors' => $errors,
            'data'   => $cleanData
        ];
    }

    /**
     * Busca una reclamación por clave de idempotencia
     */
    public function findByIdempotencyKey(string $idempKey): ?array {
        if (!$this->isDbConnected()) {
            return null;
        }

        try {
            $stmt = $this->db->prepare("SELECT id, codigo_reclamacion, idempotency_key, request_fingerprint, tipo_registro, email, nombre_completo, fecha_registro FROM reclamaciones WHERE idempotency_key = :key LIMIT 1");
            $stmt->execute([':key' => $idempKey]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (PDOException $e) {
            error_log("[ClaimModel findByIdempotencyKey Error] " . EmailOutbox::sanitizeError($e->getMessage()));
            return null;
        }
    }

    /**
     * Guarda la reclamación y encola sus correos dentro de una única transacción atómica
     * 
     * @return array|string|false Retorna array con datos de la reclamación, 'duplicate' si colisionó por UNIQUE, o false ante error
     */
    public function saveWithOutbox(string $idempKey, array $data, ?string $fingerprint = null): array|string|false {
        if (!$this->isDbConnected()) {
            return false;
        }

        $fp = $fingerprint ?? ($data['request_fingerprint'] ?? null);

        try {
            $this->db->beginTransaction();

            $sql = "INSERT INTO reclamaciones (
                        codigo_reclamacion, idempotency_key, request_fingerprint, tipo_documento, numero_documento, nombre_completo,
                        telefono, email, domicilio, tipo_servicio, nombre_servicio,
                        detalle_servicio, tipo_registro, detalle_reclamacion, pedido_consumidor,
                        declaracion_jurada, ip_address, user_agent, estado, fecha_registro
                    ) VALUES (
                        :codigo_reclamacion, :idempotency_key, :request_fingerprint, :tipo_documento, :numero_documento, :nombre_completo,
                        :telefono, :email, :domicilio, :tipo_servicio, :nombre_servicio,
                        :detalle_servicio, :tipo_registro, :detalle_reclamacion, :pedido_consumidor,
                        :declaracion_jurada, :ip_address, :user_agent, 'Pendiente', NOW()
                    )";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':codigo_reclamacion'  => $data['codigo_reclamacion'],
                ':idempotency_key'     => $idempKey,
                ':request_fingerprint' => $fp,
                ':tipo_documento'      => $data['tipo_documento'],
                ':numero_documento'    => $data['numero_documento'],
                ':nombre_completo'     => $data['nombre_completo'],
                ':telefono'            => $data['telefono'],
                ':email'               => $data['email'],
                ':domicilio'           => $data['domicilio'],
                ':tipo_servicio'       => $data['tipo_servicio'],
                ':nombre_servicio'     => $data['nombre_servicio'],
                ':detalle_servicio'    => $data['detalle_servicio'],
                ':tipo_registro'       => $data['tipo_registro'],
                ':detalle_reclamacion' => $data['detalle_reclamacion'],
                ':pedido_consumidor'   => $data['pedido_consumidor'],
                ':declaracion_jurada'  => $data['declaracion_jurada'],
                ':ip_address'          => Security::getClientIp(),
                ':user_agent'          => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
            ]);

            $claimId = (int)$this->db->lastInsertId();

            // Encolar correos en la misma transacción
            EmailOutbox::enqueueClaimEmails($this->db, $idempKey, $claimId, $data);

            $this->db->commit();

            return [
                'id'                 => $claimId,
                'codigo_reclamacion' => $data['codigo_reclamacion'],
                'tipo_registro'      => $data['tipo_registro'],
                'email'              => $data['email']
            ];
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            // Manejar colisión por condición de carrera en restricción UNIQUE (MySQL error 1062)
            if ((int)($e->errorInfo[1] ?? 0) === 1062 || str_contains($e->getMessage(), '1062')) {
                return 'duplicate';
            }

            error_log("[ClaimModel saveWithOutbox Error] " . EmailOutbox::sanitizeError($e->getMessage()));
            return false;
        }
    }

    /**
     * Guarda la reclamación en MySQL (método legado / retrocompatibilidad)
     */
    public function save(array $data): int|bool {
        if (!$this->isDbConnected()) {
            return false;
        }

        try {
            $sql = "INSERT INTO reclamaciones (
                        codigo_reclamacion, tipo_documento, numero_documento, nombre_completo,
                        telefono, email, domicilio, tipo_servicio, nombre_servicio,
                        detalle_servicio, tipo_registro, detalle_reclamacion, pedido_consumidor,
                        declaracion_jurada, ip_address, user_agent, estado, fecha_registro
                    ) VALUES (
                        :codigo_reclamacion, :tipo_documento, :numero_documento, :nombre_completo,
                        :telefono, :email, :domicilio, :tipo_servicio, :nombre_servicio,
                        :detalle_servicio, :tipo_registro, :detalle_reclamacion, :pedido_consumidor,
                        :declaracion_jurada, :ip_address, :user_agent, 'Pendiente', NOW()
                    )";

            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([
                ':codigo_reclamacion'  => $data['codigo_reclamacion'],
                ':tipo_documento'      => $data['tipo_documento'],
                ':numero_documento'    => $data['numero_documento'],
                ':nombre_completo'     => $data['nombre_completo'],
                ':telefono'            => $data['telefono'],
                ':email'               => $data['email'],
                ':domicilio'           => $data['domicilio'],
                ':tipo_servicio'       => $data['tipo_servicio'],
                ':nombre_servicio'     => $data['nombre_servicio'],
                ':detalle_servicio'    => $data['detalle_servicio'],
                ':tipo_registro'       => $data['tipo_registro'],
                ':detalle_reclamacion' => $data['detalle_reclamacion'],
                ':pedido_consumidor'   => $data['pedido_consumidor'],
                ':declaracion_jurada'  => $data['declaracion_jurada'],
                ':ip_address'          => Security::getClientIp(),
                ':user_agent'          => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
            ]);

            return $success ? (int)$this->db->lastInsertId() : false;
        } catch (PDOException $e) {
            error_log("[ClaimModel save Error] " . EmailOutbox::sanitizeError($e->getMessage()));
            return false;
        }
    }

    public static ?\Closure $mailerMock = null;

    /**
     * Envía las notificaciones de correo tanto al administrador como al consumidor
     */
    public function sendEmails(array $data): array {
        if (self::$mailerMock !== null) {
            return (self::$mailerMock)($data);
        }

        $mailer = new SmtpMailer();
        $adminSent = $mailer->sendClaimAdminNotification($data);
        $userSent  = $mailer->sendClaimUserReceipt($data);

        return [
            'admin_sent' => $adminSent,
            'user_sent'  => $userSent
        ];
    }
}
