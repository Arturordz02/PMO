<?php
/**
 * PMO SOLUTIONS - Proxy de Configuración
 * Redirige a app/Config/config.php para compatibilidad de rutas de configuración.
 */
if (!defined('PMO_APP_ACCESS')) {
    define('PMO_APP_ACCESS', true);
}
return require dirname(__DIR__) . '/app/Config/config.php';

