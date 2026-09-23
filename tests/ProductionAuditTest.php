<?php
/**
 * PMO SOLUTIONS — Suite de Auditoría de Producción: Dominio, Timezone, Seguridad y Cero Residuos de Cuentas
 * 
 * Valida de forma integral:
 * 1. Timezone unificada en PHP ('America/Lima') y MariaDB (diferencia 0-2s cuando BD está disponible).
 * 2. Eliminación definitiva del prototipo de cuentas de usuario:
 *    - GET /registro -> HTTP 404
 *    - POST /registro -> HTTP 404
 *    - GET /login -> HTTP 404
 *    - POST /login -> HTTP 404
 *    - GET /mi-cuenta -> HTTP 404
 *    - POST /logout -> HTTP 404
 * 3. Inmunidad del Navbar y sesión antigua:
 *    - Cero elementos "Iniciar sesión", "Registrarse", "Mi Cuenta" o logout
 *    - Cero scripts exclusivos de usuario (registro.js, login.js, mi-cuenta.js)
 *    - Una sesión antigua existente con variables pmo_user_* no altera la vista de invitado
 * 4. Esquemas de base de datos MySQL (backend/schema.sql y schema_v2.sql):
 *    - Cero tabla "usuarios" ni referencias residuales
 * 5. Seguridad, Hardening y Dominio Oficial:
 *    - Rechazo CSRF con HTTP 403 en formularios legítimos
 *    - Cumplimiento estricto de CSP: Cero scripts inline y Cero event handlers inline en app/Views/
 *    - Dominio canónico oficial https://pmo-solutions.com
 */

declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

define('PMO_APP_ACCESS', true);
define('PMO_TEST_RUNNER', true);

require_once dirname(__DIR__) . '/app/Core/Autoloader.php';
App\Core\Autoloader::register();

$config = require dirname(__DIR__) . '/app/Config/config.php';
date_default_timezone_set($config['app']['timezone'] ?? 'America/Lima');

use App\Core\Database;

class ProductionAuditTest {

    private int $passed = 0;
    private int $failed = 0;
    private int $skipped = 0;
    private string $rootDir;

    public function __construct() {
        $this->rootDir = str_replace('\\', '/', dirname(__DIR__));
    }

    private function skip(string $name, string $reason): void {
        $this->skipped++;
        echo "  \033[33m⚡ [SKIP]\033[0m {$name} -> {$reason}\n";
    }

    private function assert(bool $condition, string $name, string $detail = ''): void {
        if ($condition) {
            $this->passed++;
            echo "  \033[32m✔ [PASS]\033[0m {$name}\n";
        } else {
            $this->failed++;
            echo "  \033[31m✖ [FAIL]\033[0m {$name}";
            if ($detail) echo " -> {$detail}";
            echo "\n";
        }
    }

