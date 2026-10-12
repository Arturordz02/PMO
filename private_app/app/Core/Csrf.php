<?php
namespace App\Core;

/**
 * PMO SOLUTIONS - Módulo de Protección CSRF y Validación Estricta de Origen
 * 
 * Gestiona:
 * - Generación y persistencia criptográfica de tokens CSRF en sesión PHP segura
 * - Validación en tiempo constante mediante hash_equals()
 * - Validación estricta de cabeceras Origin y Referer con normalización
 * - Restricción de entornos (localhost/127.0.0.1 solo en modo development)
 * - Protección contra repetición y orígenes engañosos/nulos
 */
class Csrf {

    /** Duración de validez del token CSRF: 2 horas (7200 segundos) */
    public const TOKEN_TTL = 7200;

    /**
     * Obtiene el token CSRF activo o genera uno nuevo asociado a la sesión actual.
     */
    public static function getToken(): string {
        Security::startSecureSession();

        $now = time();
        $token = $_SESSION['pmo_csrf_token'] ?? null;
        $createdAt = $_SESSION['pmo_csrf_token_time'] ?? 0;

        if (empty($token) || !is_string($token) || ($now - $createdAt > self::TOKEN_TTL) || strlen($token) !== 64) {
            $token = bin2hex(random_bytes(32));
            $_SESSION['pmo_csrf_token'] = $token;
            $_SESSION['pmo_csrf_token_time'] = $now;
        }

        return $token;
    }

    /**
     * Valida un token CSRF provisto contra la sesión activa usando comparación en tiempo constante.
     */
    public static function validateToken(?string $token): bool {
        if (empty($token) || !is_string($token) || strlen($token) !== 64) {
            return false;
        }

        Security::startSecureSession();

        $storedToken = $_SESSION['pmo_csrf_token'] ?? null;
        $createdAt   = $_SESSION['pmo_csrf_token_time'] ?? 0;

        if (empty($storedToken) || !is_string($storedToken) || (time() - $createdAt > self::TOKEN_TTL)) {
            return false;
        }

        return hash_equals($storedToken, $token);
    }

    /**
     * Extrae el token CSRF desde cabeceras HTTP o datos del payload ya procesados.
     */
    public static function extractToken(?array $data = null): ?string {
        // 1. Cabecera HTTP personalizada X-CSRF-Token
        $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_SERVER['REDIRECT_HTTP_X_CSRF_TOKEN'] ?? null;
        if (!empty($headerToken) && is_string($headerToken)) {
            return trim($headerToken);
        }

        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (is_array($headers)) {
                foreach ($headers as $key => $value) {
                    if (strcasecmp($key, 'X-CSRF-Token') === 0 && !empty($value) && is_string($value)) {
                        return trim($value);
                    }
                }
            }
        }

        // 2. Campo en el payload de datos (POST o JSON decodificado)
        if ($data !== null && !empty($data['csrf_token']) && is_string($data['csrf_token'])) {
            return trim($data['csrf_token']);
        }

        // 3. Fallback a $_POST directo si no se pasó $data
        if ($data === null && !empty($_POST['csrf_token']) && is_string($_POST['csrf_token'])) {
            return trim($_POST['csrf_token']);
        }

