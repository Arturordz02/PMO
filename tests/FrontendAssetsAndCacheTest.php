<?php
/**
 * PMO SOLUTIONS — Suite de Pruebas: Frontend Assets, CSP y Estrategia de Caché (Tarea 8)
 *
 * Cobertura de pruebas:
 * 1. Auditoría de dependencias: Sin uso de @latest ni @next, versiones fijas en todos los CDN.
 * 2. Disponibilidad y licencias de vendor locales: Chart.js v4.4.0 y jsPDF v2.5.1 en js/vendor/ con licencias MIT.
 * 3. Eliminación de fallbacks CDN: soft-skills.js carga exclusivamente desde los assets locales versionados.
 * 4. Política CSP: Unificada en PHP y .htaccess (Header onsuccess unset / Header always set), sin unsafe-eval ni comodines, unpkg.com eliminado.
 * 5. Helper View::asset(): Cache busting automático con filemtime(), normalización de rutas y fallback.
 * 6. Eliminación de importmap inline en favor de metadatos <meta name="asset-*"> para dynamic import().
 * 7. Sustitución total de /css/main.css por el asset real /css/styles.css en preloads y configuración.
 * 8. Estrategia de caché: Caché inmutable (1 año) con ?v=, caché corta revalidable sin versión, y no-store en HTML/CSRF.
 * 9. Pruebas reales en servidor Apache 2.4 en vivo (configtest, página dinámica con 1 sola CSP, asset inmutable vs revalidable).
 * 10. Service Worker (sw.js): Reglas anti-CSRF, exclusión de APIs/POST/no-store y purga de versiones obsoletas.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Core/Env.php';
require_once dirname(__DIR__) . '/app/Core/Security.php';
require_once dirname(__DIR__) . '/app/Core/View.php';
require_once dirname(__DIR__) . '/app/Core/Controller.php';

use App\Core\Security;
use App\Core\View;

class FrontendAssetsAndCacheTest {

    private int $passed = 0;
    private int $failed = 0;
    private int $skipped = 0;
    private string $rootDir;

    public function __construct() {
        $this->rootDir = dirname(__DIR__);
    }

    public function run(): void {
        echo "\n" . str_repeat('=', 70) . "\n";
        echo " SUITE DE PRUEBAS: FRONTEND ASSETS, CSP Y CACHÉ (TAREA 8)\n";
        echo str_repeat('=', 70) . "\n\n";

        $this->testDependencyAudit();
        $this->testLocalVendorLibrariesAndNoCdnFallback();
        $this->testContentSecurityPolicyUnification();
        $this->testAssetVersioningAndMetaTags();
        $this->testCriticalAssetPreloadFix();
        $this->testCacheHeadersStrategy();
        $this->testRealApacheServerIntegration();
        $this->testServiceWorkerRules();

        echo "\n" . str_repeat('=', 70) . "\n";
        echo " RESUMEN: TAREA 8 (FRONTEND ASSETS, CSP Y CACHÉ)\n";
        echo str_repeat('=', 70) . "\n";
        echo " Total tests: " . ($this->passed + $this->failed + $this->skipped) .
             " | Pasados: {$this->passed}" .
             " | Fallados: {$this->failed}" .
             " | Omitidos (SKIP): {$this->skipped}\n\n";

        if ($this->failed > 0) {
            echo "✖ SE ENCONTRARON FALLOS EN LA AUDITORÍA DE FRONTEND/CSP/CACHÉ.\n\n";
            exit(1);
        } else {
            echo "✔ TODAS LAS PRUEBAS DE FRONTEND, CSP Y CACHÉ PASARON AL 100%.\n\n";
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
    // 1. Auditoría de Dependencias Frontend
    // ─────────────────────────────────────────────────────────────────────────
    private function testDependencyAudit(): void {
        echo "--- 1. Auditoría de Dependencias Frontend y CDNs ---\n";

        $viewFiles = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->rootDir . '/app/Views'));
        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isFile() && $fileInfo->getExtension() === 'php') {
                $viewFiles[] = $fileInfo->getPathname();
            }
        }

        $hasLatestOrNext = false;
        $offending = [];

        foreach ($viewFiles as $file) {
            $content = (string)file_get_contents($file);
            if (preg_match('/(unpkg\.com\/[^"\'\s]+@(latest|next)|cdn\.jsdelivr\.net\/[^"\'\s]+@(latest|next))/i', $content, $m)) {
                $hasLatestOrNext = true;
                $offending[] = basename($file) . " -> " . $m[0];
            }
        }

        $this->assert(!$hasLatestOrNext, "Ninguna plantilla de vista utiliza versiones no fijadas (@latest o @next)" . (!empty($offending) ? " (Encontrado: " . implode(', ', $offending) . ")" : ""));

        // Verificar fijación de versiones en layout principal
        $mainLayout = (string)file_get_contents($this->rootDir . '/app/Views/layouts/main.php');
        $this->assert(str_contains($mainLayout, 'bootstrap@5.3.3'), "Bootstrap 5 fijado en versión explícita v5.3.3");
        $this->assert(str_contains($mainLayout, 'font-awesome/6.5.1'), "FontAwesome fijado en versión explícita v6.5.1");
        $this->assert(str_contains($mainLayout, 'animate.css/4.1.1'), "Animate.css fijado en versión explícita v4.1.1");
        $this->assert(str_contains($mainLayout, 'aos/2.3.4'), "AOS (Animate On Scroll) fijado en versión explícita v2.3.4");
        $this->assert(str_contains($mainLayout, 'hover.css/2.3.1'), "Hover.css fijado en versión explícita v2.3.1");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 2. Disponibilidad de Vendor Locales y Ausencia de Fallbacks CDN
    // ─────────────────────────────────────────────────────────────────────────
    private function testLocalVendorLibrariesAndNoCdnFallback(): void {
        echo "\n--- 2. Disponibilidad de Librerías Locales y Ausencia de Fallbacks CDN ---\n";

        $chartJsPath = $this->rootDir . '/js/vendor/chart.umd.min.js';
        $jspdfPath = $this->rootDir . '/js/vendor/jspdf.umd.min.js';
        $readmePath = $this->rootDir . '/js/vendor/README.md';
        $licChartPath = $this->rootDir . '/js/vendor/LICENSE-chartjs.txt';
        $licJspdfPath = $this->rootDir . '/js/vendor/LICENSE-jspdf.txt';

        $this->assert(file_exists($chartJsPath) && filesize($chartJsPath) > 100000, "Chart.js v4.4.0 alojado localmente en js/vendor/ (" . round(filesize($chartJsPath) / 1024) . " KB)");
        $this->assert(file_exists($jspdfPath) && filesize($jspdfPath) > 100000, "jsPDF v2.5.1 alojado localmente en js/vendor/ (" . round(filesize($jspdfPath) / 1024) . " KB)");
        $this->assert(file_exists($readmePath), "Documentación de versión y origen presente en js/vendor/README.md");
        $this->assert(file_exists($licChartPath) && file_exists($licJspdfPath), "Archivos de licencias MIT presentes (LICENSE-chartjs.txt, LICENSE-jspdf.txt)");

        // Verificar que soft-skills.js use URLs versionadas desde meta y no contenga fallbacks CDN innecesarios
        $softSkillsJs = (string)file_get_contents($this->rootDir . '/js/modules/soft-skills.js');
        $this->assert(str_contains($softSkillsJs, 'meta[name="asset-chartjs"]'), "soft-skills.js obtiene la URL versionada de Chart.js desde metadatos");
        $this->assert(str_contains($softSkillsJs, 'meta[name="asset-jspdf"]'), "soft-skills.js obtiene la URL versionada de jsPDF desde metadatos");
        $this->assert(!str_contains($softSkillsJs, 'https://cdn.jsdelivr.net/npm/chart.js'), "Fallbacks CDN innecesarios eliminados de Chart.js");
        $this->assert(!str_contains($softSkillsJs, 'https://cdnjs.cloudflare.com/ajax/libs/jspdf'), "Fallbacks CDN innecesarios eliminados de jsPDF");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 3. Política de Seguridad de Contenido (CSP) Unificada
    // ─────────────────────────────────────────────────────────────────────────
    private function testContentSecurityPolicyUnification(): void {
        echo "\n--- 3. Política de Seguridad de Contenido (CSP) Unificada & sin unpkg.com ---\n";

        $htaccess = (string)file_get_contents($this->rootDir . '/.htaccess');
        $securityPhp = (string)file_get_contents($this->rootDir . '/app/Core/Security.php');
        $controllerPhp = (string)file_get_contents($this->rootDir . '/app/Core/Controller.php');

        $this->assert(str_contains($htaccess, 'Header onsuccess unset Content-Security-Policy') && str_contains($htaccess, 'Header always set Content-Security-Policy'), ".htaccess utiliza el patrón compatible 'Header onsuccess unset / Header always set' para evitar duplicación");
        $this->assert(str_contains($securityPhp, "Content-Security-Policy: default-src 'self'"), "Security::initHeaders() emite política CSP canónica");
        $this->assert(str_contains($controllerPhp, "Content-Security-Policy: default-src 'self'"), "Controller::render() emite política CSP canónica");
        $this->assert(!str_contains($htaccess, "'unsafe-eval'") && !str_contains($securityPhp, "'unsafe-eval'"), "unsafe-eval estrictamente ausente de toda la política CSP");
        $this->assert(!str_contains($htaccess, "unpkg.com") && !str_contains($securityPhp, "unpkg.com"), "unpkg.com eliminado de CSP al no existir recursos en dicho CDN");
        $this->assert(!str_contains($htaccess, "script-src *") && !str_contains($securityPhp, "script-src *"), "Sin comodines abiertos (*) en script-src ni default-src");
        $this->assert(str_contains($securityPhp, "default-src 'self'"), "default-src restringido a 'self'");
        $this->assert(str_contains($securityPhp, "object-src 'none'"), "object-src deshabilitado ('none')");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 4. Versionado de Assets y Metadatos en Layout
    // ─────────────────────────────────────────────────────────────────────────
    private function testAssetVersioningAndMetaTags(): void {
        echo "\n--- 4. Versionado Automático de Assets y Metadatos para Import Dinámico ---\n";

        $cssUrl = View::asset('/css/styles.css');
        $jsUrl = View::asset('js/main.js');

        $this->assert(str_starts_with($cssUrl, '/css/styles.css?v='), "View::asset('/css/styles.css') genera URL versionada: {$cssUrl}");
        $this->assert(str_starts_with($jsUrl, '/js/main.js?v='), "View::asset('js/main.js') normaliza rutas sin barra inicial: {$jsUrl}");

        // Simular modificación de archivo y comprobar cambio en parámetro ?v=
        $tempCss = $this->rootDir . '/css/temp_test_' . uniqid() . '.css';
        file_put_contents($tempCss, '/* test */');
        $initialTime = time() - 3600;
        touch($tempCss, $initialTime);

        $url1 = View::asset('/css/' . basename($tempCss));
        $newTime = time() + 100;
        touch($tempCss, $newTime);
        $url2 = View::asset('/css/' . basename($tempCss));

        @unlink($tempCss);

        $this->assert($url1 !== $url2, "Actualización física del asset cambia inmediatamente su URL (?v={$initialTime} vs ?v={$newTime})");

        // Fallback estable para archivos inexistentes
        $fallbackUrl = View::asset('/css/archivo_fantasma_inexistente.css');
        $this->assert(str_contains($fallbackUrl, '?v='), "Fallback estable cuando filemtime() no puede ejecutarse");

        // Verificación de metadatos en layout principal
        $mainLayout = (string)file_get_contents($this->rootDir . '/app/Views/layouts/main.php');
        $this->assert(!str_contains($mainLayout, 'renderImportMap'), "Import map inline eliminado de main.php");
        $this->assert(str_contains($mainLayout, 'meta name="asset-utils"'), "Metadato <meta name=\"asset-utils\"> presente en layout");
        $this->assert(str_contains($mainLayout, 'meta name="asset-chartjs"'), "Metadato <meta name=\"asset-chartjs\"> presente en layout");
        $this->assert(str_contains($mainLayout, 'meta name="asset-jspdf"'), "Metadato <meta name=\"asset-jspdf\"> presente en layout");

        // Verificación de import dinámico en soft-skills.js
        $softSkillsJs = (string)file_get_contents($this->rootDir . '/js/modules/soft-skills.js');
        $this->assert(str_contains($softSkillsJs, 'import(utilsMetaUrl)'), "soft-skills.js importa utils.js dinámicamente mediante import(url)");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 5. Sustitución Total de /css/main.css por /css/styles.css
    // ─────────────────────────────────────────────────────────────────────────
    private function testCriticalAssetPreloadFix(): void {
        echo "\n--- 5. Sustitución Total de /css/main.css por /css/styles.css ---\n";

        $htaccess = (string)file_get_contents($this->rootDir . '/.htaccess');
        $viewPhp = (string)file_get_contents($this->rootDir . '/app/Core/View.php');

        $this->assert(!str_contains($htaccess, '/css/main.css'), ".htaccess no contiene referencias obsoletas a /css/main.css");
        $this->assert(str_contains($htaccess, '/css/styles.css'), ".htaccess precarga el asset real /css/styles.css");
        $this->assert(!str_contains($viewPhp, '/css/main.css'), "View::\$criticalAssets no contiene referencias a /css/main.css");
        $this->assert(str_contains($viewPhp, '/css/styles.css'), "View::\$criticalAssets precarga el asset real /css/styles.css");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 6. Estrategia de Cabeceras de Caché HTTP (Inmutable vs Revalidable vs no-store)
    // ─────────────────────────────────────────────────────────────────────────
    private function testCacheHeadersStrategy(): void {
        echo "\n--- 6. Estrategia de Cabeceras de Caché HTTP ---\n";

        $htaccess = (string)file_get_contents($this->rootDir . '/.htaccess');
        $securityPhp = (string)file_get_contents($this->rootDir . '/app/Core/Security.php');
        $controllerPhp = (string)file_get_contents($this->rootDir . '/app/Core/Controller.php');
        $indexPhp = (string)file_get_contents($this->rootDir . '/index.php');

        $this->assert(str_contains($securityPhp, "Cache-Control: no-store, no-cache, must-revalidate"), "Security::initHeaders() emite Cache-Control no-store para APIs y POSTs");
        $this->assert(str_contains($controllerPhp, "Cache-Control: no-store, no-cache, must-revalidate"), "Controller::render() emite Cache-Control no-store para vistas HTML con CSRF");

        $this->assert(str_contains($htaccess, 'SetEnvIfExpr') && str_contains($htaccess, 'HAS_ASSET_VERSION'), ".htaccess utiliza SetEnvIfExpr sin anidación <FilesMatch> dentro de <If>");
        $this->assert(str_contains($htaccess, 'max-age=31536000, immutable'), ".htaccess aplica caché larga e inmutable (1 año) para URLs con ?v=...");
        $this->assert(str_contains($htaccess, 'max-age=3600, must-revalidate'), ".htaccess aplica caché corta y revalidable para assets estáticos sin versión");
        $this->assert(str_contains($htaccess, "no-store, no-cache, must-revalidate"), ".htaccess protege páginas .html y .php con directiva no-store");
        $this->assert(str_contains($indexPhp, 'max-age=31536000, immutable'), "index.php (CLI server) aplica caché inmutable para assets con ?v=...");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 7. Pruebas Reales en Servidor Apache 2.4 en Vivo
    // ─────────────────────────────────────────────────────────────────────────
    private function testRealApacheServerIntegration(): void {
        echo "\n--- 7. Pruebas de Integración con Apache 2.4 Real ---\n";

        $httpdPaths = [
            'C:\\xampp\\apache\\bin\\httpd.exe',
            'C:\\Program Files\\Apache Group\\Apache2\\bin\\httpd.exe',
            'C:\\Apache24\\bin\\httpd.exe'
        ];

        $httpdExe = null;
        foreach ($httpdPaths as $path) {
            if (file_exists($path)) {
                $httpdExe = $path;
                break;
            }
        }

        if (!$httpdExe) {
            $this->skip("Apache 2.4 configtest y sintaxis", "Binario de Apache (httpd.exe / apachectl) no encontrado en el sistema");
            $this->skip("Apache 2.4 página dinámica (HTTP 200 y 1 sola CSP)", "Servidor Apache no disponible");
            $this->skip("Apache 2.4 asset con ?v= (inmutable 1 año)", "Servidor Apache no disponible");
            $this->skip("Apache 2.4 asset sin versión (revalidable 1 hora)", "Servidor Apache no disponible");
            $this->skip("Apache 2.4 página HTML/API (no-store)", "Servidor Apache no disponible");
            return;
        }

        // 1. Configtest
        exec("\"$httpdExe\" -t 2>&1", $cfgOut, $cfgCode);
        $this->assert($cfgCode === 0, "Apache 2.4 configtest / sintaxis OK (Código {$cfgCode})");

        // 2. Iniciar servidor Apache temporal en puerto 8897 para pruebas reales de cabeceras
        $tempConf = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pmo_httpd_test.conf';
        $baseConfPath = dirname($httpdExe, 2) . '/conf/httpd.conf';
        
        if (!file_exists($baseConfPath)) {
            $this->skip("Pruebas HTTP en Apache en vivo", "Archivo base httpd.conf no encontrado en " . $baseConfPath);
            return;
        }

        $conf = file_get_contents($baseConfPath);
        $conf = preg_replace('/^Listen\s+\d+/m', 'Listen 8897', $conf);
        $conf = preg_replace('/^ServerName\s+.*/m', 'ServerName 127.0.0.1:8897', $conf);
        $docRoot = str_replace('\\', '/', $this->rootDir);
        $conf = preg_replace('/DocumentRoot\s+"[^"]+"/', "DocumentRoot \"{$docRoot}\"", $conf);
        $conf = preg_replace('/<Directory\s+"[^"]+htdocs[^"]*">.*?<\/Directory>/s', '', $conf);
        $conf .= "\n<Directory \"{$docRoot}\">\n    Options Indexes FollowSymLinks ExecCGI\n    AllowOverride All\n    Require all granted\n</Directory>\n";

        file_put_contents($tempConf, $conf);

        $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $proc = @proc_open("\"$httpdExe\" -f \"$tempConf\"", $descriptors, $pipes);

        if (!is_resource($proc)) {
            $this->skip("Pruebas HTTP en Apache en vivo", "No se pudo iniciar el proceso de Apache temporal");
            return;
        }

        usleep(1500000); // Esperar 1.5s a que enlace el puerto

        $getApacheHeaders = function(string $url): array {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Forwarded-Proto: https']);
            $response = curl_exec($ch);
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $headersText = substr((string)$response, 0, $headerSize);
            curl_close($ch);

            return ['code' => $httpCode, 'headers_text' => $headersText];
        };

        // Test A: Página dinámica (HTTP 200 y una sola CSP)
        $resDyn = $getApacheHeaders('http://127.0.0.1:8897/evaluacion-habilidades');
        $cspOccurrences = substr_count(strtolower($resDyn['headers_text']), 'content-security-policy:');
        $this->assert($resDyn['code'] === 200 && $cspOccurrences === 1, "Apache 2.4 página dinámica responde HTTP {$resDyn['code']} con exactamente 1 cabecera CSP (sin duplicación)");

        // Test B: Asset con ?v= (inmutable 1 año)
        $resVer = $getApacheHeaders('http://127.0.0.1:8897/css/styles.css?v=1787863607');
        $isImmutable = str_contains($resVer['headers_text'], 'max-age=31536000') && str_contains($resVer['headers_text'], 'immutable');
        $this->assert($resVer['code'] === 200 && $isImmutable, "Apache 2.4 asset con ?v= responde HTTP 200 con Cache-Control: public, max-age=31536000, immutable");

        // Test C: Asset sin versión (revalidable 1 hora)
        $resUnv = $getApacheHeaders('http://127.0.0.1:8897/css/styles.css');
        $isShortReval = str_contains($resUnv['headers_text'], 'max-age=3600') && str_contains($resUnv['headers_text'], 'must-revalidate') && !str_contains($resUnv['headers_text'], 'immutable');
        $this->assert($resUnv['code'] === 200 && $isShortReval, "Apache 2.4 asset sin versión responde HTTP 200 con Cache-Control: public, max-age=3600, must-revalidate");

        // Test D: Página HTML/API (no-store)
        $isNoStore = str_contains($resDyn['headers_text'], 'no-store') && str_contains($resDyn['headers_text'], 'must-revalidate');
        $this->assert($isNoStore, "Apache 2.4 página HTML/API emite Cache-Control con directiva no-store");

        // Limpieza de proceso Apache
        if (is_array($pipes)) {
            foreach ($pipes as $p) {
                if (is_resource($p)) @fclose($p);
            }
        }
        @proc_terminate($proc);
        @proc_close($proc);
        if (PHP_OS_FAMILY === 'Windows') {
            @exec("taskkill /F /IM httpd.exe /T 2>NUL");
        }
        @unlink($tempConf);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 8. Auditoría y Reglas del Service Worker (sw.js)
    // ─────────────────────────────────────────────────────────────────────────
    private function testServiceWorkerRules(): void {
        echo "\n--- 8. Auditoría de Reglas del Service Worker (sw.js) ---\n";

        $swJs = (string)file_get_contents($this->rootDir . '/js/sw.js');

        $this->assert(str_contains($swJs, "CACHE_VERSION    = 'pmo-v2.2.0'"), "Versión del Service Worker actualizada a pmo-v2.2.0");
        $this->assert(!str_contains($swJs, "'/'") && !str_contains($swJs, "'/index.php'"), "PRECACHE_ASSETS excluye la raíz '/' y páginas dinámicas");
        $this->assert(str_contains($swJs, '/js/vendor/chart.umd.min.js'), "PRECACHE_ASSETS precachea Chart.js local");
        $this->assert(str_contains($swJs, '/js/vendor/jspdf.umd.min.js'), "PRECACHE_ASSETS precachea jsPDF local");

        $this->assert(!str_contains($swJs, '/frontend/404.html'), "PRECACHE_ASSETS y fallback no contienen referencias a /frontend/404.html");
        $this->assert(str_contains($swJs, 'NETWORK_ONLY_PATTERNS'), "NETWORK_ONLY_PATTERNS definido para evitar caché en formularios y APIs");
        $this->assert(str_contains($swJs, '!cc.includes(\'no-store\')') && str_contains($swJs, '!cc.includes(\'private\')'), "Service Worker omite almacenar respuestas con directiva no-store o private");
        $this->assert(str_contains($swJs, 'caches.delete(name)'), "Service Worker elimina cachés obsoletas durante evento activate");
    }
}

// Ejecución
$test = new FrontendAssetsAndCacheTest();
$test->run();