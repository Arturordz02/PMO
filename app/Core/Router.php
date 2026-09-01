<?php
namespace App\Core;

/**
 * PMO SOLUTIONS - Enrutador RESTful
 * 
 * Gestiona el mapeo de URLs amigables hacia Controladores y Métodos.
 * Soporta métodos GET y POST, parámetros dinámicos y fallback para 404.
 */
class Router {

    private array $routes = [];
    private $notFoundHandler = null;

    /**
     * Registra una ruta GET
     */
    public function get(string $path, $handler): self {
        $this->addRoute('GET', $path, $handler);
        return $this;
    }

    /**
     * Registra una ruta POST
     */
    public function post(string $path, $handler): self {
        $this->addRoute('POST', $path, $handler);
        return $this;
    }

    /**
     * Define el manejador para rutas no encontradas (Error 404)
     */
    public function setNotFoundHandler($handler): self {
        $this->notFoundHandler = $handler;
        return $this;
    }

    /**
     * Registra la ruta interna
     */
    private function addRoute(string $method, string $path, $handler): void {
        $normalizedPath = $this->normalizePath($path);
        $this->routes[] = [
            'method'  => strtoupper($method),
            'path'    => $normalizedPath,
            'handler' => $handler
        ];
    }

    /**
     * Normaliza la ruta eliminando barras extras y extensiones .html
     */
    private function normalizePath(string $path): string {
        // Eliminar extensiones .html si las trae para retrocompatibilidad
        $clean = preg_replace('/\.html$/i', '', $path);
        $clean = trim($clean, '/');
        return $clean === '' ? '/' : '/' . $clean;
    }

    /**
     * Despacha la petición entrante
     */
    public function dispatch(): void {
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';

        // Extraer solo la ruta eliminando query strings (?param=valor)
        $parsedUri = parse_url($requestUri, PHP_URL_PATH) ?? '/';

        // Manejar subcarpetas en entornos de desarrollo local (ej. /PMO-Solutions/...)
        $scriptName = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        if ($scriptName !== '/' && $scriptName !== '\\' && strpos($parsedUri, $scriptName) === 0) {
            $parsedUri = substr($parsedUri, strlen($scriptName));
        }

        $currentPath = $this->normalizePath($parsedUri);

        foreach ($this->routes as $route) {
            if ($route['method'] === $requestMethod) {
                // Conversión de ruta con comodines a regex: ej. /curso/{slug} -> /curso/([^/]+)
                $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $route['path']);
                $pattern = '#^' . $pattern . '$#';

                if (preg_match($pattern, $currentPath, $matches)) {
                    array_shift($matches); // Eliminar la coincidencia completa
                    $this->executeHandler($route['handler'], $matches);
                    return;
                }
            }
        }

        // Si no se encontró ninguna ruta coincidente, invocar el manejador 404
        if ($this->notFoundHandler) {
            $this->executeHandler($this->notFoundHandler, []);
        } else {
            http_response_code(404);
            echo "<h1>404 Not Found</h1><p>Página no encontrada.</p>";
        }
    }

    /**
     * Ejecuta el callback o el método del Controlador
     */
    private function executeHandler($handler, array $params = []): void {
        if (is_callable($handler)) {
            call_user_func_array($handler, $params);
            return;
        }

        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            if (class_exists($class)) {
                $controllerInstance = new $class();
                if (method_exists($controllerInstance, $method)) {
                    call_user_func_array([$controllerInstance, $method], $params);
                    return;
                }
            }
        }

        http_response_code(500);
        echo "<h1>500 Error de Servidor</h1><p>No se pudo ejecutar el controlador asignado.</p>";
    }
}

