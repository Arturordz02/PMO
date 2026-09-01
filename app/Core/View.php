<?php
namespace App\Core;

/**
 * PMO SOLUTIONS - Motor de Vistas (View Engine)
 * 
 * Gestiona el renderizado de vistas basadas en plantillas, layouts maestros
 * y componentes parciales reutilizables.
 */
class View {

    /**
     * Renderiza una vista dentro de un Layout maestro
     * 
     * @param string $view Ruta relativa de la vista (ej. 'home', 'courses/nec4')
     * @param array $data Datos que se pasarán a la vista
     * @param string|null $layout Nombre del layout (por defecto 'main')
     */
    public static function render(string $view, array $data = [], ?string $layout = 'main'): void {
        $baseDir = dirname(__DIR__) . '/Views/';
        $viewFile = $baseDir . 'pages/' . ltrim($view, '/') . '.php';

        if (!file_exists($viewFile)) {
            throw new \Exception("La vista '{$viewFile}' no existe.");
        }

        // Extraer variables para hacerlas accesibles en la vista
        extract($data);

        // Capturar el contenido de la vista en el buffer
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        // Si se especificó un layout maestro, renderizarlo inyectando $content
        if ($layout) {
            $layoutFile = $baseDir . 'layouts/' . $layout . '.php';
            if (!file_exists($layoutFile)) {
                throw new \Exception("El layout '{$layoutFile}' no existe.");
            }
            require $layoutFile;
        } else {
            echo $content;
        }
    }

    /**
     * Incluye un componente parcial reutilizable
     * 
     * @param string $partial Nombre del parcial (ej. 'navbar', 'footer', 'toast')
     * @param array $data Datos adicionales para el parcial
     */
    public static function partial(string $partial, array $data = []): void {
        $baseDir = dirname(__DIR__) . '/Views/';
        $partialFile = $baseDir . 'partials/' . ltrim($partial, '/') . '.php';

        if (file_exists($partialFile)) {
            extract($data);
            require $partialFile;
        }
    }

    /**
     * Helper para sanitizar cadenas en HTML
     */
    public static function e($value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

