<?php
/**
 * PMO SOLUTIONS — Suite de Pruebas: Limpieza de Archivos Heredados y Paquete de Producción
 *
 * Cobertura de pruebas:
 * 1. Auditoría física: Verificación de eliminación de los 21 archivos heredados y duplicados.
 * 2. Integridad de archivos conservados: app/, artisan/, backend/ (schemas, migraciones, .htaccess), storage/ (.htaccess, .gitkeep), js/vendor/ (librerías y licencias).
 * 3. Cobertura de rutas MVC canónicas: 15 vistas públicas y endpoints de API activos retornando HTTP 200/201 sin warnings ni errores PHP.
 * 4. Rutas y alias retirados: Verificación de que POST /contacto, POST /libro-de-reclamaciones, POST /api/send-contact, POST /api/submit-claim, /backend/*.php retornan HTTP 404 a través del Router.
 * 5. Seguridad estricta en backend/.htaccess: Bloqueo completo y ausencia de permisos a scripts retirados (send-contact, submit-claim).
 * 6. Auditoría global de referencias: Cero enlaces rotos o referencias residuales a /frontend/*.html o alias /backend/*.php.
 * 7. Auditoría del Service Worker (sw.js v2.2.0): Precachea exclusivamente archivos existentes, fallback offline HTML sin scripts/eventos inline (CSP compliant), y retorno JSON para APIs/formularios.
 * 8. Generador reproducible en artisan/create_production_zip.php:
 *    - Ejecución en directorio temporal aislado.
 *    - Validación del código de salida ($exitCode === 0).
 *    - Inspección y validación estricta de los ZIPs recién generados.
 *    - Exclusión estricta de .env, tests/, frontend/, .git/, logs/caché/outbox de runtime en Producción.
 *    - Inclusión de código fuente completo, tests y generador en Cierre.
 * 9. Prueba de Portabilidad Aislada: Copia el proyecto a una ruta temporal distinta y verifica que artisan/create_production_zip.php genera ambos ZIPs sin depender de la ruta original.
 */

declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

putenv('PMO_APP_ENV=development');
$_ENV['PMO_APP_ENV'] = 'development';
putenv('PMO_TEST_RUNNER=1');
$_ENV['PMO_TEST_RUNNER'] = '1';
define('PMO_TEST_RUNNER', true);

if (!class_exists('ZipArchive') && file_exists(dirname(PHP_BINARY) . '/ext/php_zip.dll')) {
    $cmd = sprintf('"%s" -d extension=zip "%s"', PHP_BINARY, __FILE__);
    passthru($cmd, $exitCode);
    exit($exitCode);
}

class ProjectCleanupTest {

    private int $passed = 0;
    private int $failed = 0;
    private int $skipped = 0;
    private string $rootDir;

    public function __construct() {
        $this->rootDir = str_replace('\\', '/', dirname(__DIR__));
    }

    public function run(): void {
        echo "\n" . str_repeat('=', 70) . "\n";
        echo " SUITE DE PRUEBAS: LIMPIEZA DE ARCHIVOS Y PAQUETE PRODUCCIÓN\n";
        echo str_repeat('=', 70) . "\n\n";

        $this->testLegacyFilesPhysicalAbsence();
        $this->testRetainedCanonicalFilesPresence();
        $this->testMvcRoutesAndCanonicalApis();
        $this->testRetiredRoutesAndAliasesReturn404();
        $this->testBackendHtaccessHardening();
        $this->testGlobalReferencesAndBrokenLinks();
        $this->testServiceWorkerV22Integrity();
        $this->testExternalEnrollmentLinksRegression();
        $this->testSitemapAndRobotsSeoCompliance();
        $this->testPackagerInIsolatedTempDirectory();
        $this->testPackagerPortabilityInClonedDirectory();

        echo "\n" . str_repeat('=', 70) . "\n";
        echo " RESUMEN: LIMPIEZA Y PAQUETE DE PRODUCCIÓN\n";
        echo str_repeat('=', 70) . "\n";
        echo " Total tests: " . ($this->passed + $this->failed + $this->skipped) .
             " | Pasados: {$this->passed}" .
             " | Fallados: {$this->failed}" .
             " | Omitidos (SKIP): {$this->skipped}\n\n";

        if ($this->failed > 0) {
            echo "✖ SE ENCONTRARON FALLOS EN LA SUITE DE LIMPIEZA / PAQUETE.\n\n";
            exit(1);
        } else {
            echo "✔ TODAS LAS PRUEBAS DE LIMPIEZA Y PAQUETE PASARON AL 100%.\n\n";
        }
    }

    private function assert(bool $condition, string $message): void {
        if ($condition) {
            $this->passed++;
            echo "  ✔ [PASS] {$message}\n";
        } else {
            $this->failed++;
            echo "  ✖ [FAIL] {$message}\n";
        }
    }

