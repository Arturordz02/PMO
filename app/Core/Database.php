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

        if ($config === null) {
            $configFile = dirname(__DIR__) . '/Config/config.php';
            $globalConfig = file_exists($configFile) ? require $configFile : [];
            $config = $globalConfig['database'] ?? [];
        }

        // Si la base de datos está explícitamente deshabilitada
        if (empty($config['enabled'])) {
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
}