    private function executeRequest(
        string $method,
        string $uri,
        ?array $postData = null,
        array $sessionOverrides = [],
        array $serverOverrides = []
    ): array {
        $phpBinary = PHP_BINARY;
        $indexFile = addslashes($this->rootDir . '/index.php');

        $parts = parse_url($uri);
        $path  = $parts['path'] ?? '/';
        $query = $parts['query'] ?? '';

        $script = '<?php ';
        $script .= 'namespace { ';
        $script .= 'ini_set("display_errors", "1"); error_reporting(E_ALL); ';
        $script .= '$_GET = []; parse_str("' . addslashes($query) . '", $_GET); ';
        $script .= '$_POST = []; ';
        $script .= '$_SERVER["REQUEST_METHOD"] = "' . addslashes($method) . '"; ';
        $script .= '$_SERVER["REQUEST_URI"] = "' . addslashes($uri) . '"; ';
        $script .= '$_SERVER["SCRIPT_NAME"] = "/index.php"; ';
        $script .= '$_SERVER["REMOTE_ADDR"] = "127.0.0.1"; ';
        $script .= '$_SERVER["HTTP_HOST"] = "localhost"; ';
        $script .= '$_SERVER["HTTP_ORIGIN"] = "https://pmo-solutions.com"; ';
        $script .= '$_SERVER["HTTP_ACCEPT"] = "text/html,application/xhtml+xml,application/json"; ';

        foreach ($serverOverrides as $sk => $sv) {
            if ($sv === null) {
                $script .= 'unset($_SERVER["' . addslashes($sk) . '"]); ';
            } else {
                $script .= '$_SERVER["' . addslashes($sk) . '"] = "' . addslashes((string)$sv) . '"; ';
            }
        }

        if (!empty($sessionOverrides)) {
            $script .= 'require_once "' . addslashes($this->rootDir) . '/app/Core/Autoloader.php"; ';
            $script .= 'App\Core\Autoloader::register(); ';
            $script .= 'App\Core\Security::startSecureSession(); ';
            foreach ($sessionOverrides as $sk => $sv) {
                $script .= '$_SESSION["' . addslashes($sk) . '"] = ' . var_export($sv, true) . '; ';
            }
        }

        if ($method === 'POST') {
            $script .= '$_SERVER["CONTENT_TYPE"] = "application/json"; ';
            if ($postData !== null) {
                $script .= '$GLOBALS["_MOCK_INPUT"] = ' . var_export(json_encode($postData), true) . '; ';
                $script .= '$_POST = ' . var_export($postData, true) . '; ';
            }
        }

        $script .= 'register_shutdown_function(function() { ';
        $script .= '  echo "\n___STATUS___:" . http_response_code(); ';
        $script .= '  $hdrs = headers_list(); ';
        $script .= '  echo "\n___HEADERS___:" . base64_encode(serialize($hdrs)); ';
        $script .= '  echo "\n___SESS___:" . base64_encode(serialize($_SESSION ?? [])); ';
        $script .= '}); ';
        $script .= 'require "' . $indexFile . '"; ';
        $script .= '} ';

        $tmp = tempnam(sys_get_temp_dir(), 'pmo_audit_');
        file_put_contents($tmp, $script);
        $output = (string)shell_exec(escapeshellarg($phpBinary) . ' ' . escapeshellarg($tmp) . ' 2>&1');
        @unlink($tmp);

        $status = 200;
        if (preg_match('/___STATUS___:(\d+)/', $output, $m)) {
            $status = (int)$m[1];
            $output = str_replace($m[0], '', $output);
        }

        $headers = [];
        if (preg_match('/___HEADERS___:([A-Za-z0-9+\/=]+)/', $output, $hm)) {
            $headers = unserialize(base64_decode($hm[1])) ?: [];
            $output = str_replace($hm[0], '', $output);
        }

        $sessionData = [];
        if (preg_match('/___SESS___:([A-Za-z0-9+\/=]+)/', $output, $sm)) {
            $sessionData = unserialize(base64_decode($sm[1])) ?: [];
            $output = str_replace($sm[0], '', $output);
        }

        $output = trim($output);
        $json = json_decode($output, true);

        return [
            'status'  => $status,
            'headers' => $headers,
            'session' => $sessionData,
            'json'    => $json,
            'html'    => $output,
        ];
    }

    public function run(): void {
        echo "\n" . str_repeat('=', 70) . "\n";
        echo " SUITE DE AUDITORÍA: TIMEZONE, RUTAS ELIMINADAS Y SEGURIDAD\n";
        echo str_repeat('=', 70) . "\n";

        $this->testTimezoneSynchronization();
        $this->testEliminatedUserRoutesReturn404();
        $this->testNavbarAndStaleSessionSafety();
        $this->testDatabaseSchemasIntegrity();
        $this->testSecurityAndDomainCompliance();
        $this->testCanonicalOgUrlAndSitemapCompliance();

        $this->printSummary();
    }

