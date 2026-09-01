<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Security;
use App\Core\SmtpMailer;
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

        // Validar Declaración Jurada obligatoria por ley
        if (empty($input['declaracion_jurada'])) {
            $errors['declaracion_jurada'] = 'Debe aceptar la declaración jurada para registrar la Hoja de Reclamación.';
        }

        // Validar campos obligatorios
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

        // Sanitización estricta
        $cleanData = [
            'codigo_reclamacion'  => Security::generateClaimCode('REC'),
            'tipo_documento'      => Security::cleanString($input['tipo_documento'] ?? 'DNI', 20),
            'numero_documento'    => Security::cleanString($input['numero_documento'] ?? '', 30),
            'nombre_completo'     => Security::cleanString($input['nombre_completo'] ?? '', 150),
            'telefono'            => Security::cleanString($input['telefono'] ?? '', 30),
            'email'               => Security::cleanEmail($input['email'] ?? ''),
            'domicilio'           => Security::cleanString($input['domicilio'] ?? '', 255),
            'tipo_servicio'       => Security::cleanString($input['tipo_servicio'] ?? 'Servicio de Capacitación / Curso', 100),
            'nombre_servicio'     => Security::cleanString($input['nombre_servicio'] ?? '', 150),
            'detalle_servicio'    => Security::cleanString($input['detalle_servicio'] ?? '', 255),
            'tipo_registro'       => in_array($input['tipo_registro'] ?? '', ['Reclamo', 'Queja'], true) ? $input['tipo_registro'] : 'Reclamo',
            'detalle_reclamacion' => Security::cleanMultiline($input['detalle_reclamacion'] ?? '', 3000),
            'pedido_consumidor'   => Security::cleanMultiline($input['pedido_consumidor'] ?? '', 3000),
            'declaracion_jurada'  => !empty($input['declaracion_jurada']) ? 1 : 0
        ];

        // Validar formato de email
        if (!empty($cleanData['email']) && !Security::validateEmail($cleanData['email'])) {
            $errors['email'] = 'El formato del correo electrónico ingresado no es válido.';
        }

        // Validar teléfono
        if (!empty($cleanData['telefono']) && !Security::validatePhone($cleanData['telefono'])) {
            $errors['telefono'] = 'El número telefónico ingresado no es válido.';
        }

        return [
            'errors' => $errors,
            'data'   => $cleanData
        ];
    }

    /**
     * Guarda la reclamación en MySQL
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
            error_log("[ClaimModel save Error] " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envía las notificaciones de correo tanto al administrador como al consumidor
     */
    public function sendEmails(array $data): array {
        $mailer = new SmtpMailer();
        $adminSent = $mailer->sendClaimAdminNotification($data);
        $userSent  = $mailer->sendClaimUserReceipt($data);

        return [
            'admin_sent' => $adminSent,
            'user_sent'  => $userSent
        ];
    }
}

