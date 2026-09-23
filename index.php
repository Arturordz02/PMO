<?php
/**
 * =============================================================================
 * PMO SOLUTIONS - FRONT CONTROLLER v2.0 (index.php)
 * =============================================================================
 *
 * Punto de entrada único para la aplicación web bajo el patrón MVC.
 *
 * Mejoras v2.0:
 *  - Registro del ErrorHandler global (graceful degradation)
 *  - Limpieza ligera de caché expirada (1/20 requests)
 *  - Nuevas rutas: Módulo de Habilidades Blandas (GET + POST + API)
 *  - Configuración de modo de minificación según entorno
 */

// 1. Soporte para servidor web embebido de PHP (CLI server) con headers de caché y CSP para assets
if (php_sapi_name() === 'cli-server') {
    if (empty(getenv('PMO_APP_ENV'))) {
        putenv('PMO_APP_ENV=development');
        $_ENV['PMO_APP_ENV'] = 'development';
    }
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $fullPath = __DIR__ . $path;
    if ($path !== '/' && file_exists($fullPath) && !is_dir($fullPath)) {
        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $staticExts = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'avif', 'woff', 'woff2', 'ttf', 'otf', 'ico'];
        if (in_array($ext, $staticExts, true)) {
            $query = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY) ?? '';
            if (preg_match('/v=[0-9a-zA-Z_.-]+/', $query)) {
                header('Cache-Control: public, max-age=31536000, immutable');
            } else {
                header('Cache-Control: public, max-age=3600, must-revalidate');
            }

            $mimeTypes = [
                'css'   => 'text/css; charset=utf-8',
                'js'    => 'application/javascript; charset=utf-8',
                'png'   => 'image/png',
                'jpg'   => 'image/jpeg',
                'jpeg'  => 'image/jpeg',
                'gif'   => 'image/gif',
                'svg'   => 'image/svg+xml',
                'webp'  => 'image/webp',
                'avif'  => 'image/avif',
                'woff'  => 'font/woff',
                'woff2' => 'font/woff2',
                'ttf'   => 'font/ttf',
                'otf'   => 'font/otf',
                'ico'   => 'image/x-icon',
            ];
            if (isset($mimeTypes[$ext])) {
                header('Content-Type: ' . $mimeTypes[$ext]);
            }
            readfile($fullPath);
            exit(0);
        }
        return false;
    }
}

// 2. Definición de constante de acceso seguro
if (!defined('PMO_APP_ACCESS')) {
    define('PMO_APP_ACCESS', true);
}

// 2. Carga y registro del Autoloader nativo PSR-4
require_once __DIR__ . '/app/Core/Autoloader.php';
App\Core\Autoloader::register();

// 3. Cargar configuración global
$config = [];
$configFile = __DIR__ . '/app/Config/config.php';
if (file_exists($configFile)) {
    $config = require $configFile;
}

// Configurar zona horaria canónica de PHP tan pronto como se carga la configuración
date_default_timezone_set($config['app']['timezone'] ?? 'America/Lima');

// 4. Registrar el manejador global de errores y excepciones (graceful degradation)
App\Core\ErrorHandler::register($config);

// 5. Configurar modo de vista según entorno
$isDebug = ($config['app']['environment'] ?? 'production') === 'development';
App\Core\View::setMinifyHtml(!$isDebug);

// 6. Limpieza ligera de caché expirada (probabilística, no bloquea el request)
App\Core\LogArchiver::quickClean();

// 7. Inicialización del Enrutador
$router = new App\Core\Router();

// =============================================================================
// RUTAS PÚBLICAS PRINCIPALES
// =============================================================================
$router->get('/', [App\Controllers\HomeController::class, 'index']);
$router->get('/capacitaciones', [App\Controllers\CourseController::class, 'index']);

