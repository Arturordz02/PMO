<?php
namespace App\Core;

/**
 * PMO SOLUTIONS - Módulo de Seguridad y Utilidades (Security Hardening)
 * 
 * Gestiona:
 * - Cabeceras de seguridad y CORS
 * - Control estricto de métodos HTTP
 * - Sanitización de entradas y mitigación XSS
 * - Validación de formatos de datos
 * - Protección anti-spam Honeypot y Rate Limiting
 * - Generación de códigos únicos institucionales
 */
class Security {

    /**
     * Configura cabeceras de respuesta segura y gestiona CORS
     */
    public static function initHeaders(array $allowedOrigins = []): void {
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net data:; img-src 'self' data: https:; connect-src 'self'; frame-src 'self' https://www.google.com https://maps.google.com; frame-ancestors 'self'; form-action 'self'; base-uri 'self'; object-src 'none';");
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private');
        header('Pragma: no-cache');

        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if ($origin && strtolower(trim($origin)) !== 'null') {
            $normalized = Csrf::normalizeOrigin($origin);
            $allowed = Csrf::getAllowedOrigins($allowedOrigins);
            if ($normalized !== null && in_array($normalized, $allowed, true)) {
                header("Access-Control-Allow-Origin: {$normalized}");
                header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
                header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, Idempotency-Key, X-CSRF-Token, Authorization, X-Report-Token');
                header('Access-Control-Max-Age: 86400');
            }
        }

        if (isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) === 'OPTIONS') {
            http_response_code(204);
            exit();
        }
    }

    /**
     * Exige un método HTTP específico
     */
    public static function requireMethod(string $method = 'POST'): void {
        $currentMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (strtoupper($currentMethod) !== strtoupper($method)) {
            self::sendJson(false, 'Método HTTP no permitido. Se requiere ' . strtoupper($method), [], 405);
        }
    }

    private static bool $jsonSyntaxError = false;
    private static bool $payloadTooLarge = false;
    private static bool $jsonDepthExceeded = false;
    private static bool $unsupportedMediaType = false;
    private static ?string $cachedRawInput = null;

    /**
     * Retorna true si la última lectura de JSON tuvo error de sintaxis
     */
    public static function hasJsonSyntaxError(): bool {
        return self::$jsonSyntaxError;
    }

    /**
     * Retorna true si el cuerpo de la solicitud excedió el tamaño máximo permitido
     */
    public static function hasPayloadTooLarge(): bool {
        return self::$payloadTooLarge;
    }

    /**
     * Retorna true si la estructura JSON excedió la profundidad máxima permitida
     */
    public static function hasJsonDepthExceeded(): bool {
        return self::$jsonDepthExceeded;
    }

    /**
     * Retorna true si el Content-Type no es compatible con el endpoint esperado
     */
    public static function hasUnsupportedMediaType(): bool {
        return self::$unsupportedMediaType;
    }

    /**
     * Resetea los indicadores de error de entrada
     */
    public static function resetInputErrors(): void {
        self::$jsonSyntaxError = false;
        self::$payloadTooLarge = false;
        self::$jsonDepthExceeded = false;
        self::$unsupportedMediaType = false;
        self::$cachedRawInput = null;
    }

    /**
     * Resetea el indicador de error de sintaxis JSON (compatibilidad)
     */
    public static function resetJsonSyntaxError(): void {
        self::resetInputErrors();
    }

    /**
     * Valida que el Content-Type sea exactamente application/json (permitiendo parámetros como charset).
     * Rechaza application/jsonp, text/json, text/plain, etc.
     */
    public static function requireJsonContentType(?string $contentType = null): bool {
        $ct = $contentType ?? ($_SERVER['CONTENT_TYPE'] ?? ($_SERVER['HTTP_CONTENT_TYPE'] ?? ''));
        $ct = trim($ct);
        if ($ct === '') {
            self::$unsupportedMediaType = true;
            return false;
        }

        $parts = explode(';', $ct, 2);
        $mediaType = strtolower(trim($parts[0]));

        if ($mediaType !== 'application/json') {
            self::$unsupportedMediaType = true;
            return false;
        }

        return true;
    }

    /**
     * Obtiene los datos enviados por la petición (JSON o Form-data)
     * Controla Content-Length, tamaño real y profundidad máxima de JSON (default 5).
     */
    public static function getRequestData(int $maxBytes = 65536, int $maxDepth = 5): array {
        self::resetInputErrors();
        $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';

        // 1. Control preventivo de Content-Length
        if (isset($_SERVER['CONTENT_LENGTH'])) {
            $contentLength = (int)$_SERVER['CONTENT_LENGTH'];
            if ($contentLength > $maxBytes) {
                self::$payloadTooLarge = true;
                return [];
            }
        }

        if (self::requireJsonContentType($contentType)) {
            // Lectura única de php://input
            $rawInput = self::$cachedRawInput;
            if ($rawInput === null) {
                $rawInput = file_get_contents('php://input');
                // En entorno CLI de testing, permitir mock de input si php://input está vacío
                if (empty($rawInput) && php_sapi_name() === 'cli' && isset($GLOBALS['_MOCK_INPUT'])) {
                    $rawInput = (string)$GLOBALS['_MOCK_INPUT'];
                }
                self::$cachedRawInput = (string)$rawInput;
            }

            // 2. Control del tamaño real del cuerpo
            if (strlen($rawInput) > $maxBytes) {
                self::$payloadTooLarge = true;
                return [];
            }

            if (trim($rawInput) === '') {
                // Fallback exclusivamente en CLI si no se proveyó input en php://input
                if (php_sapi_name() === 'cli' && !empty($_POST) && is_array($_POST)) {
                    return $_POST;
                }
                return [];
            }

            // 3. Decodificación con límite de profundidad estricto
            $data = json_decode($rawInput, true, $maxDepth);
            $err = json_last_error();

            if ($err === JSON_ERROR_DEPTH) {
                self::$jsonDepthExceeded = true;
                return [];
            }

            if ($err !== JSON_ERROR_NONE) {
                self::$jsonSyntaxError = true;
                return [];
            }

            if (!is_array($data)) {
                return [];
            }

            return $data;
        }

        return !empty($_POST) && is_array($_POST) ? $_POST : [];
    }

    /**
     * Verifica el campo trampa Honeypot para neutralizar bots de spam
     */
    public static function checkHoneypot(array $data, string $field = 'website_hp'): bool {
        return empty($data[$field]);
    }

    /**
     * Sanitiza una cadena de texto de una sola línea (previene ataques XSS)
     */
    public static function cleanString($value, int $maxLength = 255): string {
        if (!is_string($value)) {
            return '';
        }
        $clean = trim($value);
        $clean = strip_tags($clean);
        $clean = htmlspecialchars($clean, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        if (mb_strlen($clean, 'UTF-8') > $maxLength) {
            $clean = mb_substr($clean, 0, $maxLength, 'UTF-8');
        }
        return $clean;
    }

    /**
     * Sanitiza un texto multilínea preservando saltos
     */
    public static function cleanMultiline($value, int $maxLength = 4000): string {
        if (!is_string($value)) {
            return '';
        }
        $clean = trim($value);
        $clean = strip_tags($clean);
        $clean = htmlspecialchars($clean, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        if (mb_strlen($clean, 'UTF-8') > $maxLength) {
            $clean = mb_substr($clean, 0, $maxLength, 'UTF-8');
        }
        return $clean;
    }

    /**
     * Sanitiza y normaliza una dirección de correo electrónico
     */
    public static function cleanEmail($email): string {
        if (!is_string($email)) {
            return '';
        }
        $clean = trim(strtolower($email));
        $clean = filter_var($clean, FILTER_SANITIZE_EMAIL);
        return $clean ?: '';
    }

    /**
     * Valida el formato de correo electrónico
     */
    public static function validateEmail(string $email): bool {
        if (empty($email) || mb_strlen($email, 'UTF-8') > 150) {
            return false;
        }
        return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Valida un número telefónico básico
     */
    public static function validatePhone(string $phone): bool {
        if (empty($phone)) {
            return false;
        }
        $clean = preg_replace('/[^\d\+\-\s\(\)]/', '', $phone);
        $digitsOnly = preg_replace('/\D/', '', $phone);
        return (strlen($digitsOnly) >= 6 && strlen($clean) <= 25);
    }

    /**
     * Valida la presencia de campos obligatorios en el array de datos
     */
    public static function validateRequired(array $data, array $requiredFields): array {
        $errors = [];
        foreach ($requiredFields as $field => $label) {
            $fieldName = is_numeric($field) ? $label : $field;
            $fieldLabel = is_numeric($field) ? ucfirst($label) : $label;

            if (!isset($data[$fieldName]) || trim((string)$data[$fieldName]) === '') {
                $errors[$fieldName] = "El campo '{$fieldLabel}' es obligatorio.";
            }
        }
        return $errors;
    }

    /**
     * Genera un código único institucional para la Hoja de Reclamación
     * Formato: REC-2026-XXXXX
     */
    public static function generateClaimCode(string $prefix = 'REC'): string {
        $year = date('Y');
        $random = strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
        return sprintf('%s-%s-%s', $prefix, $year, $random);
    }

    private static int $lastRetryAfter = 0;

    /**
     * Retorna los segundos restantes antes de poder reintentar tras un 429
     */
    public static function getLastRetryAfter(): int {
        return self::$lastRetryAfter;
    }

    /**
     * Genera un token criptográfico de alta entropía (32 bytes = 64 hex) para acceso al reporte
     */
    public static function generateReportToken(): string {
        return bin2hex(random_bytes(32));
    }

    /**
     * Calcula el hash SHA-256 de un token de reporte
     */
    public static function hashReportToken(string $token): string {
        return hash('sha256', $token);
    }

    /**
     * Valida el formato del token (hexadecimal estricto de 64 caracteres)
     */
    public static function isValidReportTokenFormat(string $token): bool {
        return strlen($token) === 64 && ctype_xdigit($token);
    }

    /**
     * Extrae el token de reporte desde cabeceras HTTP (Bearer o X-Report-Token)
     */
    public static function extractReportToken(): ?string {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';

        if (empty($authHeader) && function_exists('getallheaders')) {
            $headers = getallheaders();
            foreach ($headers as $key => $value) {
                if (strcasecmp($key, 'Authorization') === 0) {
                    $authHeader = $value;
                    break;
                }
            }
        }

        if (!empty($authHeader) && preg_match('/Bearer\s+([a-fA-F0-9]{64})/i', $authHeader, $matches)) {
            return strtolower($matches[1]);
        }

        // Alternativa mediante cabecera personalizada X-Report-Token
        $customToken = $_SERVER['HTTP_X_REPORT_TOKEN'] ?? '';
        if (empty($customToken) && function_exists('getallheaders')) {
            $headers = getallheaders();
            foreach ($headers as $key => $value) {
                if (strcasecmp($key, 'X-Report-Token') === 0) {
                    $customToken = $value;
                    break;
                }
            }
        }

        if (!empty($customToken) && self::isValidReportTokenFormat($customToken)) {
            return strtolower(trim($customToken));
        }

        return null;
    }

    /**
     * Emite una respuesta 404 estandarizada e indistinguible byte a byte
     * (sin timestamp dinámico) para mitigar oráculos de enumeración.
     */
    public static function sendIndistinguishable404(): void {
        self::initHeaders();
        header('Cache-Control: no-store, private');
        http_response_code(404);
        $payload = [
            'success' => false,
            'message' => 'Evaluación no encontrada. Verifica el código ingresado.'
        ];
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit();
    }

    /**
     * Ejecuta una comparación en tiempo constante ficticia para mitigar canales laterales de tiempo
     */
    public static function dummyHashEquals(): void {
        $dummy = '0000000000000000000000000000000000000000000000000000000000000000';
        hash_equals($dummy, $dummy);
    }

    /**
     * Inicia una sesión PHP segura con directivas de hardening
     */
    public static function startSecureSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            $sessName = session_name();
            if (!empty($_COOKIE[$sessName]) && is_string($_COOKIE[$sessName]) && preg_match('/^[a-zA-Z0-9,-]{1,128}$/', $_COOKIE[$sessName])) {
                @session_id($_COOKIE[$sessName]);
            }

            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
                || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

            if (!headers_sent()) {
                ini_set('session.use_strict_mode', '1');
                ini_set('session.use_trans_sid', '0');

                session_set_cookie_params([
                    'lifetime' => 0,
                    'path'     => '/',
                    'domain'   => '',
                    'secure'   => $isHttps,
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
            }

            @session_start();
        }

        // Control de expiración de sesión administrativa (inactividad 30m / absoluto 8h)
        if (isset($_SESSION['pmo_admin_user'])) {
            $now = time();
            $lastActivity = $_SESSION['pmo_last_activity'] ?? $now;
            $createdAt    = $_SESSION['pmo_created_at'] ?? $now;

            if (($now - $lastActivity > 1800) || ($now - $createdAt > 28800)) {
                self::logout();
            } else {
                $_SESSION['pmo_last_activity'] = $now;
            }
        }
    }

    /**
     * Autentica un usuario administrativo
     */
    public static function login(string $username, string $password): bool {
        self::startSecureSession();

        $adminUser = getenv('PMO_ADMIN_USERNAME') ?: '';
        $adminHash = getenv('PMO_ADMIN_PASSWORD_HASH') ?: '';

        if (empty($adminUser) || empty($adminHash)) {
            $configFile = dirname(__DIR__) . '/Config/config.php';
            if (file_exists($configFile)) {
                $conf = require $configFile;
                $adminUser = $conf['security']['admin_username'] ?? '';
                $adminHash = $conf['security']['admin_password_hash'] ?? '';
            }
        }

        // Si no está configurado el administrador, rechazar con cálculo ficticio
        if (empty($adminUser) || empty($adminHash)) {
            password_verify($password, '$2y$10$abcdefghijklmnopqrstuuABCDEFGHIJKLMNOPQRSTUVWXYZ012345');
            return false;
        }

        $userMatches = hash_equals($adminUser, $username);
        $passMatches = password_verify($password, $adminHash);

        if ($userMatches && $passMatches) {
            if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
                @session_regenerate_id(true);
            }
            $_SESSION['pmo_admin_user'] = $username;
            $_SESSION['pmo_admin_role'] = 'admin';
            $_SESSION['pmo_created_at'] = time();
            $_SESSION['pmo_last_activity'] = time();
            return true;
        }

        return false;
    }

    /**
     * Cierra la sesión administrativa destruyéndola completamente.
     */
    public static function logout(): void {
        if (isset($_SESSION) && is_array($_SESSION)) {
            unset(
                $_SESSION['pmo_admin_user'],
                $_SESSION['pmo_admin_role'],
                $_SESSION['pmo_created_at'],
                $_SESSION['pmo_last_activity']
            );
            $_SESSION = [];
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            if (ini_get("session.use_cookies") && !headers_sent()) {
                $params = session_get_cookie_params();
                @setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }
            @session_destroy();
        }
    }

    /**
     * Verifica si el usuario actual está autenticado
     */
    public static function isAuthenticated(): bool {
        self::startSecureSession();
        return !empty($_SESSION['pmo_admin_user']);
    }

    /**
     * Retorna el rol del usuario autenticado
     */
    public static function getUserRole(): ?string {
        self::startSecureSession();
        return $_SESSION['pmo_admin_role'] ?? null;
    }

    /**
     * Comprueba si el usuario tiene un rol específico
     */
    public static function hasRole(string $role): bool {
        return self::isAuthenticated() && self::getUserRole() === $role;
    }

    /**
     * Exige un rol específico o emite HTTP 403 Forbidden
     */
    public static function requireRole(string $role): void {
        if (!self::hasRole($role)) {
            self::sendJson(false, 'Acceso no autorizado. Se requieren permisos administrativos.', [], 403);
        }
    }

    /**
     * Exige autenticación o emite HTTP 401 Unauthorized
     */
    public static function requireAuthentication(): void {
        if (!self::isAuthenticated()) {
            self::sendJson(false, 'Acceso denegado. Se requiere autenticación.', [], 401);
        }
    }

    /**
     * Obtiene la dirección IP del cliente validando proxies confiables
     */
    public static function getClientIp(): string {
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        $configFile = dirname(__DIR__) . '/Config/config.php';
        $trustedProxies = [];
        if (file_exists($configFile)) {
            $conf = require $configFile;
            $trustedProxies = $conf['security']['trusted_proxies'] ?? [];
        }

        // Solo inspeccionar cabeceras X-Forwarded / Cloudflare si REMOTE_ADDR está en la lista blanca de proxies confiables
        if (in_array($remoteAddr, $trustedProxies, true)) {
            $ipSources = [
                'HTTP_CF_CONNECTING_IP',
                'HTTP_X_FORWARDED_FOR',
                'HTTP_CLIENT_IP'
            ];

            foreach ($ipSources as $key) {
                if (!empty($_SERVER[$key])) {
                    $ipList = explode(',', $_SERVER[$key]);
                    $ip = trim($ipList[0]);
                    if (filter_var($ip, FILTER_VALIDATE_IP)) {
                        return $ip;
                    }
                }
            }
        }

        return filter_var($remoteAddr, FILTER_VALIDATE_IP) ? $remoteAddr : '0.0.0.0';
    }

    /**
     * Obtiene la configuración de rate limiting para una acción
     */
    public static function getRateLimitConfig(string $actionKey): array {
        $configFile = dirname(__DIR__) . '/Config/config.php';
        if (file_exists($configFile)) {
            $conf = require $configFile;
            if (isset($conf['security']['rate_limits'][$actionKey])) {
                return $conf['security']['rate_limits'][$actionKey];
            }
            if (isset($conf['security']['rate_limits']['default'])) {
                return $conf['security']['rate_limits']['default'];
            }
        }
        return ['requests' => 10, 'window' => 300];
    }

    /**
     * Rate limiter con claves diferenciadas, almacenamiento atómico mediante flock() y cálculo de Retry-After.
     * Utiliza un manejador de errores temporal para evitar que advertencias de E_WARNING (fopen, mkdir, flock, etc.)
     * contaminen la salida HTTP o revelen rutas del sistema. Ante cualquier fallo de E/S, responde HTTP 503 con JSON válido.
     */
    public static function checkRateLimit(string|int $actionKey = 'default', ?int $maxRequests = null, ?int $windowSeconds = null): bool {
        if (is_int($actionKey)) {
            $windowSeconds = $maxRequests ?: 300;
            $maxRequests = $actionKey;
            $actionKey = 'default';
        }

        if ($maxRequests === null || $windowSeconds === null) {
            $cfg = self::getRateLimitConfig((string)$actionKey);
            $maxRequests = $maxRequests ?? ($cfg['requests'] ?? 10);
            $windowSeconds = $windowSeconds ?? ($cfg['window'] ?? 300);
        }

        $ip = self::getClientIp();
        $ipHash = md5($ip);
        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pmo_rate_limits';

        $fp = false;
        $locked = false;
        $failed = false;
        $failReason = '';

        // Manejador de errores temporal para suprimir advertencias de E/S y evitar fugas de rutas
        set_error_handler(function($errno, $errstr, $errfile, $errline) {
            return true;
        });

        try {
            if (!is_dir($tempDir)) {
                $dirCreated = mkdir($tempDir, 0755, true);
                if (!$dirCreated && !is_dir($tempDir)) {
                    $failed = true;
                    $failReason = "Cannot create rate limit dir: {$tempDir}";
                }
            }

            if (!$failed) {
                $safeAction = preg_replace('/[^a-zA-Z0-9_\-]/', '_', (string)$actionKey);
                $file = $tempDir . DIRECTORY_SEPARATOR . "rate_{$safeAction}_{$ipHash}.json";
                $now = time();

                $fp = fopen($file, 'c+');
                if ($fp === false) {
                    $failed = true;
                    $failReason = "Failed to open rate limit storage: {$file}";
                } else {
                    $locked = flock($fp, LOCK_EX);
                    if (!$locked) {
                        $failed = true;
                        $failReason = "Failed to acquire lock on rate limit storage: {$file}";
                    } else {
                        rewind($fp);
                        $content = stream_get_contents($fp);
                        $history = !empty($content) ? json_decode($content, true) : [];
                        if (!is_array($history)) {
                            $history = [];
                        }

                        $history = array_values(array_filter($history, function($timestamp) use ($now, $windowSeconds) {
                            return is_numeric($timestamp) && ($now - $timestamp) < $windowSeconds;
                        }));

                        if (count($history) >= $maxRequests) {
                            $oldest = reset($history);
                            self::$lastRetryAfter = max(1, ($oldest + $windowSeconds) - $now);
                            flock($fp, LOCK_UN);
                            fclose($fp);
                            $fp = false;
                            return false;
                        }

                        $history[] = $now;
                        $jsonContent = json_encode($history);

                        $writeSuccess = (ftruncate($fp, 0) !== false)
                            && (rewind($fp) !== false)
                            && (fwrite($fp, (string)$jsonContent) !== false)
                            && fflush($fp);

                        if (!$writeSuccess) {
                            $failed = true;
                            $failReason = "Failed to write to rate limit storage: {$file}";
                        }

                        flock($fp, LOCK_UN);
                        fclose($fp);
                        $fp = false;
                    }
                }
            }
        } catch (\Throwable $e) {
            $failed = true;
            $failReason = $e->getMessage();
        } finally {
            restore_error_handler();
            if ($fp !== false && is_resource($fp)) {
                if ($locked) {
                    @flock($fp, LOCK_UN);
                }
                @fclose($fp);
            }
        }

        if ($failed) {
            $sanitizedErr = EmailOutbox::sanitizeError($failReason);
            error_log("[RateLimiter Error] {$sanitizedErr}");
            self::sendJson(false, 'Servicio no disponible temporalmente.', [], 503);
        }

        return true;
    }

    /**
     * Resetea el contador de rate limiting para una acción e IP
     */
    public static function resetRateLimit(string $actionKey = 'default'): void {
        $ip = self::getClientIp();
        $ipHash = md5($ip);
        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pmo_rate_limits';
        $safeAction = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $actionKey);
        $file = $tempDir . DIRECTORY_SEPARATOR . "rate_{$safeAction}_{$ipHash}.json";
        if (file_exists($file)) {
            set_error_handler(function() { return true; });
            try {
                unlink($file);
            } catch (\Throwable $e) {
                // Ignore unlink error during cleanup
            } finally {
                restore_error_handler();
            }
        }
    }

    /**
     * Emite una respuesta JSON estándar y termina la ejecución
     */
    public static function sendJson(bool $success, string $message, array $extraData = [], int $statusCode = 200): void {
        http_response_code($statusCode);
        if ($statusCode === 429 && self::$lastRetryAfter > 0 && !headers_sent()) {
            header('Retry-After: ' . self::$lastRetryAfter);
        }
        $response = array_merge([
            'success'   => $success,
            'message'   => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ], $extraData);

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit();
    }
}