    private function skip(string $message, string $reason): void {
        $this->skipped++;
        echo "  ↷ [SKIP] {$message} -> {$reason}\n";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 1. Auditoría Física: Eliminación de Archivos Heredados y Duplicados
    // ─────────────────────────────────────────────────────────────────────────
    private function testLegacyFilesPhysicalAbsence(): void {
        echo "--- 1. Eliminación Física de Archivos Heredados y Duplicados ---\n";

        $legacyFiles = [
            'backend/Database.php',
            'backend/Security.php',
            'backend/SmtpMailer.php',
            'backend/config.php',
            'backend/config.example.php',
            'backend/tests/SecurityTest.php',
            'frontend/404.html',
            'frontend/index.html',
            'frontend/capacitaciones.html',
            'frontend/contacto.html',
            'frontend/libro-de-reclamaciones.html',
            'frontend/nec4.html',
            'frontend/primavera-p6.html',
            'frontend/dab-jrd.html',
            'frontend/vdc-bim.html',
            'frontend/contratos-estado.html',
            'frontend/compliance.html',
            'frontend/riesgos-pmi.html',
            'frontend/analisis-cuantitativo.html',
            'frontend/analisis-forense.html',
            'frontend/eventos-compensables.html'
        ];

        foreach ($legacyFiles as $file) {
            $exists = file_exists($this->rootDir . '/' . $file);
            $this->assert(!$exists, "Archivo heredado eliminado del disco: {$file}");
        }

        require_once $this->rootDir . '/artisan/PackagingPolicy.php';
        foreach (\Artisan\PackagingPolicy::FORBIDDEN_USER_FILES as $userFile) {
            $exists = file_exists($this->rootDir . '/' . $userFile);
            $this->assert(!$exists, "Archivo del prototipo de usuarios eliminado físicamente del disco: {$userFile}");
        }

        $this->assert(!is_dir($this->rootDir . '/frontend'), "Directorio frontend/ eliminado completamente");
        $this->assert(!is_dir($this->rootDir . '/backend/tests'), "Directorio backend/tests/ eliminado completamente");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 2. Integridad de Archivos Conservados
    // ─────────────────────────────────────────────────────────────────────────
    private function testRetainedCanonicalFilesPresence(): void {
        echo "\n--- 2. Integridad y Presencia de Archivos Canónicos Conservados ---\n";

        $criticalFiles = [
            // Núcleo MVC
            'app/Config/config.php',
            'app/Core/Autoloader.php',
            'app/Core/Router.php',
            'app/Core/Controller.php',
            'app/Core/Model.php',
            'app/Core/View.php',
            'app/Core/Security.php',
            'app/Core/Csrf.php',
            'app/Core/Database.php',
            'app/Core/QueryBuilder.php',
            'app/Core/SmtpMailer.php',
            'app/Core/EmailOutbox.php',
            'app/Core/Cache.php',
            'app/Core/CircuitBreaker.php',
            'app/Core/RetryHandler.php',
            'app/Core/ErrorHandler.php',
            'app/Core/LogArchiver.php',
            'app/Core/ImageOptimizer.php',
            'app/Core/Idempotency.php',
            'app/Core/Env.php',
            // Controladores & Modelos
            'app/Controllers/HomeController.php',
            'app/Controllers/CourseController.php',
            'app/Controllers/ContactController.php',
            'app/Controllers/ClaimController.php',
            'app/Controllers/SoftSkillsController.php',
            'app/Controllers/ErrorController.php',
            'app/Models/SoftSkillsModel.php',
            // Vistas principales
            'app/Views/layouts/main.php',
            'app/Views/pages/home.php',
            'app/Views/pages/404.php',
            'app/Views/pages/contacto.php',
            'app/Views/pages/capacitaciones.php',
            'app/Views/pages/libro-de-reclamaciones.php',
            'app/Views/pages/evaluacion-habilidades.php',
            // Tareas CLI & Herramientas
            'artisan/process-outbox.php',
            'artisan/archive-logs.php',
            'artisan/create_production_zip.php',
            'artisan/PackagingPolicy.php',
            // Base de datos canónica y migraciones
            'backend/schema_v2.sql',
            'backend/schema.sql',
            'backend/migrations/001_add_report_access_token_hash.sql',
            'backend/migrations/001_rollback.sql',
            'backend/migrations/002_add_idempotency_and_outbox.sql',
            'backend/migrations/002_rollback.sql',
            'backend/.htaccess',
            // Chatbot
            'modules/chatbot/chatbot-view.php',
            'modules/chatbot/chatbot.css',
            'modules/chatbot/chatbot.js',
            'modules/chatbot/config.php',
            'modules/chatbot/knowledge.json',
            'modules/chatbot/.htaccess',
            'modules/chatbot/tests/ChatbotTest.php',
            // Assets y Vendor
            'css/styles.css',
            'js/main.js',
            'js/sw.js',
            'js/core/utils.js',
            'js/modules/soft-skills.js',
            'js/vendor/chart.umd.min.js',
            'js/vendor/jspdf.umd.min.js',
            'js/vendor/LICENSE-chartjs.txt',
            'js/vendor/LICENSE-jspdf.txt',
            'js/vendor/README.md',
            // Storage
            'storage/.htaccess',
            'storage/logs/.gitkeep',
            'storage/cache/.gitkeep',
            'storage/outbox/.gitkeep',
            'storage/outbox/.htaccess',
            // Configuración y metadatos
            '.htaccess',
            'index.php',
            'robots.txt',
            'sitemap.xml',
            'README.md',
            '.env.example'
        ];

        foreach ($criticalFiles as $file) {
            $exists = file_exists($this->rootDir . '/' . $file);
            $this->assert($exists, "Archivo canónico esencial presente: {$file}");
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 3. Cobertura de Rutas MVC Canónicas
    // ─────────────────────────────────────────────────────────────────────────
    private function testMvcRoutesAndCanonicalApis(): void {
        echo "\n--- 3. Verificación de Rutas Públicas MVC y Endpoints de API ---\n";

        $routesToTest = [
            '/' => 'Home',
            '/capacitaciones' => 'Capacitaciones',
            '/contacto' => 'Contacto',
            '/libro-de-reclamaciones' => 'Libro de Reclamaciones',
            '/evaluacion-habilidades' => 'Evaluación de Habilidades',
            '/nec4' => 'NEC4',
            '/primavera-p6' => 'Primavera P6',
            '/dab-jrd' => 'DAB & JRD',
            '/vdc-bim' => 'VDC & BIM',
            '/contratos-estado' => 'Contratos del Estado',
            '/compliance' => 'Compliance',
            '/riesgos-pmi' => 'Riesgos PMI',
            '/analisis-cuantitativo' => 'Análisis Cuantitativo',
            '/analisis-forense' => 'Análisis Forense',
            '/eventos-compensables' => 'Eventos Compensables',
        ];

        $php = PHP_BINARY;

        foreach ($routesToTest as $uri => $label) {
            $script = '<?php
define("PMO_APP_ACCESS", true);
putenv("PMO_APP_ENV=development");
$_ENV["PMO_APP_ENV"] = "development";
$_SERVER["REQUEST_METHOD"] = "GET";
$_SERVER["REQUEST_URI"] = "' . $uri . '";
$_SERVER["HTTP_HOST"] = "localhost";

require_once "' . $this->rootDir . '/app/Core/Autoloader.php";
\App\Core\Autoloader::register();

require "' . $this->rootDir . '/index.php";
';
            $tmp = sys_get_temp_dir() . '/pmo_test_route_' . md5($uri) . '.php';
            file_put_contents($tmp, $script);
            $output = (string)shell_exec("\"{$php}\" \"{$tmp}\" 2>&1");
            if (file_exists($tmp)) {
                unlink($tmp);
            }

            $hasErrors = preg_match('/(Fatal error|Parse error|Warning:|Notice:|Deprecated:)/i', $output);
            $hasHtml = str_contains($output, '<!DOCTYPE html') || str_contains($output, '<html');

            $this->assert(!$hasErrors && $hasHtml, "Ruta MVC {$uri} ({$label}) renderiza HTTP 200 sin errores PHP");
        }

        // Test API Catálogo
        $scriptCatalog = '<?php
define("PMO_APP_ACCESS", true);
putenv("PMO_APP_ENV=development");
$_ENV["PMO_APP_ENV"] = "development";
$_SERVER["REQUEST_METHOD"] = "GET";
$_SERVER["REQUEST_URI"] = "/api/evaluacion-habilidades/catalogo";
$_SERVER["HTTP_HOST"] = "localhost";

require_once "' . $this->rootDir . '/app/Core/Autoloader.php";
\App\Core\Autoloader::register();

require "' . $this->rootDir . '/index.php";
';
        $tmpCat = sys_get_temp_dir() . '/pmo_test_cat.php';
        file_put_contents($tmpCat, $scriptCatalog);
        $outCat = (string)shell_exec("\"{$php}\" \"{$tmpCat}\" 2>&1");
        if (file_exists($tmpCat)) {
            unlink($tmpCat);
        }

        $jsonCat = json_decode($outCat, true);
        $isValidCatalog = is_array($jsonCat) && ($jsonCat['success'] ?? false) === true && isset($jsonCat['catalog']) && isset($jsonCat['total_questions']) && isset($jsonCat['maturity_levels']);
        $this->assert($isValidCatalog, "API Catálogo /api/evaluacion-habilidades/catalogo (getCatalog) retorna estructura JSON válida con competencias y niveles");

        // Test Rutas Canónicas POST (exigen CSRF e Idempotencia)
        $scriptCanonPost = '<?php
define("PMO_APP_ACCESS", true);
putenv("PMO_APP_ENV=development");
$_ENV["PMO_APP_ENV"] = "development";
$_SERVER["REQUEST_METHOD"] = "POST";
$_SERVER["REQUEST_URI"] = "/contacto/submit";
$_SERVER["HTTP_HOST"] = "localhost";
$_SERVER["HTTP_ACCEPT"] = "application/json";

require_once "' . $this->rootDir . '/app/Core/Autoloader.php";
\App\Core\Autoloader::register();

\App\Core\Security::startSecureSession();
$token = \App\Core\Csrf::getToken();
$_SERVER["HTTP_X_CSRF_TOKEN"] = $token;
$_SERVER["HTTP_IDEMPOTENCY_KEY"] = "idemp_test_1234567890123456";

require "' . $this->rootDir . '/index.php";
';
        $tmpCanon = sys_get_temp_dir() . '/pmo_test_canon.php';
        file_put_contents($tmpCanon, $scriptCanonPost);
        $outCanon = (string)shell_exec("\"{$php}\" \"{$tmpCanon}\" 2>&1");
        if (file_exists($tmpCanon)) {
            unlink($tmpCanon);
        }

        $jsonCanon = json_decode($outCanon, true);
        $this->assert(isset($jsonCanon['success']) && $jsonCanon['success'] === false && isset($jsonCanon['errors']), "Ruta canónica POST /contacto/submit procesada por ContactController (HTTP 422 en payload vacío)");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 4. Rutas y Alias Retirados Retornan HTTP 404
    // ─────────────────────────────────────────────────────────────────────────
    private function testRetiredRoutesAndAliasesReturn404(): void {
        echo "\n--- 4. Verificación de Rutas Retiradas y Alias -> HTTP 404 ---\n";

        $retiredRoutes = [
            '/contacto',
            '/libro-de-reclamaciones',
            '/api/send-contact',
            '/api/submit-claim',
            '/backend/send-contact.php',
            '/backend/submit-claim.php'
        ];

        $php = PHP_BINARY;

        foreach ($retiredRoutes as $uri) {
            $script = '<?php
define("PMO_APP_ACCESS", true);
putenv("PMO_APP_ENV=development");
$_ENV["PMO_APP_ENV"] = "development";
$_SERVER["REQUEST_METHOD"] = "POST";
$_SERVER["REQUEST_URI"] = "' . $uri . '";
$_SERVER["HTTP_HOST"] = "localhost";
$_SERVER["HTTP_ACCEPT"] = "application/json";

require_once "' . $this->rootDir . '/app/Core/Autoloader.php";
\App\Core\Autoloader::register();

require "' . $this->rootDir . '/index.php";
';
            $tmp = sys_get_temp_dir() . '/pmo_test_obs_' . md5($uri) . '.php';
            file_put_contents($tmp, $script);
            $output = (string)shell_exec("\"{$php}\" \"{$tmp}\" 2>&1");
            if (file_exists($tmp)) {
                unlink($tmp);
            }

            $json = json_decode($output, true);
            $is404 = (isset($json['error']) && str_contains(strtolower($json['error']), 'no encontrada')) ||
                     (isset($json['message']) && str_contains(strtolower($json['message']), 'no encontrada')) ||
                     str_contains($output, '404');

            $this->assert($is404, "Ruta POST retirada {$uri} retorna HTTP 404 a través del Router");
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 5. Seguridad Estricta en backend/.htaccess
    // ─────────────────────────────────────────────────────────────────────────
    private function testBackendHtaccessHardening(): void {
        echo "\n--- 5. Seguridad de Directorio en backend/.htaccess ---\n";

        $htaccess = (string)file_get_contents($this->rootDir . '/backend/.htaccess');

        $this->assert(str_contains($htaccess, 'Require all denied') || str_contains($htaccess, 'Deny from all'), "backend/.htaccess bloquea completamente el acceso web");
        $this->assert(!str_contains($htaccess, 'send-contact.php'), "backend/.htaccess NO contiene excepciones para send-contact.php");
        $this->assert(!str_contains($htaccess, 'submit-claim.php'), "backend/.htaccess NO contiene excepciones para submit-claim.php");
        $this->assert(!str_contains($htaccess, 'Require all granted') && !str_contains($htaccess, 'Allow from all'), "backend/.htaccess NO concede permisos públicos de ningún tipo");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 6. Auditoría Global de Referencias Residuales
    // ─────────────────────────────────────────────────────────────────────────
    private function testGlobalReferencesAndBrokenLinks(): void {
        echo "\n--- 6. Auditoría Global de Referencias Residuales ---\n";

        $scanDirs = ['app', 'css', 'js', 'artisan'];
        $brokenPatterns = [
            '/frontend/' => 'Referencia a ruta o carpeta frontend/',
            'backend/Database.php' => 'Referencia a script backend/Database.php',
            'backend/Security.php' => 'Referencia a script backend/Security.php',
            'backend/SmtpMailer.php' => 'Referencia a script backend/SmtpMailer.php',
            'backend/config.php' => 'Referencia a archivo backend/config.php',
            'backend/send-contact.php' => 'Referencia a endpoint backend/send-contact.php',
            'backend/submit-claim.php' => 'Referencia a endpoint backend/submit-claim.php'
        ];

        foreach ($brokenPatterns as $pattern => $description) {
            $foundIn = [];
            foreach ($scanDirs as $dir) {
                $dirPath = $this->rootDir . '/' . $dir;
                if (!is_dir($dirPath)) continue;

                $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dirPath));
                foreach ($iterator as $file) {
                    if ($file->isFile() && in_array($file->getExtension(), ['php', 'js', 'css', 'html'])) {
                        $relPath = str_replace($this->rootDir . '/', '', str_replace('\\', '/', $file->getPathname()));
                        if ($relPath === 'artisan/quality-gate.php' || $relPath === 'artisan/create_production_zip.php') {
                            continue;
                        }
                        $content = (string)file_get_contents($file->getPathname());
                        if (str_contains($content, $pattern)) {
                            $foundIn[] = $relPath;
                        }
                    }
                }
            }

            $this->assert(empty($foundIn), "Ausencia de '{$pattern}' ({$description})" . (!empty($foundIn) ? " (Encontrado en: " . implode(', ', $foundIn) . ")" : ""));
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 7. Auditoría del Service Worker (sw.js v2.2.0)
    // ─────────────────────────────────────────────────────────────────────────
    private function testServiceWorkerV22Integrity(): void {
        echo "\n--- 7. Auditoría del Service Worker (sw.js v2.2.0) ---\n";

        $swJs = (string)file_get_contents($this->rootDir . '/js/sw.js');

        $this->assert(str_contains($swJs, "CACHE_VERSION    = 'pmo-v2.2.0'"), "Versión del Service Worker actualizada a pmo-v2.2.0");
        $this->assert(!str_contains($swJs, '/frontend/404.html'), "sw.js no contiene referencias a /frontend/404.html");
        $this->assert(!str_contains($swJs, '/frontend/'), "sw.js no contiene ninguna ruta hacia frontend/");
        $this->assert(!str_contains($swJs, 'onclick='), "Fallback HTML en sw.js no contiene eventos inline onclick");
        $this->assert(!str_contains($swJs, '<script'), "Fallback HTML en sw.js no contiene scripts inline");
        $this->assert(str_contains($swJs, 'networkWithHtmlFallback'), "Navegaciones HTML entregan offlineFallback() ante fallo de red");
        $this->assert(str_contains($swJs, 'networkOnlyJson'), "APIs y formularios entregan JSON estructurado ante fallo de red");

        // Extraer URLs de PRECACHE_ASSETS y verificar que todas existen físicamente
        if (preg_match('/const\s+PRECACHE_ASSETS\s*=\s*\[(.*?)\];/s', $swJs, $m)) {
            preg_match_all('/\'([^\']+)\'|"([^"]+)"/', $m[1], $matches);
            $urls = array_filter(array_merge($matches[1], $matches[2]));

            $allExist = true;
            $missing = [];
            foreach ($urls as $url) {
                $filePath = $this->rootDir . '/' . ltrim($url, '/');
                if (!file_exists($filePath)) {
                    $allExist = false;
                    $missing[] = $url;
                }
            }

            $this->assert($allExist, "Todos los assets de PRECACHE_ASSETS existen físicamente en disco" . (!empty($missing) ? " (Faltantes: " . implode(', ', $missing) . ")" : ""));
        } else {
            $this->assert(false, "No se pudo extraer PRECACHE_ASSETS de sw.js");
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 7b. Test de Regresión: Enlaces Externos de Matrícula y Contacto
    // ─────────────────────────────────────────────────────────────────────────
    private function testExternalEnrollmentLinksRegression(): void {
        echo "\n--- 7b. Test de Regresión: Enlaces Externos de Matrícula y Contacto ---\n";

        $expectedLinks = [
            'app/Views/pages/courses/analisis-cuantitativo.php' => [
                'https://docs.google.com/forms/d/e/1FAIpQLSdPOpAucuZBS-Tu97Nusr-E9pGhRVRmAOwPyNgF-ryInobyKw/viewform',
                'https://api.whatsapp.com/send?phone=51944276649&text=Hola%20PMO%20Solutions,%20deseo%20informaci%C3%B3n%20sobre%20el%20curso%20An%C3%A1lisis%20Cuantitativo%20de%20Riesgos'
            ],
            'app/Views/pages/courses/analisis-forense.php' => [
                'https://docs.google.com/forms/d/e/1FAIpQLSeR3gWqBQP6c1BHrlW0s2MM44HG5uRiNRPYH2R9vweE53Zr9g/viewform',
                'https://docs.google.com/forms/d/e/1FAIpQLSdPOpAucuZBS-Tu97Nusr-E9pGhRVRmAOwPyNgF-ryInobyKw/viewform',
                'https://api.whatsapp.com/send?phone=51944276649&text=Hola%20PMO%20Solutions,%20deseo%20informaci%C3%B3n%20sobre%20el%20curso%20An%C3%A1lisis%20Forense%20de%20Atrasos'
            ],
            'app/Views/pages/courses/compliance.php' => [
                'https://docs.google.com/forms/d/e/1FAIpQLSdEFpjxGQkRDyFAMb0oqys9dLDm20etfkj_CGeTWrhpZgFX1w/viewform',
                'https://docs.google.com/forms/d/e/1FAIpQLSdPOpAucuZBS-Tu97Nusr-E9pGhRVRmAOwPyNgF-ryInobyKw/viewform',
                'https://api.whatsapp.com/send?phone=51944276649&text=Hola%20PMO%20Solutions,%20deseo%20informaci%C3%B3n%20sobre%20el%20curso%20Compliance%20en%20la%20Construcci%C3%B3n'
            ],
            'app/Views/pages/courses/contratos-estado.php' => [
                'https://docs.google.com/forms/d/e/1FAIpQLSdAnt9Kowdtr1YN0H1w1x7GI_Aprp7CuE5htHYPG3fdb-ie5w/viewform',
                'https://docs.google.com/forms/d/e/1FAIpQLSehW88_KPBJ8OIQsGReXkiwCgnhKEtVujT3Nh_Q_ABpuxryTQ/viewform',
                'https://api.whatsapp.com/send?phone=51944276649&text=Hola%20PMO%20Solutions,%20deseo%20informaci%C3%B3n%20sobre%20el%20curso%20Gesti%C3%B3n%20del%20Cambio%20en%20Contratos%20del%20Estado'
            ],
            'app/Views/pages/courses/dab-jrd.php' => [
                'https://docs.google.com/forms/d/e/1FAIpQLSehW88_KPBJ8OIQsGReXkiwCgnhKEtVujT3Nh_Q_ABpuxryTQ/viewform',
                'https://api.whatsapp.com/send?phone=51944276649&text=Hola%20PMO%20Solutions,%20deseo%20informaci%C3%B3n%20sobre%20el%20curso%20Junta%20de%20Resoluci%C3%B3n%20de%20Disputas%20DAB%20-%20JRD'
            ],
            'app/Views/pages/courses/eventos-compensables.php' => [
                'https://docs.google.com/forms/d/e/1FAIpQLSdPOpAucuZBS-Tu97Nusr-E9pGhRVRmAOwPyNgF-ryInobyKw/viewform',
                'https://api.whatsapp.com/send?phone=51944276649&text=Hola%20PMO%20Solutions,%20deseo%20informaci%C3%B3n%20sobre%20el%20curso%20Gesti%C3%B3n%20de%20Eventos%20Compensables%20NEC'
            ],
            'app/Views/pages/courses/nec4.php' => [
                'https://docs.google.com/forms/d/e/1FAIpQLSdPOpAucuZBS-Tu97Nusr-E9pGhRVRmAOwPyNgF-ryInobyKw/viewform',
                'https://docs.google.com/forms/d/e/1FAIpQLSehW88_KPBJ8OIQsGReXkiwCgnhKEtVujT3Nh_Q_ABpuxryTQ/viewform',
                'https://api.whatsapp.com/send?phone=51944276649&text=Hola%20PMO%20Solutions,%20deseo%20informaci%C3%B3n%20sobre%20el%20curso%20Casos%20de%20Aplicaci%C3%B3n%20en%20Contratos%20NEC4'
            ],
            'app/Views/pages/courses/primavera-p6.php' => [
                'https://docs.google.com/forms/d/e/1FAIpQLSdPOpAucuZBS-Tu97Nusr-E9pGhRVRmAOwPyNgF-ryInobyKw/viewform',
                'https://api.whatsapp.com/send?phone=51944276649&text=Hola%20PMO%20Solutions,%20deseo%20informaci%C3%B3n%20sobre%20el%20curso%20Primavera%20P6%20y%20Power%20BI'
            ],
            'app/Views/pages/courses/riesgos-pmi.php' => [
                'https://docs.google.com/forms/d/e/1FAIpQLSeHQ4g60QDw2-BvvPZeF_LC3-DneCmCI81DjTw9nKR_qK5irQ/viewform',
                'https://docs.google.com/forms/d/e/1FAIpQLSehW88_KPBJ8OIQsGReXkiwCgnhKEtVujT3Nh_Q_ABpuxryTQ/viewform',
                'https://api.whatsapp.com/send?phone=51944276649&text=Hola%20PMO%20Solutions,%20deseo%20informaci%C3%B3n%20sobre%20el%20curso%20Gesti%C3%B3n%20de%20Riesgos%20PMI'
            ],
            'app/Views/pages/courses/vdc-bim.php' => [
                'https://docs.google.com/forms/d/e/1FAIpQLScwEvk1Kgxn92CyiHnvfqBmhQPmmwyInupOksUtN8ecEV51KQ/viewform',
                'https://docs.google.com/forms/d/e/1FAIpQLSdPOpAucuZBS-Tu97Nusr-E9pGhRVRmAOwPyNgF-ryInobyKw/viewform',
                'https://api.whatsapp.com/send?phone=51944276649&text=Hola%20PMO%20Solutions,%20deseo%20informaci%C3%B3n%20sobre%20el%20curso%20VDC%20-%20BIM'
            ]
        ];

        foreach ($expectedLinks as $viewFile => $urls) {
            $filePath = $this->rootDir . '/' . $viewFile;
            $this->assert(file_exists($filePath), "Vista de curso existe: {$viewFile}");
            $content = (string)file_get_contents($filePath);

            foreach ($urls as $url) {
                $found = str_contains($content, $url);
                $this->assert($found, "Enlace de matrícula conservado en {$viewFile}: " . substr($url, 0, 60) . '...');
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 8. Verificación de Sitemap XML y Directivas Robots.txt
    // ─────────────────────────────────────────────────────────────────────────
    private function testSitemapAndRobotsSeoCompliance(): void {
        echo "\n--- 8. Verificación de Sitemap XML y Directivas Robots.txt ---\n";

        $sitemapPath = $this->rootDir . '/sitemap.xml';
        $this->assert(file_exists($sitemapPath), "Archivo sitemap.xml presente en la raíz del proyecto");

        libxml_use_internal_errors(true);
        $sitemapXml = simplexml_load_file($sitemapPath);
        $xmlErrors = libxml_get_errors();
        libxml_clear_errors();
        $this->assert($sitemapXml !== false && empty($xmlErrors), "sitemap.xml es un documento XML 100% válido");

        $sitemapUrls = [];
        if ($sitemapXml && isset($sitemapXml->url)) {
            foreach ($sitemapXml->url as $urlNode) {
                $sitemapUrls[] = (string)$urlNode->loc;
            }
        }

        $this->assert(count($sitemapUrls) === 17, "sitemap.xml contiene exactamente 17 URLs públicas (" . count($sitemapUrls) . " encontradas)");

        // 3 rutas nuevas obligatorias
        $this->assert(in_array('https://pmo-solutions.com/evaluacion-habilidades', $sitemapUrls, true), "sitemap.xml contiene https://pmo-solutions.com/evaluacion-habilidades");
        $this->assert(in_array('https://pmo-solutions.com/terminos-y-condiciones', $sitemapUrls, true), "sitemap.xml contiene https://pmo-solutions.com/terminos-y-condiciones");
        $this->assert(in_array('https://pmo-solutions.com/politica-de-privacidad', $sitemapUrls, true), "sitemap.xml contiene https://pmo-solutions.com/politica-de-privacidad");

        // Prohibiciones
        $this->assert(!in_array('https://pmo-solutions.com/politicas-de-privacidad', $sitemapUrls, true), "sitemap.xml excluye la ruta errónea plural /politicas-de-privacidad");
        $this->assert(!in_array('https://pmo-solutions.com/registro', $sitemapUrls, true), "sitemap.xml excluye /registro");
        $this->assert(!in_array('https://pmo-solutions.com/login', $sitemapUrls, true), "sitemap.xml excluye /login");
        $this->assert(!in_array('https://pmo-solutions.com/mi-cuenta', $sitemapUrls, true), "sitemap.xml excluye /mi-cuenta");
        $this->assert(!in_array('https://pmo-solutions.com/logout', $sitemapUrls, true), "sitemap.xml excluye /logout");

        // Robots.txt
        $robotsPath = $this->rootDir . '/robots.txt';
        $this->assert(file_exists($robotsPath), "Archivo robots.txt presente en la raíz del proyecto");
        $robotsContent = (string)file_get_contents($robotsPath);
        $this->assert(
            str_contains($robotsContent, 'Sitemap: https://pmo-solutions.com/sitemap.xml'),
            "robots.txt apunta al sitemap oficial: https://pmo-solutions.com/sitemap.xml"
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 9. Validación del Generador en Directorio Temporal Aislado
    // ─────────────────────────────────────────────────────────────────────────
    private function testPackagerInIsolatedTempDirectory(): void {
        echo "\n--- 9. Verificación del Generador en Directorio Temporal Aislado ---\n";

        $generatorPath = $this->rootDir . '/artisan/create_production_zip.php';
        $this->assert(file_exists($generatorPath), "Generador artisan/create_production_zip.php presente en el proyecto");

        $tempOutDir = sys_get_temp_dir() . '/pmo_zip_test_' . bin2hex(random_bytes(4));
        if (!is_dir($tempOutDir)) {
            mkdir($tempOutDir, 0777, true);
        }

        $cmd = sprintf('"%s" %s"%s" "%s" 2>&1', PHP_BINARY, extension_loaded('zip') ? '' : '-d extension=zip ', $generatorPath, $tempOutDir);
        exec($cmd, $output, $exitCode);

        $this->assert($exitCode === 0, "artisan/create_production_zip.php ejecutado exitosamente con código 0" . ($exitCode !== 0 ? " -> Salida: " . implode(" | ", array_slice($output, -10)) : ""));

        $prodZipPath = $tempOutDir . '/PMO-Solutions-Produccion.zip';
        $cierreZipPath = $tempOutDir . '/PMO-Solutions-Codigo-Fuente.zip';

        $this->assert(file_exists($prodZipPath), "ZIP de producción generado en directorio temporal");
        $this->assert(file_exists($cierreZipPath), "ZIP de cierre generado en directorio temporal");

        require_once $this->rootDir . '/artisan/PackagingPolicy.php';

        // 8.1 Inspeccionar ZIP de producción
        $prodErrors = \Artisan\PackagingPolicy::validateProductionZipArchive($prodZipPath);
        $this->assert(empty($prodErrors), "ZIP de producción cumple al 100% la política centralizada PackagingPolicy" . (!empty($prodErrors) ? " (" . implode('; ', $prodErrors) . ")" : ""));

        $zipProd = new ZipArchive();
        $openedProd = $zipProd->open($prodZipPath);
        $this->assert($openedProd === true, "ZIP de producción recién generado es válido y legible");

        if ($openedProd === true) {
            $entriesProd = [];
            for ($i = 0; $i < $zipProd->numFiles; $i++) {
                $entriesProd[] = str_replace('\\', '/', $zipProd->getNameIndex($i));
            }

            // Validar lista permitida exacta de backend/
            $backendFiles = array_filter($entriesProd, fn($e) => str_starts_with($e, 'backend/') && !str_ends_with($e, '/'));
            $diffBackend = array_diff($backendFiles, \Artisan\PackagingPolicy::BACKEND_PROD_ALLOWLIST);
            $this->assert(empty($diffBackend), "backend/ en producción contiene ÚNICAMENTE la lista permitida exacta (7 archivos)" . (!empty($diffBackend) ? " (Archivos no permitidos: " . implode(', ', $diffBackend) . ")" : ""));

            $missingBackend = array_diff(\Artisan\PackagingPolicy::BACKEND_PROD_ALLOWLIST, $entriesProd);
            $this->assert(empty($missingBackend), "backend/ en producción incluye todos los 7 archivos canónicos obligatorios (schemas, migraciones 001-002, .htaccess)" . (!empty($missingBackend) ? " (Faltantes: " . implode(', ', $missingBackend) . ")" : ""));

            // Validar exclusión estricta de archivos de cuentas de usuario eliminadas
            $forbiddenInProd = array_intersect(\Artisan\PackagingPolicy::FORBIDDEN_USER_FILES, $entriesProd);
            $this->assert(empty($forbiddenInProd), "ZIP de producción excluye estrictamente todos los archivos de cuentas de usuario eliminadas");

            // Validar exclusión estricta de tests y pruebas de módulos
            $hasAnyTests = false;
            foreach ($entriesProd as $e) {
                if (str_starts_with($e, 'tests/') || str_contains($e, '/tests/') || str_ends_with($e, 'Test.php')) {
                    $hasAnyTests = true;
                    break;
                }
            }
            $this->assert(!$hasAnyTests, "ZIP de producción excluye estrictamente tests/ y pruebas internas de módulos (modules/chatbot/tests/ChatbotTest.php)");

            // Validar exclusión de .env pero inclusión de .env.example
            $this->assert(in_array('.env.example', $entriesProd, true), "ZIP de producción incluye .env.example");
            $this->assert(!in_array('.env', $entriesProd, true), "ZIP de producción excluye estrictamente credenciales locales (.env)");

            // Validar inclusión de chatbot funcional
            $this->assert(in_array('modules/chatbot/chatbot-view.php', $entriesProd, true), "ZIP de producción incluye chatbot-view.php");
            $this->assert(in_array('modules/chatbot/chatbot.js', $entriesProd, true), "ZIP de producción incluye chatbot.js");
            $this->assert(in_array('modules/chatbot/chatbot.css', $entriesProd, true), "ZIP de producción incluye chatbot.css");
            $this->assert(in_array('modules/chatbot/config.php', $entriesProd, true), "ZIP de producción incluye chatbot config.php");
            $this->assert(in_array('modules/chatbot/knowledge.json', $entriesProd, true), "ZIP de producción incluye chatbot knowledge.json");
            $this->assert(in_array('modules/chatbot/.htaccess', $entriesProd, true), "ZIP de producción incluye modules/chatbot/.htaccess");

            // Validar ausencia de .git, .vscode, markdown y reportes
            $hasForbiddenMeta = false;
            foreach ($entriesProd as $e) {
                if (
                    str_starts_with($e, '.git/') || str_contains($e, '/.git/') || $e === '.gitignore' ||
                    str_starts_with($e, '.vscode/') || str_contains($e, '/.vscode/') ||
                    str_starts_with($e, '.idea/') || str_contains($e, '/.idea/') ||
                    str_starts_with($e, '.agent/') || str_contains($e, '/.agent/') ||
                    str_ends_with(strtolower($e), '.md') ||
                    str_starts_with($e, 'storage/reports') ||
                    str_starts_with($e, 'PMO-Solutions/')
                ) {
                    $hasForbiddenMeta = true;
                    break;
                }
            }
            $this->assert(!$hasForbiddenMeta, "ZIP de producción excluye estrictamente .git, .vscode, markdown, storage/reports y carpetas contenedoras duplicadas");

            $zipProd->close();
        }

        // 8.2 Inspeccionar ZIP de Cierre
        $cierreErrors = \Artisan\PackagingPolicy::validateClosureZipArchive($cierreZipPath);
        $this->assert(empty($cierreErrors), "ZIP de código fuente cumple al 100% la política centralizada PackagingPolicy" . (!empty($cierreErrors) ? " (" . implode('; ', $cierreErrors) . ")" : ""));

        $zipCierre = new ZipArchive();
        $openedCierre = $zipCierre->open($cierreZipPath);
        $this->assert($openedCierre === true, "ZIP de cierre recién generado es válido y legible");

        if ($openedCierre === true) {
            $entriesCierre = [];
            for ($i = 0; $i < $zipCierre->numFiles; $i++) {
                $entriesCierre[] = str_replace('\\', '/', $zipCierre->getNameIndex($i));
            }
            $zipCierre->close();

            $this->assert(in_array('tests/ProjectCleanupTest.php', $entriesCierre, true), "ZIP de cierre incluye tests/ProjectCleanupTest.php");
            $this->assert(in_array('modules/chatbot/tests/ChatbotTest.php', $entriesCierre, true), "ZIP de cierre incluye modules/chatbot/tests/ChatbotTest.php");
            $this->assert(in_array('tests/browser_test.js', $entriesCierre, true), "ZIP de cierre incluye tests/browser_test.js");
            $this->assert(in_array('backend/schema_v2.sql', $entriesCierre, true), "ZIP de cierre incluye backend/schema_v2.sql");
            $this->assert(!in_array('backend/migrations/003_add_usuarios_table.sql', $entriesCierre, true), "ZIP de cierre excluye migración 003 de usuarios");
            $forbiddenInCierre = array_intersect(\Artisan\PackagingPolicy::FORBIDDEN_USER_FILES, $entriesCierre);
            $this->assert(empty($forbiddenInCierre), "ZIP de cierre excluye estrictamente todos los archivos de cuentas de usuario eliminadas");
            $this->assert(in_array('README.md', $entriesCierre, true), "ZIP de cierre incluye README.md");
            $this->assert(in_array('DEPLOYMENT.md', $entriesCierre, true), "ZIP de cierre incluye DEPLOYMENT.md");
            $this->assert(in_array('.env.example', $entriesCierre, true), "ZIP de cierre incluye .env.example");
            $this->assert(in_array('artisan/create_production_zip.php', $entriesCierre, true), "ZIP de cierre incluye el generador artisan/create_production_zip.php");
            $this->assert(!in_array('.env', $entriesCierre, true), "ZIP de cierre excluye credenciales locales (.env)");
            $this->assert(!in_array('.git', $entriesCierre, true) && !in_array('.git/', $entriesCierre, true), "ZIP de cierre excluye directorio .git");
            $this->assert(!in_array('.vscode', $entriesCierre, true) && !in_array('.vscode/', $entriesCierre, true), "ZIP de cierre excluye directorio .vscode");
            $this->assert(!in_array('storage/reports', $entriesCierre, true) && !in_array('storage/reports/', $entriesCierre, true), "ZIP de cierre excluye storage/reports/");

            // Contar ocurrencias de create_production_zip.php para comprobar que no se agrega dos veces
            $packagerOccurrences = 0;
            foreach ($entriesCierre as $e) {
                if ($e === 'artisan/create_production_zip.php') {
                    $packagerOccurrences++;
                }
            }
            $this->assert($packagerOccurrences === 1, "artisan/create_production_zip.php agregado exactamente una vez en el ZIP de cierre");
        }

        // Limpieza del directorio temporal
        $this->deleteDirectoryRecursive($tempOutDir);
        $this->assert(!is_dir($tempOutDir), "Directorio temporal de prueba de empaquetado eliminado con éxito sin residuos");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 9. Prueba de Portabilidad Aislada (Ejecución en Otra Ubicación)
    // ─────────────────────────────────────────────────────────────────────────
    private function testPackagerPortabilityInClonedDirectory(): void {
        echo "\n--- 9. Prueba de Portabilidad Aislada (Clon en Directorio Temporal) ---\n";

        $tempProjectDir = sys_get_temp_dir() . '/pmo_portable_proj_' . bin2hex(random_bytes(4));
        $tempProjectOut = sys_get_temp_dir() . '/pmo_portable_out_' . bin2hex(random_bytes(4));

        mkdir($tempProjectDir, 0777, true);
        mkdir($tempProjectOut, 0777, true);

        // Copiar estructura completa del proyecto al clon temporal (excluyendo solo .git para velocidad)
        $this->copyDirectoryRecursive($this->rootDir, $tempProjectDir, ['.git']);

        $isolatedGenerator = $tempProjectDir . '/artisan/create_production_zip.php';
        $this->assert(file_exists($isolatedGenerator), "Generador presente en el clon temporal independiente");

        $cmd = sprintf('"%s" %s"%s" "%s" 2>&1', PHP_BINARY, extension_loaded('zip') ? '' : '-d extension=zip ', $isolatedGenerator, $tempProjectOut);
        exec($cmd, $output, $exitCode);

        $this->assert($exitCode === 0, "Generador ejecutado exitosamente en ubicación independiente (Código 0)" . ($exitCode !== 0 ? " -> Salida: " . implode(" | ", array_slice($output, -10)) : ""));

        $isolatedProdZip = $tempProjectOut . '/PMO-Solutions-Produccion.zip';
        $isolatedCierreZip = $tempProjectOut . '/PMO-Solutions-Codigo-Fuente.zip';

        $this->assert(file_exists($isolatedProdZip) && filesize($isolatedProdZip) > 1000, "ZIP de producción generado correctamente en entorno aislado portable");
        $this->assert(file_exists($isolatedCierreZip) && filesize($isolatedCierreZip) > 1000, "ZIP de cierre generado correctamente en entorno aislado portable");

        // Limpieza completa
        $this->deleteDirectoryRecursive($tempProjectDir);
        $this->deleteDirectoryRecursive($tempProjectOut);

        $this->assert(!is_dir($tempProjectDir) && !is_dir($tempProjectOut), "Directorios temporales de portabilidad eliminados limpiamente");
    }

    private function copyDirectoryRecursive(string $src, string $dst, array $skipPrefixes = []): void {
        $dir = opendir($src);
        if (!$dir) return;

        @mkdir($dst, 0777, true);
        while (false !== ($file = readdir($dir))) {
            if ($file === '.' || $file === '..') continue;
            if (in_array($file, $skipPrefixes, true)) continue;

            $srcPath = $src . '/' . $file;
            $dstPath = $dst . '/' . $file;

            if (is_dir($srcPath)) {
                $this->copyDirectoryRecursive($srcPath, $dstPath, $skipPrefixes);
            } else {
                copy($srcPath, $dstPath);
            }
        }
        closedir($dir);
    }

    private function deleteDirectoryRecursive(string $dir): void {
        if (!is_dir($dir)) return;
        $items = scandir($dir);
        if (!$items) return;

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->deleteDirectoryRecursive($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}

// Ejecución directa de la suite
$suite = new ProjectCleanupTest();
$suite->run();