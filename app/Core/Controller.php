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
     * Redirecciona a una URL relativa o absoluta
     */
    protected function redirect(string $url): void {
        header("Location: {$url}");
        exit();
    }
}