// Rutas de Cursos y Especializaciones Individuales
$router->get('/dab-jrd',                [App\Controllers\CourseController::class, 'dabJrd']);
$router->get('/analisis-forense',       [App\Controllers\CourseController::class, 'analisisForense']);
$router->get('/nec4',                   [App\Controllers\CourseController::class, 'nec4']);
$router->get('/vdc-bim',               [App\Controllers\CourseController::class, 'vdcBim']);
$router->get('/contratos-estado',       [App\Controllers\CourseController::class, 'contratosEstado']);
$router->get('/compliance',             [App\Controllers\CourseController::class, 'compliance']);
$router->get('/primavera-p6',           [App\Controllers\CourseController::class, 'primaveraP6']);
$router->get('/riesgos-pmi',            [App\Controllers\CourseController::class, 'riesgosPmi']);
$router->get('/analisis-cuantitativo', [App\Controllers\CourseController::class, 'analisisCuantitativo']);
$router->get('/eventos-compensables',  [App\Controllers\CourseController::class, 'eventosCompensables']);

// Ruta dinámica para cursos (slug arbitrario)
$router->get('/curso/{slug}', [App\Controllers\CourseController::class, 'detail']);

// =============================================================================
// MÓDULO DE EVALUACIÓN DE HABILIDADES BLANDAS (NUEVO v2.0)
// =============================================================================

// Vista principal de evaluación (formulario interactivo)
$router->get('/evaluacion-habilidades', [App\Controllers\SoftSkillsController::class, 'index']);

// API: Procesar evaluación (recibe respuestas y retorna reporte JSON)
$router->post('/api/evaluacion-habilidades', [App\Controllers\SoftSkillsController::class, 'submit']);

// API: Obtener catálogo de competencias (preguntas sin puntajes)
$router->get('/api/evaluacion-habilidades/catalogo', [App\Controllers\SoftSkillsController::class, 'getCatalog']);
$router->get('/api/evaluacion-habilidades/catalog',  [App\Controllers\SoftSkillsController::class, 'getCatalog']);

// API: Consultar reporte por código de evaluación (controlado por feature flag y SoftSkillsController::getReport)
$router->get('/api/evaluacion-habilidades/{codigo}', [App\Controllers\SoftSkillsController::class, 'getReport']);


// =============================================================================
// RUTAS ADMINISTRATIVAS & AUTENTICACIÓN (CONTROLADO POR FEATURE FLAG)
// =============================================================================
if (!empty($config['features']['admin_enabled'])) {
    // Vista: Listado paginado de evaluaciones (uso administrativo protegido)
    $router->get('/evaluacion-habilidades/resultados', [App\Controllers\SoftSkillsController::class, 'listResults']);
    $router->get('/admin/login',   [App\Controllers\AuthController::class, 'showLogin']);
    $router->post('/admin/login',  [App\Controllers\AuthController::class, 'login']);
    $router->post('/admin/logout', [App\Controllers\AuthController::class, 'logout']);
}

// =============================================================================
// RUTAS DE FORMULARIOS & APIS (CONTACTO Y RECLAMACIONES)
// =============================================================================

// Contacto
$router->get('/contacto',                    [App\Controllers\ContactController::class, 'index']);
$router->post('/contacto/submit',            [App\Controllers\ContactController::class, 'submit']);

// Libro de Reclamaciones (Ley N° 29571)
$router->get('/libro-de-reclamaciones',      [App\Controllers\ClaimController::class, 'index']);
$router->post('/reclamaciones/submit',       [App\Controllers\ClaimController::class, 'submit']);

// Páginas Legales e Institucionales (Públicas)
$router->get('/terminos-y-condiciones',      [App\Controllers\LegalController::class, 'terminos']);
$router->get('/politica-de-privacidad',      [App\Controllers\LegalController::class, 'privacidad']);

// =============================================================================
// MANEJADOR DE RUTAS NO ENCONTRADAS (ERROR 404)
// =============================================================================
$router->setNotFoundHandler([App\Controllers\ErrorController::class, 'notFound']);

// 8. Despachar la petición
$router->dispatch();