    private function testTimezoneSynchronization(): void {
        echo "\n--- 1. Auditoría de Timezone y Sincronización PHP-MySQL ---\n";

        $this->assert(date_default_timezone_get() === 'America/Lima', "PHP date_default_timezone_get() es 'America/Lima'");

        $pdo = Database::getConnection();
        if ($pdo === null) {
            $this->skip("Sincronización MariaDB", "Base de datos MySQL no habilitada o credenciales ausentes.");
            return;
        }

        $stmt = $pdo->query("SELECT @@session.time_zone AS session_tz, NOW() AS now_db, CURRENT_TIMESTAMP() AS current_ts, UTC_TIMESTAMP() AS utc_ts");
        $tzData = $stmt->fetch();

        $this->assert($tzData['session_tz'] === '-05:00', "MariaDB session time_zone es estrictamente '-05:00'");

        $phpNow = date('Y-m-d H:i:s');
        $dbNow  = $tzData['now_db'];
        $diffSeconds = abs(strtotime($phpNow) - strtotime($dbNow));

        $this->assert($diffSeconds <= 2, "Diferencia entre PHP ({$phpNow}) y MariaDB NOW() ({$dbNow}) es de {$diffSeconds}s (<= 2s)");
    }

    private function testEliminatedUserRoutesReturn404(): void {
        echo "\n--- 2. Retorno Nativo HTTP 404 en Rutas de Cuentas Eliminadas ---\n";

        $eliminatedRoutes = [
            ['GET', '/registro'],
            ['POST', '/registro'],
            ['GET', '/login'],
            ['POST', '/login'],
            ['GET', '/mi-cuenta'],
            ['POST', '/logout'],
        ];

        foreach ($eliminatedRoutes as [$m, $uri]) {
            $res = $this->executeRequest($m, $uri, $m === 'POST' ? ['dummy' => '1'] : null);
            $this->assert($res['status'] === 404, "{$m} {$uri} responde HTTP 404 Not Found (sin redirección)", "Status recibido: {$res['status']}");

            // Verificar que no redirige con Location
            $hasLocation = false;
            foreach ($res['headers'] as $hdr) {
                if (stripos($hdr, 'Location:') === 0) {
                    $hasLocation = true;
                    break;
                }
            }
            $this->assert(!$hasLocation, "{$m} {$uri} no emite cabecera Location de redirección");
        }
    }

    private function testNavbarAndStaleSessionSafety(): void {
        echo "\n--- 3. Inmunidad del Navbar y Sesiones Antiguas ---\n";

        // 1. Petición normal a Home
        $guestHome = $this->executeRequest('GET', '/');
        $this->assert($guestHome['status'] === 200, "GET / responde HTTP 200 OK");

        $forbiddenStrings = [
            'href="/login"',
            'href="/registro"',
            'Iniciar sesión',
            'Crear cuenta',
            'href="/mi-cuenta"',
            'Mi Cuenta',
            'pmo-logout-form',
            'js/pages/login.js',
            'js/pages/registro.js',
            'js/pages/mi-cuenta.js'
        ];

        foreach ($forbiddenStrings as $str) {
            $this->assert(strpos($guestHome['html'], $str) === false, "Navbar no contiene '{$str}'");
        }

        // 2. Petición con sesión antigua simulada (con pmo_user_id y datos)
        $staleSession = [
            'pmo_user_id'            => 12345,
            'pmo_user_email'         => 'antiguo_usuario@example.invalid',
            'pmo_user_name'          => 'Carlos',
            'pmo_user_last_activity' => time(),
            'pmo_user_created_at'    => time() - 3600
        ];

        $staleHome = $this->executeRequest('GET', '/', null, $staleSession);
        $this->assert($staleHome['status'] === 200, "GET / con sesión antigua responde HTTP 200 OK");

        foreach ($forbiddenStrings as $str) {
            $this->assert(strpos($staleHome['html'], $str) === false, "Navbar con sesión antigua no contiene '{$str}'");
        }
        $this->assert(strpos($staleHome['html'], 'Carlos') === false, "Navbar con sesión antigua no muestra nombre de usuario");
    }

