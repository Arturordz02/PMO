<?php
/**
 * PMO SOLUTIONS - Capa de Acceso a Datos (PDO MySQL)
 * 
 * Gestiona la conexión y operaciones seguras con la base de datos MySQL.
 * Utiliza sentencias preparadas para prevenir inyección SQL.
 * Compatible con las tablas definidas en schema.sql (reclamaciones y contactos).
 */

if (!defined('PMO_APP_ACCESS')) {
    define('PMO_APP_ACCESS', true);
}

class Database {
    private static ?PDO $instance = null;
    private static bool $connectionAttempted = false;
    private static ?string $lastError = null;

    /**
     * Obtiene la instancia única de conexión PDO
     * 
     * @param array $config Configuración de la base de datos desde config.php
     * @return PDO|null Retorna la conexión PDO o null si está deshabilitada/falla
     */
    public static function getConnection(array $config): ?PDO {
        if (self::$instance !== null) {
            return self::$instance;
        }

        // Si la base de datos está explícitamente deshabilitada
        if (empty($config['enabled'])) {
            return null;
        }

        if (self::$connectionAttempted) {
            return null;
        }

        self::$connectionAttempted = true;

        try {
            $host    = $config['host'] ?? 'localhost';
            $port    = (int)($config['port'] ?? 3306);
            $dbname  = $config['name'] ?? '';
            $user    = $config['user'] ?? '';
            $pass    = $config['password'] ?? '';
            $charset = $config['charset'] ?? 'utf8mb4';

            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE {$charset}_unicode_ci",
                PDO::ATTR_TIMEOUT            => 5
            ];

            self::$instance = new PDO($dsn, $user, $pass, $options);
            return self::$instance;
        } catch (PDOException $e) {
            self::$lastError = $e->getMessage();
            error_log("[PMO Database Error] " . $e->getMessage());
            return null;
        }
    }

    /**
     * Retorna el último mensaje de error si existió
     */
    public static function getLastError(): ?string {
        return self::$lastError;
    }

    /**
     * Guarda una reclamación en la tabla 'reclamaciones'
     * 
     * @param PDO $pdo
     * @param array $data
     * @return int|bool ID del registro insertado o false si falló
     */
    public static function saveClaim(PDO $pdo, array $data) {
        try {
            $sql = "INSERT INTO reclamaciones (
                        codigo_reclamacion,
                        tipo_documento,
                        numero_documento,
                        nombre_completo,
                        telefono,
                        email,
                        domicilio,
                        tipo_servicio,
                        nombre_servicio,
                        detalle_servicio,
                        tipo_registro,
                        detalle_reclamacion,
                        pedido_consumidor,
                        declaracion_jurada,
                        ip_address,
                        user_agent,
                        estado,
                        fecha_registro
                    ) VALUES (
                        :codigo_reclamacion,
                        :tipo_documento,
                        :numero_documento,
                        :nombre_completo,
                        :telefono,
                        :email,
                        :domicilio,
                        :tipo_servicio,
                        :nombre_servicio,
                        :detalle_servicio,
                        :tipo_registro,
                        :detalle_reclamacion,
                        :pedido_consumidor,
                        :declaracion_jurada,
                        :ip_address,
                        :user_agent,
                        'Pendiente',
                        NOW()
                    )";

            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([
                ':codigo_reclamacion'  => $data['codigo_reclamacion'] ?? '',
                ':tipo_documento'      => $data['tipo_documento'] ?? 'DNI',
                ':numero_documento'    => $data['numero_documento'] ?? '',
                ':nombre_completo'     => $data['nombre_completo'] ?? '',
                ':telefono'            => $data['telefono'] ?? '',
                ':email'               => $data['email'] ?? '',
                ':domicilio'           => $data['domicilio'] ?? '',
                ':tipo_servicio'       => $data['tipo_servicio'] ?? 'Servicio de Capacitación / Curso',
                ':nombre_servicio'     => $data['nombre_servicio'] ?? '',
                ':detalle_servicio'    => $data['detalle_servicio'] ?? null,
                ':tipo_registro'       => $data['tipo_registro'] ?? 'Reclamo',
                ':detalle_reclamacion' => $data['detalle_reclamacion'] ?? '',
                ':pedido_consumidor'   => $data['pedido_consumidor'] ?? '',
                ':declaracion_jurada'  => !empty($data['declaracion_jurada']) ? 1 : 0,
                ':ip_address'          => Security::getClientIp(),
                ':user_agent'          => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
            ]);

            return $success ? (int)$pdo->lastInsertId() : false;
        } catch (PDOException $e) {
            self::$lastError = $e->getMessage();
            error_log("[PMO Database saveClaim Error] " . $e->getMessage());
            return false;
        }
    }

    /**
     * Guarda una consulta de contacto en la tabla 'contactos'
     * 
     * @param PDO $pdo
     * @param array $data
     * @return int|bool ID del registro insertado o false si falló
     */
    public static function saveContact(PDO $pdo, array $data) {
        try {
            $sql = "INSERT INTO contactos (
                        nombre,
                        telefono,
                        email,
                        servicio,
                        mensaje,
                        ip_address,
                        user_agent,
                        estado,
                        fecha_registro
                    ) VALUES (
                        :nombre,
                        :telefono,
                        :email,
                        :servicio,
                        :mensaje,
                        :ip_address,
                        :user_agent,
                        'Nuevo',
                        NOW()
                    )";

            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([
                ':nombre'     => $data['nombre'] ?? '',
                ':telefono'   => $data['telefono'] ?? '',
                ':email'      => $data['email'] ?? '',
                ':servicio'   => $data['servicio'] ?? 'Capacitación Profesional',
                ':mensaje'    => $data['mensaje'] ?? '',
                ':ip_address' => Security::getClientIp(),
                ':user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
            ]);

            return $success ? (int)$pdo->lastInsertId() : false;
        } catch (PDOException $e) {
            self::$lastError = $e->getMessage();
            error_log("[PMO Database saveContact Error] " . $e->getMessage());
            return false;
        }
    }
}