        return null;
    }

    /**
     * Normaliza un origen o URL a su forma canónica: scheme://host[:port]
     */
    public static function normalizeOrigin(string $url): ?string {
        $trimmed = trim($url);
        if ($trimmed === '' || strtolower($trimmed) === 'null') {
            return null;
        }

        $parsed = parse_url($trimmed);
        if (!$parsed || empty($parsed['scheme']) || empty($parsed['host'])) {
            return null;
        }

        $scheme = strtolower($parsed['scheme']);
        $host   = strtolower($parsed['host']);
        $port   = isset($parsed['port']) ? (int)$parsed['port'] : null;

        // Omitir puertos estándar según esquema
        $isDefaultPort = ($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443);
        $portPart = ($port && !$isDefaultPort) ? ":{$port}" : '';

        return "{$scheme}://{$host}{$portPart}";
    }

    /**
     * Obtiene la lista canónica de orígenes permitidos según el entorno activo.
     */
    public static function getAllowedOrigins(array $configuredOrigins = []): array {
        $configFile = dirname(__DIR__) . '/Config/config.php';
        $config = [];
        if (file_exists($configFile)) {
            $config = require $configFile;
        }

        $origins = !empty($configuredOrigins) ? $configuredOrigins : ($config['security']['allowed_origins'] ?? []);

        // Incluir PMO_SITE_URL si está definido
        $siteUrl = $config['app']['site_url'] ?? Env::string('PMO_SITE_URL', 'https://pmo-solutions.com');
        if (!empty($siteUrl)) {
            $origins[] = $siteUrl;
        }

        // Normalizar todos los orígenes de la lista
        $normalizedList = [];
        foreach ($origins as $orig) {
            $norm = self::normalizeOrigin($orig);
            if ($norm !== null) {
                $normalizedList[] = $norm;
            }
        }

        $env = Env::string('PMO_APP_ENV', 'production');
        $isDevelopment = ($env === 'development');

        // Filtrar localhost y 127.0.0.1 si estamos en producción
        $finalList = [];
        foreach (array_unique($normalizedList) as $origin) {
            $parsed = parse_url($origin);
            $host = $parsed['host'] ?? '';
            $isLocal = ($host === 'localhost' || $host === '127.0.0.1' || str_starts_with($host, '192.168.') || str_starts_with($host, '10.'));

            if ($isLocal && !$isDevelopment) {
                continue; // En producción, prohibir orígenes locales
            }

            $finalList[] = $origin;
        }

        return array_values(array_unique($finalList));
    }

    /**
     * Valida la cabecera Origin contra la lista permitida.
     * Retorna:
     *   - true  si coincide exactamente con un origen permitido
     *   - false si el origen es 'null' o no está en la lista permitida
     *   - null  si la cabecera Origin no está presente
     */
    public static function checkOrigin(array $allowedOrigins = []): ?bool {
        if (!isset($_SERVER['HTTP_ORIGIN'])) {
            return null;
        }

        $rawOrigin = $_SERVER['HTTP_ORIGIN'];
        if (strtolower(trim($rawOrigin)) === 'null') {
            return false; // Rechazar explícitamente Origin: null
        }

        $normalized = self::normalizeOrigin($rawOrigin);
        if ($normalized === null) {
            return false;
        }

        $allowed = self::getAllowedOrigins($allowedOrigins);
        if (in_array($normalized, $allowed, true)) {
            return true;
        }

        // En desarrollo, permitir puertos locales arbitrarios en localhost y 127.0.0.1
        $env = Env::string('PMO_APP_ENV', 'production');
        if ($env === 'development') {
            $parsed = parse_url($normalized);
            $host = $parsed['host'] ?? '';
            $scheme = $parsed['scheme'] ?? 'http';
            if ($host === 'localhost' || $host === '127.0.0.1') {
                if (in_array("{$scheme}://{$host}", $allowed, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Valida la cabecera Referer contra la lista permitida.
     * Retorna:
     *   - true  si el origen del Referer coincide con uno permitido
     *   - false si el Referer proviene de un origen no autorizado
     *   - null  si la cabecera Referer no está presente
     */
    public static function checkReferer(array $allowedOrigins = []): ?bool {
        if (empty($_SERVER['HTTP_REFERER'])) {
            return null;
        }

        $normalized = self::normalizeOrigin($_SERVER['HTTP_REFERER']);
        if ($normalized === null) {
            return false;
        }

        $allowed = self::getAllowedOrigins($allowedOrigins);
        if (in_array($normalized, $allowed, true)) {
            return true;
        }

        // En desarrollo, permitir puertos locales arbitrarios en localhost y 127.0.0.1
        $env = Env::string('PMO_APP_ENV', 'production');
        if ($env === 'development') {
            $parsed = parse_url($normalized);
            $host = $parsed['host'] ?? '';
            $scheme = $parsed['scheme'] ?? 'http';
            if ($host === 'localhost' || $host === '127.0.0.1') {
                if (in_array("{$scheme}://{$host}", $allowed, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Valida exhaustivamente la petición HTTP (Token CSRF + Origen/Referer).
     * 
     * Reglas:
     * 1. Token CSRF obligatorio siempre.
     * 2. Si existe Origin -> debe ser válido.
     * 3. Si no existe Origin pero sí Referer -> Referer debe ser válido.
     * 4. Si ambos están ausentes -> solo se exige el token CSRF válido.
     * 
     * @param array|null $parsedData Datos del payload ya parseados (evita doble decodificación)
     * @param array $allowedOrigins Lista opcional de orígenes
     * @return array ['valid' => bool, 'error' => string, 'code' => int]
     */
    public static function validateRequest(?array $parsedData = null, array $allowedOrigins = []): array {
        // 1. Validar Origen (si está presente)
        $originCheck = self::checkOrigin($allowedOrigins);
        if ($originCheck === false) {
            return [
                'valid' => false,
                'error' => 'Origen de la solicitud no permitido (Origin no autorizado o nulo).',
                'code'  => 403
            ];
        }

        // 2. Si no hay Origin, validar Referer (si está presente)
        if ($originCheck === null) {
            $refererCheck = self::checkReferer($allowedOrigins);
            if ($refererCheck === false) {
                return [
                    'valid' => false,
                    'error' => 'Origen de la solicitud no permitido (Referer no autorizado).',
                    'code'  => 403
                ];
            }
        }

        // 3. Validar Token CSRF obligatorio
        $token = self::extractToken($parsedData);
        if ($token === null || !self::validateToken($token)) {
            return [
                'valid' => false,
                'error' => 'Token CSRF inválido, ausente o expirado. Por favor, recarga la página e intenta nuevamente.',
                'code'  => 403
            ];
        }

        return ['valid' => true, 'error' => '', 'code' => 200];
    }

    /**
     * Exige una petición válida y emite respuesta JSON HTTP 403 si falla la verificación.
     */
    public static function requireValidRequest(?array $parsedData = null, array $allowedOrigins = []): void {
        $result = self::validateRequest($parsedData, $allowedOrigins);
        if (!$result['valid']) {
            Security::sendJson(false, $result['error'], [], $result['code'] ?: 403);
        }
    }

    /**
     * Resetea el token CSRF activo (útil para pruebas unitarias).
     */
    public static function reset(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            unset($_SESSION['pmo_csrf_token'], $_SESSION['pmo_csrf_token_time']);
        }
    }
}

