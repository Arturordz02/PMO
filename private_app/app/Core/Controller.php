<?php
namespace App\Core;

/**
 * PMO SOLUTIONS - Controlador Base
 * 
 * Clase abstracta de la cual heredan todos los controladores de la aplicación.
 * Provee utilidades para renderizar vistas, emitir respuestas JSON y redireccionar.
 */
abstract class Controller {

    protected array $config = [];

    public function __construct() {
        $configFile = dirname(__DIR__) . '/Config/config.php';
        if (file_exists($configFile)) {
            $this->config = require $configFile;
        }
    }

    /**
     * Renderiza una vista pasando datos
     */
    protected function render(string $view, array $data = [], ?string $layout = 'main'): void {
        if (!headers_sent()) {
            header_remove('X-Powered-By');
            header('Content-Type: text/html; charset=utf-8');
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
            header('X-XSS-Protection: 1; mode=block');
            header('Referrer-Policy: strict-origin-when-cross-origin');
            header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
            header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net data:; img-src 'self' data: https:; connect-src 'self'; frame-src 'self' https://www.google.com https://maps.google.com; frame-ancestors 'self'; form-action 'self'; base-uri 'self'; object-src 'none';");
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private');
            header('Pragma: no-cache');
            if (Security::isHttps()) {
                header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
            }
        }

        // Inyectar automáticamente información global de la aplicación
        $data['app'] = $this->config['app'] ?? [];
        View::render($view, $data, $layout);
    }

    /**
     * Emite una respuesta JSON estandarizada para endpoints AJAX / API
     */
    protected function json(bool $success, string $message, array $extraData = [], int $statusCode = 200): void {
        Security::initHeaders($this->config['security']['allowed_origins'] ?? []);
        http_response_code($statusCode);

        $response = array_merge([
            'success'   => $success,
            'message'   => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ], $extraData);

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit();
    }

    /**
     * Redirecciona de forma segura a una ruta interna o a un origen explícitamente permitido.
     * Previene inyección de cabeceras (CRLF) y redirecciones abiertas (Open Redirect).
     */
    protected function redirect(string $url): void {
        $cleanUrl = trim(str_replace(["\r", "\n", "\0"], '', $url));
        $safeTarget = '/';

        if ($cleanUrl !== '') {
            if (str_starts_with($cleanUrl, '/') && !str_starts_with($cleanUrl, '//') && !str_starts_with($cleanUrl, '/\\')) {
                $safeTarget = $cleanUrl;
            } else {
                $origin = Csrf::normalizeOrigin($cleanUrl);
                $allowed = Csrf::getAllowedOrigins($this->config['security']['allowed_origins'] ?? []);
                if ($origin !== null && in_array($origin, $allowed, true)) {
                    $safeTarget = $cleanUrl;
                }
            }
        }

        header("Location: {$safeTarget}");
        exit();
    }
}

