<?php
namespace App\Models;

use App\Core\EmailOutbox;
use App\Core\Model;
use App\Core\Security;
use App\Core\SmtpMailer;
use PDO;
use PDOException;

/**
 * PMO SOLUTIONS - Modelo de Contacto (ContactModel)
 * 
 * Encapsula la validación de negocio, persistencia en BD y envío de notificaciones.
 */
class ContactModel extends Model {

    /**
     * Valida y sanitiza los datos del formulario de contacto
     */
    public function validateAndSanitize(array $input): array {
        $errors = [];

        // Validar Honeypot anti-spam
        if (!Security::checkHoneypot($input)) {
            $errors['bot'] = 'Acción no permitida.';
            return ['errors' => $errors, 'data' => []];
        }

        // 1. Validar tipos de datos escalares (rechazar arrays/objetos)
        $stringFields = ['nombre', 'telefono', 'email', 'servicio', 'mensaje'];
        foreach ($stringFields as $field) {
            if (isset($input[$field]) && !is_string($input[$field])) {
                $errors[$field] = "El campo '{$field}' debe ser una cadena de texto.";
            }
        }

        if (!empty($errors)) {
            return ['errors' => $errors, 'data' => []];
        }

        // 2. Validar campos obligatorios
        $required = [
            'nombre'   => 'Nombre Completo',
            'telefono' => 'Teléfono / WhatsApp',
            'email'    => 'Correo Electrónico',
            'mensaje'  => 'Mensaje'
        ];
        $reqErrors = Security::validateRequired($input, $required);
        if (!empty($reqErrors)) {
            $errors = array_merge($errors, $reqErrors);
        }

        $rawNombre   = trim((string)($input['nombre'] ?? ''));
        $rawTelefono = trim((string)($input['telefono'] ?? ''));
        $rawEmail    = trim((string)($input['email'] ?? ''));
        $rawServicio = trim((string)($input['servicio'] ?? 'Capacitación Profesional'));
        $rawMensaje  = trim((string)($input['mensaje'] ?? ''));

        // 3. Validar longitudes máximas y mínimas sin recorte silencioso
        if ($rawNombre !== '') {
            if (mb_strlen($rawNombre, 'UTF-8') < 3) {
                $errors['nombre'] = 'El nombre completo debe tener al menos 3 caracteres.';
            } elseif (mb_strlen($rawNombre, 'UTF-8') > 100) {
                $errors['nombre'] = 'El nombre completo no debe exceder los 100 caracteres.';
            }
        }

        if ($rawTelefono !== '') {
            if (mb_strlen($rawTelefono, 'UTF-8') < 6 || !Security::validatePhone($rawTelefono)) {
                $errors['telefono'] = 'El número telefónico debe contener entre 6 y 25 dígitos válidos.';
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

        if ($rawServicio !== '' && mb_strlen($rawServicio, 'UTF-8') > 100) {
            $errors['servicio'] = 'El campo servicio no debe exceder los 100 caracteres.';
        }

        if ($rawMensaje !== '') {
            if (mb_strlen($rawMensaje, 'UTF-8') < 10) {
                $errors['mensaje'] = 'El mensaje debe tener al menos 10 caracteres.';
            } elseif (mb_strlen($rawMensaje, 'UTF-8') > 2000) {
                $errors['mensaje'] = 'El mensaje no debe exceder los 2000 caracteres.';
            }
        }

        // 4. Sanitización
        $cleanData = [
            'nombre'   => Security::cleanString($rawNombre, 100),
            'telefono' => Security::cleanString($rawTelefono, 30),
            'email'    => Security::cleanEmail($rawEmail),
            'servicio' => Security::cleanString($rawServicio, 100),
            'mensaje'  => Security::cleanMultiline($rawMensaje, 2000),
        ];

        return [
            'errors' => $errors,
            'data'   => $cleanData
        ];
    }

    /**
     * Busca un contacto por clave de idempotencia
     */
    public function findByIdempotencyKey(string $idempKey): ?array {
        if (!$this->isDbConnected()) {
            return null;
        }

        try {
            $stmt = $this->db->prepare("SELECT id, idempotency_key, request_fingerprint, nombre, telefono, email, servicio, mensaje, fecha_registro FROM contactos WHERE idempotency_key = :key LIMIT 1");
            $stmt->execute([':key' => $idempKey]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (PDOException $e) {
            error_log("[ContactModel findByIdempotencyKey Error] " . EmailOutbox::sanitizeError($e->getMessage()));
            return null;
        }
    }

    /**
     * Guarda la consulta de contacto en MySQL con clave de idempotencia
     * 
     * @return int|string|false Retorna el ID insertado, 'duplicate' si colisionó por UNIQUE, o false ante error
     */
    public function saveWithIdempotency(string $idempKey, array $data, ?string $fingerprint = null): int|string|false {
        if (!$this->isDbConnected()) {
            return false;
        }

        $fp = $fingerprint ?? ($data['request_fingerprint'] ?? null);

        try {
            $sql = "INSERT INTO contactos (
                        idempotency_key, request_fingerprint, nombre, telefono, email, servicio, mensaje,
                        ip_address, user_agent, estado, fecha_registro
                    ) VALUES (
                        :idempotency_key, :request_fingerprint, :nombre, :telefono, :email, :servicio, :mensaje,
                        :ip_address, :user_agent, 'Nuevo', NOW()
                    )";

            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([
                ':idempotency_key'     => $idempKey,
                ':request_fingerprint' => $fp,
                ':nombre'              => $data['nombre'],
                ':telefono'            => $data['telefono'],
                ':email'               => $data['email'],
                ':servicio'            => $data['servicio'],
                ':mensaje'             => $data['mensaje'],
                ':ip_address'          => Security::getClientIp(),
                ':user_agent'          => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
            ]);

            return $success ? (int)$this->db->lastInsertId() : false;
        } catch (PDOException $e) {
            // Manejar colisión por condición de carrera en restricción UNIQUE
            if ((int)($e->errorInfo[1] ?? 0) === 1062 || $e->getCode() === '23000') {
                return 'duplicate';
            }
            error_log("[ContactModel saveWithIdempotency Error] " . EmailOutbox::sanitizeError($e->getMessage()));
            return false;
        }
    }

    /**
     * Guarda la consulta de contacto en la Base de Datos si está conectada (retrocompatibilidad)
     */
    public function save(array $data): int|bool {
        if (!$this->isDbConnected()) {
            return false;
        }

        try {
            $sql = "INSERT INTO contactos (
                        nombre, telefono, email, servicio, mensaje,
                        ip_address, user_agent, estado, fecha_registro
                    ) VALUES (
                        :nombre, :telefono, :email, :servicio, :mensaje,
                        :ip_address, :user_agent, 'Nuevo', NOW()
                    )";

            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([
                ':nombre'     => $data['nombre'],
                ':telefono'   => $data['telefono'],
                ':email'      => $data['email'],
                ':servicio'   => $data['servicio'],
                ':mensaje'    => $data['mensaje'],
                ':ip_address' => Security::getClientIp(),
                ':user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
            ]);

            return $success ? (int)$this->db->lastInsertId() : false;
        } catch (PDOException $e) {
            error_log("[ContactModel save Error] " . EmailOutbox::sanitizeError($e->getMessage()));
            return false;
        }
    }

    public static ?\Closure $mailerMock = null;

    /**
     * Envía la notificación de correo vía SMTP
     */
    public function sendEmail(array $data): bool {
        if (self::$mailerMock !== null) {
            return (bool)(self::$mailerMock)($data);
        }

        $mailer = new SmtpMailer();
        return $mailer->sendContactNotification($data);
    }
}
