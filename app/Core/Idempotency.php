<?php
namespace App\Core;

/**
 * PMO SOLUTIONS — Módulo de Idempotencia
 * 
 * Garantiza que peticiones repetidas o reintentos no provoquen registros duplicados
 * ni múltiples envíos de correo, diferenciando operaciones y validando huellas de payload (Anti-Conflict 409).
 */
class Idempotency {

    /**
     * Extrae la clave de idempotencia desde la cabecera HTTP o el payload de formulario
     */
    public static function extractKey(): ?string {
        $key = $_SERVER['HTTP_IDEMPOTENCY_KEY'] ?? $_SERVER['REDIRECT_HTTP_IDEMPOTENCY_KEY'] ?? '';

        if (empty($key) && function_exists('getallheaders')) {
            $headers = getallheaders();
            foreach ($headers as $hKey => $hVal) {
                if (strcasecmp($hKey, 'Idempotency-Key') === 0) {
                    $key = (string)$hVal;
                    break;
                }
            }
        }

        if (empty($key) && !empty($_POST['idempotency_key'])) {
            $key = (string)$_POST['idempotency_key'];
        }

        $key = trim($key);
        return $key !== '' ? $key : null;
    }

    /**
     * Valida el formato de la clave de idempotencia (16 a 64 caracteres alfanuméricos, guiones y guiones bajos)
     */
    public static function isValidKeyFormat(?string $key): bool {
        if (!is_string($key)) {
            return false;
        }
        return (bool)preg_match('/^[a-zA-Z0-9_\-]{16,64}$/', $key);
    }

    /**
     * Exige una clave de idempotencia válida o emite HTTP 400 Bad Request
     */
    public static function requireKey(): string {
        $key = self::extractKey();

        if (empty($key) || !self::isValidKeyFormat($key)) {
            Security::sendJson(
                false,
                "La cabecera 'Idempotency-Key' es obligatoria y debe tener entre 16 y 64 caracteres válidos.",
                [],
                400
            );
        }

        return $key;
    }

    /**
     * Normaliza un payload para cálculo determinista de huella
     */
    public static function normalizePayload(array $payload): array {
        $clean = [];
        $ignoredKeys = [
            'idempotency_key', 'website_hp', '_MOCK_INPUT', 'token_csrf', 'csrf_token',
            'codigo_reclamacion', 'ip_address', 'ip', 'user_agent', 'estado', 'status',
            'fecha_registro', 'fecha_actualizacion', 'fecha_respuesta', 'fecha',
            'created_at', 'updated_at', 'sent_at', 'next_retry_at', 'timestamp',
            'id', 'reference_id', 'contact_id', 'claim_id', 'attempts', 'max_attempts',
            'respuesta_administracion', 'notas_internas', 'warning', 'errors'
        ];

        foreach ($payload as $k => $v) {
            $keyStr = (string)$k;
            if (in_array($keyStr, $ignoredKeys, true)) {
                continue;
            }
            if (is_array($v)) {
                $clean[$keyStr] = self::normalizePayload($v);
            } elseif (is_string($v)) {
                $clean[$keyStr] = trim($v);
            } elseif (is_bool($v)) {
                $clean[$keyStr] = $v ? 1 : 0;
            } else {
                $clean[$keyStr] = $v;
            }
        }

        ksort($clean);
        return $clean;
    }

    /**
     * Calcula el hash SHA-256 de la huella del payload
     */
    public static function computeFingerprint(array $payload): string {
        $normalized = self::normalizePayload($payload);
        return hash('sha256', json_encode($normalized, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Obtiene una respuesta previamente almacenada para esta clave y operación
     * 
     * @param string $operation 'contact' | 'claim'
     * @param string $key Clave de idempotencia
     * @param string|null $currentFingerprint Huella del payload actual
     * @return array|null Retorna ['conflict' => true] si hay colisión de payload, o el array de respuesta original
     */
    public static function getStoredResponse(string $operation, string $key, ?string $currentFingerprint = null): ?array {
        $cacheKey = 'idemp_' . $operation . '_' . md5($key);
        $cached = Cache::get($cacheKey);

        if (!is_array($cached)) {
            return null;
        }

        $storedFingerprint = $cached['fingerprint'] ?? null;
        $response = $cached['response'] ?? $cached;

        if ($currentFingerprint !== null && $storedFingerprint !== null) {
            if (!hash_equals($storedFingerprint, $currentFingerprint)) {
                return ['conflict' => true];
            }
        }

        return is_array($response) ? $response : null;
    }

    /**
     * Almacena la respuesta generada con su huella para reutilizarla ante reintentos idénticos
     */
    public static function storeResponse(string $operation, string $key, string $fingerprint, array $response, int $ttl = 86400): void {
        $cacheKey = 'idemp_' . $operation . '_' . md5($key);
        Cache::set($cacheKey, [
            'fingerprint' => $fingerprint,
            'response'    => $response,
            'created_at'  => time()
        ], $ttl);
    }

    /**
     * Emite una respuesta HTTP 409 Conflict ante intento de reusar una clave con payload diferente
     */
    public static function sendConflict(?string $message = null): void {
        Security::sendJson(
            false,
            $message ?? "Conflicto de Idempotencia: La clave proporcionada ya fue utilizada previamente con un contenido diferente.",
            [],
            409
        );
    }
}
