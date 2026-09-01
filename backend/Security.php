<?php
/**
 * PMO SOLUTIONS - Módulo de Seguridad y Utilidades
 * 
 * Gestiona:
 * - Cabeceras de seguridad y CORS
 * - Control estricto de métodos HTTP
 * - Sanitización y validación de datos
 * - Protección anti-spam Honeypot y Rate Limiting
 * - Emisión estandarizada de respuestas JSON
 * - Generación de códigos únicos de reclamo
 */

if (!defined('PMO_APP_ACCESS')) {
    define('PMO_APP_ACCESS', true);
}

class Security {

    /**
     * Configura cabeceras de respuesta segura y gestiona CORS
     * 
     * @param array $allowedOrigins Lista de orígenes permitidos
     */
    public static function initHeaders(array $allowedOrigins = []) {
        // Establecer tipo de contenido JSON
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');

        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if ($origin) {
            if (empty($allowedOrigins) || in_array($origin, $allowedOrigins, true)) {
                header("Access-Control-Allow-Origin: {$origin}");
                header('Access-Control-Allow-Methods: POST, OPTIONS');
                header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
                header('Access-Control-Max-Age: 86400');
            }
        }

        // Si es una petición preflight de CORS (OPTIONS), responder inmediatamente
        if (isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) === 'OPTIONS') {
            http_response_code(204);
            exit();
        }
    }

    /**
     * Exige un método HTTP específico (por defecto POST)
     * 
     * @param string $method Método requerido (ej. 'POST')
     */
    public static function requireMethod(string $method = 'POST') {
        $currentMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (strtoupper($currentMethod) !== strtoupper($method)) {
            self::sendJson(false, 'Método HTTP no permitido. Se requiere ' . strtoupper($method), [], 405);
        }
    }

    /**
     * Obtiene los datos enviados por la petición (JSON o Form-data)
     * 
     * @return array
     */
    public static function getRequestData(): array {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (stripos($contentType, 'application/json') !== false) {
            $rawInput = file_get_contents('php://input');
            $data = json_decode($rawInput, true);
            return is_array($data) ? $data : [];
        }

        // Fallback para form-data tradicional o x-www-form-urlencoded
        return $_POST;
    }

    /**
     * Verifica el campo trampa Honeypot para neutralizar bots de spam
     * 
     * @param array $data Datos de la petición
     * @param string $field Nombre del campo honeypot
     * @return bool True si es legítimo (campo vacío), False si es bot
     */
    public static function checkHoneypot(array $data, string $field = 'website_hp'): bool {
        if (!empty($data[$field])) {
            // Si el campo tiene valor, es un bot que completó automáticamente todos los inputs
            return false;
        }
        return true;
    }

    /**
     * Sanitiza una cadena de texto de una sola línea
     * 
     * @param mixed $value
     * @param int $maxLength
     * @return string
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
     * Sanitiza un texto multilínea (mensajes, descripciones de reclamos)
     * 
     * @param mixed $value
     * @param int $maxLength
     * @return string
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
     * 
     * @param mixed $email
     * @return string
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
     * 
     * @param string $email
     * @return bool
     */
    public static function validateEmail(string $email): bool {
        if (empty($email) || mb_strlen($email, 'UTF-8') > 150) {
            return false;
        }
        return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Valida un número telefónico básico
     * 
     * @param string $phone
     * @return bool
     */
    public static function validatePhone(string $phone): bool {
        if (empty($phone)) {
            return false;
        }
        // Permitir dígitos, espacios, guiones y el signo + (mínimo 6 caracteres, máx 25)
        $clean = preg_replace('/[^\d\+\-\s\(\)]/', '', $phone);
        $digitsOnly = preg_replace('/\D/', '', $phone);
        return (strlen($digitsOnly) >= 6 && strlen($clean) <= 25);
    }

    /**
     * Valida la presencia de campos obligatorios en el array de datos
     * 
     * @param array $data
     * @param array $requiredFields Lista de nombres de campos
     * @return array Lista de errores (vacío si todo está correcto)
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
     * Formato: REC-2026-XXXXX (Ejemplo: REC-2026-84921)
     * 
     * @param string $prefix
     * @return string
     */
    public static function generateClaimCode(string $prefix = 'REC'): string {
        $year = date('Y');
        $random = strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
        return sprintf('%s-%s-%s', $prefix, $year, $random);
    }

    /**
     * Obtiene la dirección IP real del cliente de forma segura
     * 
     * @return string
     */
    public static function getClientIp(): string {
        $ipSources = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',  // Proxies / Load Balancers
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
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

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Rate limiter ligero basado en archivos temporales para evitar ataques de fuerza bruta o spam masivo
     * 
     * @param int $maxRequests
     * @param int $windowSeconds
     * @return bool True si la petición está dentro del límite, False si excede
     */
    public static function checkRateLimit(int $maxRequests = 10, int $windowSeconds = 300): bool {
        $ip = self::getClientIp();
        $ipHash = md5($ip);
        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pmo_rate_limits';

        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0755, true);
        }

        $file = $tempDir . DIRECTORY_SEPARATOR . "rate_{$ipHash}.json";
        $now = time();

        $history = [];
        if (file_exists($file)) {
            $content = @file_get_contents($file);
            $history = json_decode($content, true) ?: [];
        }

        // Filtrar solicitudes dentro de la ventana de tiempo
        $history = array_filter($history, function($timestamp) use ($now, $windowSeconds) {
            return ($now - $timestamp) < $windowSeconds;
        });

        if (count($history) >= $maxRequests) {
            return false;
        }

        // Registrar intento actual
        $history[] = $now;
        @file_put_contents($file, json_encode($history));

        return true;
    }

    /**
     * Emite una respuesta JSON estándar y termina la ejecución
     * 
     * @param bool $success
     * @param string $message
     * @param array $extraData
     * @param int $statusCode
     */
    public static function sendJson(bool $success, string $message, array $extraData = [], int $statusCode = 200) {
        http_response_code($statusCode);
        $response = array_merge([
            'success'   => $success,
            'message'   => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ], $extraData);

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit();
    }
}

