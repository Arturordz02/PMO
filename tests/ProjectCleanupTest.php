<?php
/**
 * PMO SOLUTIONS — Suite de Pruebas: Limpieza de Archivos Heredados y Paquete de Producción (Tarea 9)
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
        echo " SUITE DE PRUEBAS: LIMPIEZA DE ARCHIVOS Y PAQUETE PRODUCCIÓN (TAREA 9)\n";
        echo str_repeat('=', 70) . "\n\n";

        $this->testLegacyFilesPhysicalAbsence();
        $this->testRetainedCanonicalFilesPresence();
        $this->testMvcRoutesAndCanonicalApis();
        $this->testRetiredRoutesAndAliasesReturn404();
        $this->testBackendHtaccessHardening();
        $this->testGlobalReferencesAndBrokenLinks();
        $this->testServiceWorkerV22Integrity();
        $this->testPackagerInIsolatedTempDirectory();
        $this->testPackagerPortabilityInClonedDirectory();

        echo "\n" . str_repeat('=', 70) . "\n";
        echo " RESUMEN: TAREA 9 (LIMPIEZA Y PAQUETE DE PRODUCCIÓN)\n";
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
            // Base de datos canónica y migraciones
            'backend/schema_v2.sql',
            'backend/schema.sql',
            'backend/migrations/001_add_report_access_token_hash.sql',
            'backend/migrations/001_rollback.sql',
            'backend/migrations/002_add_idempotency_and_outbox.sql',
            'backend/migrations/002_rollback.sql',
            'backend/.htaccess',
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
    // 8. Validación del Generador en Directorio Temporal Aislado
    // ─────────────────────────────────────────────────────────────────────────
    private function testPackagerInIsolatedTempDirectory(): void {
        echo "\n--- 8. Verificación del Generador en Directorio Temporal Aislado ---\n";

        $generatorPath = $this->rootDir . '/artisan/create_production_zip.php';
        $this->assert(file_exists($generatorPath), "Generador artisan/create_production_zip.php presente en el proyecto");

        $tempOutDir = sys_get_temp_dir() . '/pmo_zip_test_' . bin2hex(random_bytes(4));
        if (!is_dir($tempOutDir)) {
            mkdir($tempOutDir, 0777, true);
        }

        $cmd = sprintf('"%s" "%s" "%s" 2>&1', PHP_BINARY, $generatorPath, $tempOutDir);
        exec($cmd, $output, $exitCode);

        $this->assert($exitCode === 0, "artisan/create_production_zip.php ejecutado exitosamente con código 0");

        $prodZipPath = $tempOutDir . '/PMO-Solutions-Produccion-Tarea9.zip';
        $cierreZipPath = $tempOutDir . '/PMO-Solutions-Tarea9-Cierre.zip';

        $this->assert(file_exists($prodZipPath), "ZIP de producción generado en directorio temporal");
        $this->assert(file_exists($cierreZipPath), "ZIP de cierre generado en directorio temporal");

        // Inspeccionar ZIP de producción
        $zipProd = new ZipArchive();
        $openedProd = $zipProd->open($prodZipPath);
        $this->assert($openedProd === true, "ZIP de producción recién generado es válido y legible");

        if ($openedProd === true) {
            $entriesProd = [];
            for ($i = 0; $i < $zipProd->numFiles; $i++) {
                $entriesProd[] = str_replace('\\', '/', $zipProd->getNameIndex($i));
            }
            $zipProd->close();

            $forbiddenInProd = [
                'tests/',
                'frontend/',
                '.git/',
                '.agent/',
                '.vscode/',
                '.idea/',
                'backend/Database.php',
                'backend/Security.php',
                'backend/SmtpMailer.php',
                'backend/config.php',
                'backend/config.example.php',
                'backend/tests/',
                'artisan/create_production_zip.php'
            ];

            $foundForbidden = [];
            foreach ($entriesProd as $entry) {
                if ($entry === '.env' || (str_starts_with($entry, '.env.') && $entry !== '.env.example') || str_starts_with($entry, '.env/')) {
                    $foundForbidden[] = $entry;
                }
                foreach ($forbiddenInProd as $forb) {
                    if ($entry === $forb || str_starts_with($entry, $forb)) {
                        $foundForbidden[] = $entry;
                    }
                }
                if (
                    str_starts_with($entry, 'storage/cache/') ||
                    str_starts_with($entry, 'storage/logs/') ||
                    str_starts_with($entry, 'storage/outbox/') ||
                    str_starts_with($entry, 'storage/framework/')
                ) {
                    $bn = basename($entry);
                    if ($bn !== '.gitkeep' && $bn !== '.htaccess' && !str_ends_with($entry, '/')) {
                        $foundForbidden[] = $entry;
                    }
                }
            }

            $this->assert(empty($foundForbidden), "ZIP de producción excluye estrictamente .env, tests/, frontend/, logs y herramientas de desarrollo");

            $mandatoryInProd = [
                'app/Config/config.php',
                'app/Core/Autoloader.php',
                'app/Core/Router.php',
                'app/Core/Security.php',
                'app/Views/layouts/main.php',
                'artisan/process-outbox.php',
                'artisan/archive-logs.php',
                'backend/schema_v2.sql',
                'backend/schema.sql',
                'backend/migrations/001_add_report_access_token_hash.sql',
                'backend/migrations/002_add_idempotency_and_outbox.sql',
                'backend/migrations/001_rollback.sql',
                'backend/migrations/002_rollback.sql',
                'backend/.htaccess',
                'css/styles.css',
                'js/main.js',
                'js/sw.js',
                'js/vendor/chart.umd.min.js',
                'js/vendor/jspdf.umd.min.js',
                'js/vendor/LICENSE-chartjs.txt',
                'js/vendor/LICENSE-jspdf.txt',
                'js/vendor/README.md',
                'storage/.htaccess',
                'storage/logs/.gitkeep',
                'storage/cache/.gitkeep',
                'storage/outbox/.gitkeep',
                '.htaccess',
                'index.php',
                'robots.txt',
                'sitemap.xml',
                'README.md',
                '.env.example'
            ];

            $missingInProd = [];
            foreach ($mandatoryInProd as $mand) {
                if (!in_array($mand, $entriesProd, true)) {
                    $missingInProd[] = $mand;
                }
            }

            $this->assert(empty($missingInProd), "ZIP de producción contiene todos los archivos esenciales requeridos");
        }

        // Inspeccionar ZIP de Cierre
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
            $this->assert(in_array('tests/browser_test.js', $entriesCierre, true), "ZIP de cierre incluye tests/browser_test.js");
            $this->assert(in_array('artisan/create_production_zip.php', $entriesCierre, true), "ZIP de cierre incluye el generador artisan/create_production_zip.php");
            $this->assert(!in_array('.env', $entriesCierre, true), "ZIP de cierre excluye credenciales locales (.env)");

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
        $this->assert(!is_dir($tempOutDir), "Directorio temporal de prueba de empaquetado eliminado con éxito");
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

        // Copiar estructura básica del proyecto al directorio temporal
        $this->copyDirectoryRecursive($this->rootDir, $tempProjectDir, ['.git', 'tests']);

        $isolatedGenerator = $tempProjectDir . '/artisan/create_production_zip.php';
        $this->assert(file_exists($isolatedGenerator), "Generador presente en el clon temporal independiente");

        $cmd = sprintf('"%s" "%s" "%s" 2>&1', PHP_BINARY, $isolatedGenerator, $tempProjectOut);
        exec($cmd, $output, $exitCode);

        $this->assert($exitCode === 0, "Generador ejecutado exitosamente en ubicación independiente (Código 0)");

        $isolatedProdZip = $tempProjectOut . '/PMO-Solutions-Produccion-Tarea9.zip';
        $isolatedCierreZip = $tempProjectOut . '/PMO-Solutions-Tarea9-Cierre.zip';

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