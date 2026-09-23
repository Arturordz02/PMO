<?php
namespace App\Core;

/**
 * PMO SOLUTIONS — Módulo de Gestión de Variables de Entorno y Configuración
 * 
 * Centraliza la lectura jerárquica con prioridad absoluta a variables del sistema/cPanel
 * y tipado estricto (booleanos, enteros, strings).
 */
class Env {

    private static bool $loaded = false;
    private static ?array $fileEnv = null;

    /**
     * Carga variables desde un archivo .env si existe y aplica según entorno
     */
    public static function load(?string $path = null, bool $force = false): void {
        if (self::$loaded && !$force) {
            return;
        }

        $envFile = $path ?? dirname(__DIR__, 2) . '/.env';
        self::$loaded = true;
        self::$fileEnv = [];

        // Cargar .env SOLAMENTE cuando una variable real del sistema indique explícitamente PMO_APP_ENV=development
        $systemEnv = getenv('PMO_APP_ENV');
        if ($systemEnv === false || $systemEnv === '') {
            $systemEnv = $_ENV['PMO_APP_ENV'] ?? ($_SERVER['PMO_APP_ENV'] ?? '');
        }

        if ($systemEnv !== 'development' && !$force) {
            return;
        }

        if (!file_exists($envFile) || !is_readable($envFile)) {
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, ';')) {
                continue;
            }

            if (!str_contains($line, '=')) {
                continue;
            }

            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            // Eliminar comillas envolventes si existen
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }

            self::$fileEnv[$name] = $value;

            // No sobreescribir variables ya definidas en el entorno real (cPanel / FastCGI / Apache)
            if (getenv($name) === false && !isset($_ENV[$name]) && !isset($_SERVER[$name])) {
                putenv("{$name}={$value}");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }

    /**
     * Obtiene una variable de entorno con prioridad absoluta:
     * 1. getenv()
     * 2. $_ENV
     * 3. $_SERVER
     * 4. Archivo .env cargado
     * 5. Valor por defecto
     */
    public static function get(string $key, mixed $default = null): mixed {
        // 1. getenv()
        $val = getenv($key);
        if ($val !== false && $val !== null) {
            return $val;
        }

        // 2. $_ENV
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }

        // 3. $_SERVER
        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }

        // 4. File Env
        if (self::$fileEnv !== null && isset(self::$fileEnv[$key])) {
            return self::$fileEnv[$key];
        }

        return $default;
    }

    /**
     * Convierte y valida estrictamente un valor booleano
     * Reconoce: true/false, 1/0, yes/no, on/off. Valores inválidos retornan el $default seguro.
     */
    public static function bool(string $key, bool $default = false): bool {
        $val = self::get($key, null);
        if ($val === null) {
            return $default;
        }

        if (is_bool($val)) {
            return $val;
        }

        if (is_int($val)) {
            if ($val === 1) return true;
            if ($val === 0) return false;
            return $default;
        }

        if (is_string($val)) {
            $lower = strtolower(trim($val));
            if (in_array($lower, ['true', '1', 'yes', 'on'], true)) {
                return true;
            }
            if (in_array($lower, ['false', '0', 'no', 'off', ''], true)) {
                return false;
            }
        }

        return $default;
    }

    /**
     * Convierte y valida estrictamente un valor entero
     */
    public static function int(string $key, int $default = 0): int {
        $val = self::get($key, null);
        if ($val === null || $val === '') {
            return $default;
        }

        if (is_int($val)) {
            return $val;
        }

        if (is_numeric($val)) {
            return (int)$val;
        }

        return $default;
    }

    /**
     * Obtiene una variable como string limpio
     */
    public static function string(string $key, string $default = ''): string {
        $val = self::get($key, null);
        if ($val === null || $val === '') {
            return $default;
        }

        return trim((string)$val);
    }

    /**
     * Obtiene una variable como array (delimitado por comas si es string)
     */
    public static function array(string $key, array $default = []): array {
        $val = self::get($key, null);
        if ($val === null || $val === '') {
            return $default;
        }

        if (is_array($val)) {
            return $val;
        }

        if (is_string($val)) {
            $items = array_map('trim', explode(',', $val));
            return array_values(array_filter($items, fn($item) => $item !== ''));
        }

        return $default;
    }

    /**
     * Resetea el estado interno para pruebas unitarias
     */
    public static function reset(): void {
        self::$loaded = false;
        self::$fileEnv = null;
    }
}
