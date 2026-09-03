<?php
/**
 * =============================================================================
 * PMO SOLUTIONS - FRONT CONTROLLER (index.php)
 * =============================================================================
 * 
 * Punto de entrada único para la aplicación web bajo el patrón arquitectónico
 * Modelo-Vista-Controlador (MVC).
 * 
 * Flujo de ejecución:
 * 1. Inicialización de constantes y configuración.
 * 2. Registro del Autoloader PSR-4 nativo.
 * 3. Definición y mapeo de rutas RESTful.
 * 4. Despacho hacia el Controlador y Método correspondiente.
 */

// 1. Definición de constante de acceso seguro
if (!defined('PMO_APP_ACCESS')) {
    define('PMO_APP_ACCESS', true);
}

// 2. Carga y registro del Autoloader nativo
require_once __DIR__ . '/app/Core/Autoloader.php';
App\Core\Autoloader::register();

// 3. Inicialización del Enrutador
$router = new App\Core\Router();

// -----------------------------------------------------------------------------
// RUTAS PÚBLICAS PRINCIPALES
// -----------------------------------------------------------------------------
$router->get('/', [App\Controllers\HomeController::class, 'index']);
$router->get('/capacitaciones', [App\Controllers\CourseController::class, 'index']);

// Rutas de Cursos y Especializaciones Individuales
$router->get('/dab-jrd', [App\Controllers\CourseController::class, 'dabJrd']);
$router->get('/analisis-forense', [App\Controllers\CourseController::class, 'analisisForense']);
$router->get('/nec4', [App\Controllers\CourseController::class, 'nec4']);
$router->get('/vdc-bim', [App\Controllers\CourseController::class, 'vdcBim']);
$router->get('/contratos-estado', [App\Controllers\CourseController::class, 'contratosEstado']);
$router->get('/compliance', [App\Controllers\CourseController::class, 'compliance']);
$router->get('/primavera-p6', [App\Controllers\CourseController::class, 'primaveraP6']);
$router->get('/riesgos-pmi', [App\Controllers\CourseController::class, 'riesgosPmi']);
$router->get('/analisis-cuantitativo', [App\Controllers\CourseController::class, 'analisisCuantitativo']);
$router->get('/eventos-compensables', [App\Controllers\CourseController::class, 'eventosCompensables']);

// Ruta dinámica para cursos
$router->get('/curso/{slug}', [App\Controllers\CourseController::class, 'detail']);

// -----------------------------------------------------------------------------
// RUTAS DE FORMULARIOS & APIS (CONTACTO Y RECLAMACIONES)
// -----------------------------------------------------------------------------
// Contacto
$router->get('/contacto', [App\Controllers\ContactController::class, 'index']);
$router->post('/contacto', [App\Controllers\ContactController::class, 'submit']);
$router->post('/api/send-contact', [App\Controllers\ContactController::class, 'submit']);
$router->post('/backend/send-contact.php', [App\Controllers\ContactController::class, 'submit']); // Retrocompatibilidad

// Libro de Reclamaciones (Ley N° 29571)
$router->get('/libro-de-reclamaciones', [App\Controllers\ClaimController::class, 'index']);
$router->post('/libro-de-reclamaciones', [App\Controllers\ClaimController::class, 'submit']);
$router->post('/api/submit-claim', [App\Controllers\ClaimController::class, 'submit']);
$router->post('/backend/submit-claim.php', [App\Controllers\ClaimController::class, 'submit']); // Retrocompatibilidad

// -----------------------------------------------------------------------------
// RUTAS LEGALES & NORMATIVAS
// -----------------------------------------------------------------------------
$router->get('/terminos-y-condiciones', [App\Controllers\LegalController::class, 'terms']);
$router->get('/terminos', [App\Controllers\LegalController::class, 'terms']); // Alias amigable

// -----------------------------------------------------------------------------
// MANEJADOR DE RUTAS NO ENCONTRADAS (ERROR 404)
// -----------------------------------------------------------------------------
$router->setNotFoundHandler([App\Controllers\ErrorController::class, 'notFound']);

// 4. Despachar la petición
$router->dispatch();