    private function testDatabaseSchemasIntegrity(): void {
        echo "\n--- 4. Integridad de Esquemas SQL (Ausencia Total de Tabla usuarios) ---\n";

        $schemas = [
            'backend/schema.sql'    => 'Esquema base',
            'backend/schema_v2.sql' => 'Esquema canónico v2'
        ];

        foreach ($schemas as $fileRel => $label) {
            $fullPath = $this->rootDir . '/' . $fileRel;
            $this->assert(file_exists($fullPath), "Archivo de esquema existe: {$fileRel}");

            $sql = (string)file_get_contents($fullPath);
            $hasTable = preg_match('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?usuarios`?/i', $sql);
            $this->assert(!$hasTable, "{$label} ({$fileRel}) no define tabla 'usuarios'");

            $hasReferences = preg_match('/REFERENCES\s+`?usuarios`?/i', $sql);
            $this->assert(!$hasReferences, "{$label} ({$fileRel}) no contiene referencias foráneas a 'usuarios'");
        }
    }

    private function testSecurityAndDomainCompliance(): void {
        echo "\n--- 5. Hardening, CSP y Cumplimiento de Dominio Oficial ---\n";

        // A. Token CSRF inválido en POST /contacto/submit
        $invalidCsrfRes = $this->executeRequest('POST', '/contacto/submit', [
            'nombre'     => 'Tester',
            'email'      => 'test@example.com',
            'mensaje'    => 'Mensaje de prueba',
            'csrf_token' => 'invalid_csrf_token_00000000000000000000000000000000000000000'
        ]);
        $this->assert($invalidCsrfRes['status'] === 403, "POST /contacto/submit sin CSRF válido retorna HTTP 403 Forbidden");

        // B. Auditoría estática CSP en vistas (CERO scripts inline y CERO event handlers inline)
        $allViewFiles = [];
        $viewIter = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->rootDir . '/app/Views'));
        foreach ($viewIter as $fileInfo) {
            if ($fileInfo->isFile() && $fileInfo->getExtension() === 'php') {
                $allViewFiles[] = $fileInfo->getPathname();
            }
        }
        $cspViolations = [];
        $handlerViolations = [];
        foreach ($allViewFiles as $vPath) {
            $code = (string)file_get_contents($vPath);
            $vName = basename($vPath);
            if (preg_match_all('/<script\b(?![^>]*\bsrc=)[^>]*>(.*?)<\/script>/is', $code, $matches)) {
                foreach ($matches[1] as $body) {
                    if (trim($body) !== '') {
                        $cspViolations[] = "{$vName} contiene script inline ejecutable";
                    }
                }
            }
            if (preg_match_all('/\b(on[a-z]+)\s*=/i', $code, $mH)) {
                foreach ($mH[1] as $h) {
                    $handlerViolations[] = "{$vName} contiene event handler inline {$h}=";
                }
            }
        }
        $this->assert(empty($cspViolations), "Todas las vistas en app/Views/ tienen CERO scripts inline ejecutables (CSP Compliant)");
        $this->assert(empty($handlerViolations), "Todas las vistas en app/Views/ tienen CERO event handlers inline on[a-z]+= (CSP Compliant)");

        // C. Dominio oficial en canonical y layouts
        $layoutPath = $this->rootDir . '/app/Views/layouts/main.php';
        $layoutContent = (string)file_get_contents($layoutPath);
        $this->assert(str_contains($layoutContent, 'https://pmo-solutions.com'), "Layout principal define dominio canónico https://pmo-solutions.com");
    }

    private function testCanonicalOgUrlAndSitemapCompliance(): void {
        echo "\n--- 6. Auditoría SEO: URLs Canónicas Dinámicas, OpenGraph y Sitemap XML ---\n";

        // 1. Portada con y sin query params
        $resHome = $this->executeRequest('GET', '/');
        $this->assert($resHome['status'] === 200, "GET / responde HTTP 200");
        $this->assert(
            str_contains($resHome['html'], '<link rel="canonical" href="https://pmo-solutions.com/">'),
            "GET / genera canonical con barra final: https://pmo-solutions.com/"
        );
        $this->assert(
            str_contains($resHome['html'], '<meta property="og:url" content="https://pmo-solutions.com/">'),
            "GET / genera og:url igual al canonical: https://pmo-solutions.com/"
        );

        $resHomeQuery = $this->executeRequest('GET', '/?utm_source=google&ref=123');
        $this->assert(
            str_contains($resHomeQuery['html'], '<link rel="canonical" href="https://pmo-solutions.com/">'),
            "GET /?utm_source=google&ref=123 excluye parámetros query del canonical"
        );
        $this->assert(
            str_contains($resHomeQuery['html'], '<meta property="og:url" content="https://pmo-solutions.com/">'),
            "GET /?utm_source=google&ref=123 excluye parámetros query de og:url"
        );

        // 2. Rutas internas (sin barra final)
        $publicRoutes = [
            '/capacitaciones'           => 'https://pmo-solutions.com/capacitaciones',
            '/contacto'                 => 'https://pmo-solutions.com/contacto',
            '/libro-de-reclamaciones'   => 'https://pmo-solutions.com/libro-de-reclamaciones',
            '/evaluacion-habilidades'   => 'https://pmo-solutions.com/evaluacion-habilidades',
            '/terminos-y-condiciones'   => 'https://pmo-solutions.com/terminos-y-condiciones',
            '/politica-de-privacidad'   => 'https://pmo-solutions.com/politica-de-privacidad',
            '/nec4'                     => 'https://pmo-solutions.com/nec4',
            '/primavera-p6'             => 'https://pmo-solutions.com/primavera-p6',
            '/dab-jrd'                  => 'https://pmo-solutions.com/dab-jrd',
            '/vdc-bim'                  => 'https://pmo-solutions.com/vdc-bim',
            '/contratos-estado'         => 'https://pmo-solutions.com/contratos-estado',
            '/analisis-cuantitativo'    => 'https://pmo-solutions.com/analisis-cuantitativo',
            '/analisis-forense'         => 'https://pmo-solutions.com/analisis-forense',
            '/eventos-compensables'     => 'https://pmo-solutions.com/eventos-compensables',
            '/compliance'               => 'https://pmo-solutions.com/compliance',
            '/riesgos-pmi'              => 'https://pmo-solutions.com/riesgos-pmi',
        ];

        foreach ($publicRoutes as $route => $expectedCanonical) {
            $res = $this->executeRequest('GET', $route);
            $this->assert($res['status'] === 200, "GET {$route} responde HTTP 200");
            $this->assert(
                str_contains($res['html'], '<link rel="canonical" href="' . $expectedCanonical . '">'),
                "GET {$route} genera canonical sin barra final: {$expectedCanonical}"
            );
            $this->assert(
                str_contains($res['html'], '<meta property="og:url" content="' . $expectedCanonical . '">'),
                "GET {$route} genera og:url idéntico a canonical"
            );
        }

        // 3. Ruta interna con trailing slash eliminada y query params excluidos
        $resTrailingSlash = $this->executeRequest('GET', '/capacitaciones/?ref=promo&fbclid=xyz');
        $this->assert(
            str_contains($resTrailingSlash['html'], '<link rel="canonical" href="https://pmo-solutions.com/capacitaciones">'),
            "GET /capacitaciones/?ref=promo elimina barra final y excluye parámetros query en canonical"
        );
        $this->assert(
            str_contains($resTrailingSlash['html'], '<meta property="og:url" content="https://pmo-solutions.com/capacitaciones">'),
            "GET /capacitaciones/?ref=promo genera og:url limpio y coherente"
        );

        // 4. Verificación de Sitemap XML
        $sitemapPath = $this->rootDir . '/sitemap.xml';
        $this->assert(file_exists($sitemapPath), "Archivo sitemap.xml existe físicamente");

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

        // Las 3 rutas nuevas
        $this->assert(in_array('https://pmo-solutions.com/evaluacion-habilidades', $sitemapUrls, true), "sitemap.xml incluye https://pmo-solutions.com/evaluacion-habilidades");
        $this->assert(in_array('https://pmo-solutions.com/terminos-y-condiciones', $sitemapUrls, true), "sitemap.xml incluye https://pmo-solutions.com/terminos-y-condiciones");
        $this->assert(in_array('https://pmo-solutions.com/politica-de-privacidad', $sitemapUrls, true), "sitemap.xml incluye https://pmo-solutions.com/politica-de-privacidad");

        // Ausencia de /politicas-de-privacidad (plural)
        $hasPluralPolicy = false;
        foreach ($sitemapUrls as $u) {
            if (str_contains($u, 'politicas-de-privacidad')) {
                $hasPluralPolicy = true;
                break;
            }
        }
        $this->assert(!$hasPluralPolicy, "sitemap.xml NO contiene la ruta plural /politicas-de-privacidad");

        // Ausencia de rutas de cuentas eliminadas
        $forbiddenSitemapPatterns = ['/registro', '/login', '/mi-cuenta', '/logout', '/api/', '/admin/', '/curso/{slug}', '404'];
        $hasForbidden = false;
        $forbiddenFound = [];
        foreach ($sitemapUrls as $u) {
            foreach ($forbiddenSitemapPatterns as $fbp) {
                if (str_contains($u, $fbp)) {
                    $hasForbidden = true;
                    $forbiddenFound[] = "{$u} contiene {$fbp}";
                }
            }
        }
        $this->assert(!$hasForbidden, "sitemap.xml NO contiene APIs, admin, cuentas eliminadas ni rutas dinámicas", implode(', ', $forbiddenFound));

        // Dominio único pmo-solutions.com
        $nonOfficialDomain = false;
        foreach ($sitemapUrls as $u) {
            if (!str_starts_with($u, 'https://pmo-solutions.com/')) {
                $nonOfficialDomain = true;
                break;
            }
        }
        $this->assert(!$nonOfficialDomain, "Todas las URLs del sitemap usan estrictamente el dominio oficial https://pmo-solutions.com/");

        // Robots.txt apunta al sitemap
        $robotsPath = $this->rootDir . '/robots.txt';
        $this->assert(file_exists($robotsPath), "Archivo robots.txt existe físicamente");
        $robotsContent = (string)file_get_contents($robotsPath);
        $this->assert(
            str_contains($robotsContent, 'Sitemap: https://pmo-solutions.com/sitemap.xml'),
            "robots.txt apunta a https://pmo-solutions.com/sitemap.xml"
        );
    }

    private function printSummary(): void {
        echo "\n" . str_repeat('=', 70) . "\n";
        echo " RESUMEN: AUDITORÍA FINAL DE PRODUCCIÓN\n";
        echo str_repeat('=', 70) . "\n";
        echo " Total tests: " . ($this->passed + $this->failed + $this->skipped) .
             " | Pasados: {$this->passed}" .
             " | Fallados: {$this->failed}" .
             " | Omitidos (SKIP): {$this->skipped}\n\n";

        if ($this->failed > 0) {
            echo "\033[31m✖ HUBO FALLOS EN LA AUDITORÍA DE PRODUCCIÓN.\033[0m\n\n";
            exit(1);
        } else {
            echo "\033[32m✔ TODAS LAS PRUEBAS DE AUDITORÍA PASARON AL 100%.\033[0m\n\n";
        }
    }
}

$test = new ProductionAuditTest();
$test->run();
