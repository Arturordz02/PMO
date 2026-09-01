<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Security;
use App\Core\SmtpMailer;
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

        // Validar campos obligatorios
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

        // Sanitización
        $cleanData = [
            'nombre'   => Security::cleanString($input['nombre'] ?? '', 100),
            'telefono' => Security::cleanString($input['telefono'] ?? '', 30),
            'email'    => Security::cleanEmail($input['email'] ?? ''),
            'servicio' => Security::cleanString($input['servicio'] ?? 'Capacitación Profesional', 100),
            'mensaje'  => Security::cleanMultiline($input['mensaje'] ?? '', 2000),
        ];

        // Validar formato de email
        if (!empty($cleanData['email']) && !Security::validateEmail($cleanData['email'])) {
            $errors['email'] = 'El formato del correo electrónico ingresado no es válido.';
        }

        // Validar teléfono
        if (!empty($cleanData['telefono']) && !Security::validatePhone($cleanData['telefono'])) {
            $errors['telefono'] = 'El número telefónico debe contener entre 6 y 25 dígitos válidos.';
        }

        return [
            'errors' => $errors,
            'data'   => $cleanData
        ];
    }

    /**
     * Guarda la consulta de contacto en la Base de Datos si está conectada
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
            error_log("[ContactModel save Error] " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envía la notificación de correo vía SMTP
     */
    public function sendEmail(array $data): bool {
        $mailer = new SmtpMailer();
        return $mailer->sendContactNotification($data);
    }
}

