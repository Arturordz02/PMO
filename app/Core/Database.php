<?php
namespace App\Core;

use PDO;
use PDOException;

/**
 * PMO SOLUTIONS - Conexión Singleton a Base de Datos (PDO MySQL)
 * 
 * Gestiona una única conexión persistente segura a MySQL / MariaDB con PDO.
 */
class Database {
    private static ?PDO $instance = null;
    private static bool $connectionAttempted = false;
    private static ?string $lastError = null;

    /**
     * Obtiene la instancia única de conexión PDO
     * 
     * @param array|null $config Configuración opcional (si es null, lee de app/Config/config.php)
     * @return PDO|null Retorna la conexión PDO o null si está deshabilitada
     */
    public static function getConnection(?array $config = null): ?PDO {
        if (self::$instance !== null) {
            return self::$instance;
        }

        if (self::$connectionAttempted) {
            return null;
        }

        $appTimezone = 'America/Lima';
        if ($config === null) {
            $configFile = dirname(__DIR__) . '/Config/config.php';
            $globalConfig = file_exists($configFile) ? require $configFile : [];
            $config = $globalConfig['database'] ?? [];
            $appTimezone = $globalConfig['app']['timezone'] ?? 'America/Lima';
        } else {
            $appTimezone = $config['timezone'] ?? 'America/Lima';
        }

        // Si la base de datos está explícitamente deshabilitada o faltan credenciales requeridas
        if (empty($config['enabled']) || empty($config['name']) || empty($config['user']) || empty($config['password'])) {
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
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE {$charset}_unicode_ci",
                PDO::ATTR_TIMEOUT            => 5
            ];

            self::$instance = new PDO($dsn, $user, $pass, $options);

            // Sincronizar zona horaria de la sesión PDO derivando el offset de la timezone configurada
            try {
                $tzObj = new \DateTimeZone($appTimezone);
                $nowTz = new \DateTimeImmutable('now', $tzObj);
                $tzOffset = $nowTz->format('P');
            } catch (\Throwable) {
                $tzOffset = '-05:00';
            }
            self::$instance->exec("SET time_zone = '{$tzOffset}'");

            return self::$instance;
        } catch (PDOException $e) {
            $sanitized = EmailOutbox::sanitizeError($e->getMessage());
            self::$lastError = $sanitized;
            error_log("[PMO Database Error] " . $sanitized);
            return null;
        }
    }

    /**
     * Retorna si la conexión a base de datos está actualmente activa
     */
    public static function isConnected(): bool {
        return self::$instance !== null || self::getConnection() !== null;
    }

    /**
     * Alias estático para obtener la conexión PDO
     */
    public static function getInstance(?array $config = null): ?PDO {
        return self::getConnection($config);
    }

    /**
     * Retorna el último mensaje de error si existió
     */
    public static function getLastError(): ?string {
        return self::$lastError;
    }

    /**
     * Resetea la conexión para pruebas unitarias
     */
    public static function resetConnection(): void {
        self::$instance = null;
        self::$connectionAttempted = false;
        self::$lastError = null;
    }
}
