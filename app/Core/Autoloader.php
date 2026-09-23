<?php
namespace App\Core;

/**
 * PMO SOLUTIONS - Autoloader PSR-4 Nativo
 * 
 * Permite cargar automáticamente las clases de la aplicación según su Namespace
 * sin requerir dependencias externas ni Composer.
 * 
 * Ejemplo:
 *   App\Controllers\HomeController -> app/Controllers/HomeController.php
 *   App\Core\Router                -> app/Core/Router.php
 */
class Autoloader {

    /**
     * Registra el autoloader en la pila de autoload de PHP
     */
    public static function register(): void {
        spl_autoload_register([__CLASS__, 'load']);
    }

    /**
     * Resuelve y carga el archivo correspondiente a la clase
     * 
     * @param string $class Nombre completo de la clase con namespace
     */
    public static function load(string $class): void {
        $prefix = 'App\\';
        $baseDir = dirname(__DIR__) . DIRECTORY_SEPARATOR;

        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    }
}

