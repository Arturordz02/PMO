<?php
/**
 * PMO SOLUTIONS — Control Automático de Calidad Pre-Despliegue (Quality Gate)
 *
 * Ejecuta validación integral del sistema en 12 fases:
 *  1. Entorno de ejecución & extensiones PHP requeridas
 *  2. Linting de sintaxis PHP (php -l)
 *  3. Linting de sintaxis JavaScript (node --check)
 *  4. Auditoría de seguridad & archivos prohibidos / rutas absolutas
 *  5. Detección de código muerto y referencias obsoletas
 *  6. Integridad criptográfica de dependencias vendor (SHA-256)
 *  7. Existencia de esquemas, migraciones y archivos estructurales
 *  8. Desactivación de paneles administrativos y endpoints de consulta
 *  9. Coherencia normalizada de directivas CSP (Apache vs PHP)
 * 10. Validación física de PRECACHE_ASSETS en Service Worker
 * 11. Ejecución y agregación de todas las suites de prueba (PASS/FAIL/SKIP)
 * 12. Generación de reportes de auditoría en storage/reports/ (JSON & TXT)
 *
 * Uso CLI:
 *   php artisan/quality-gate.php [ruta_proyecto_opcional]
 */

declare(strict_types=1);

$projectDir = isset($argv[1]) && trim($argv[1]) !== '' && is_dir(trim($argv[1]))
    ? realpath(trim($argv[1]))
    : dirname(__DIR__);

if (!$projectDir || !is_dir($projectDir)) {
    fwrite(STDERR, "Error: Directorio de proyecto inválido o inexistente: {$projectDir}\n");
    exit(1);
}

// Normalizar separadores de ruta
$projectDir = str_replace('\\', '/', $projectDir);

require_once $projectDir . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();

echo "\n======================================================================\n";
echo " PMO SOLUTIONS — CONTROL AUTOMÁTICO DE CALIDAD (QUALITY GATE) v1.0\n";
echo " Raíz del proyecto : {$projectDir}\n";
echo " Fecha y Hora      : " . date('Y-m-d H:i:s P') . "\n";
echo " Versión PHP       : " . PHP_VERSION . "\n";
echo "======================================================================\n\n";

$metrics = [
    'passed'   => 0,
    'failed'   => 0,
    'skipped'  => 0,
    'warnings' => 0,
];

$details = [];
$failures = [];
$warnings = [];

function recordCheck(string $phase, string $name, string $status, string $message = ''): void {
    global $metrics, $details, $failures, $warnings;

    $status = strtoupper($status);
    $entry = [
        'phase'   => $phase,
        'name'    => $name,
        'status'  => $status,
        'message' => $message,
    ];
    $details[] = $entry;

    if ($status === 'PASS') {
        $metrics['passed']++;
        echo "  \033[32m✔ [PASS]\033[0m [{$phase}] {$name}" . ($message ? " — {$message}" : "") . "\n";
    } elseif ($status === 'SKIP') {
        $metrics['skipped']++;
        echo "  \033[33m⚡ [SKIP]\033[0m [{$phase}] {$name}" . ($message ? " — {$message}" : "") . "\n";
    } elseif ($status === 'WARN') {
        $metrics['warnings']++;
        $warnings[] = "[{$phase}] {$name}: {$message}";
        echo "  \033[33m▲ [WARN]\033[0m [{$phase}] {$name}" . ($message ? " — {$message}" : "") . "\n";
    } else {
        $metrics['failed']++;
        $failures[] = "[{$phase}] {$name}: {$message}";
        echo "  \033[31m✖ [FAIL]\033[0m [{$phase}] {$name}" . ($message ? " — {$message}" : "") . "\n";
    }
}

/**
 * Lee de forma segura el contenido de un archivo verificando existencia y permisos de lectura.
 * Maneja warnings temporalmente sin silenciadores '@' y registra FAIL en caso de error.
 * Distingue explícitamente entre archivo vacío válido ('') y fallo de lectura (null).
 *
 * @param string $filePath Ruta absoluta o relativa al archivo
 * @param string $phase Identificador de fase opcional para registrar FAIL
 * @param string $contextName Nombre descriptivo del chequeo
 * @return string|null Contenido leído en string (incluso si es ''), o null si la lectura falló
 */
function safeReadFile(string $filePath, string $phase = '', string $contextName = ''): ?string {
    if (!file_exists($filePath)) {
        if ($phase !== '' && $contextName !== '') {
            recordCheck($phase, $contextName, 'FAIL', "El archivo no existe: {$filePath}");
        }
        return null;
    }

    if (!is_readable($filePath)) {
        if ($phase !== '' && $contextName !== '') {
            recordCheck($phase, $contextName, 'FAIL', "El archivo no tiene permisos de lectura: {$filePath}");
        }
        return null;
    }

    $lastError = null;
    set_error_handler(function(int $errno, string $errstr) use (&$lastError) {
        $lastError = $errstr;
        return true;
    });

    try {
        $content = file_get_contents($filePath);
    } finally {
        restore_error_handler();
    }

    if ($content === false || $lastError !== null) {
        if ($phase !== '' && $contextName !== '') {
            $errDetail = $lastError ? ": {$lastError}" : "";
            recordCheck($phase, $contextName, 'FAIL', "Error al leer el archivo {$filePath}{$errDetail}");
        }
        return null;
    }

    return $content;
}

// ─────────────────────────────────────────────────────────────────────────────
// FASE 1: Entorno & Extensiones PHP
// ─────────────────────────────────────────────────────────────────────────────
echo "--- 1. Entorno de Ejecución & Extensiones PHP ---\n";

if (version_compare(PHP_VERSION, '8.0.30', '>=')) {
    recordCheck('Fase 1', 'Versión compatible PHP 8.0.30+', 'PASS', 'PHP ' . PHP_VERSION . ' detectado');
} else {
    recordCheck('Fase 1', 'Versión compatible PHP 8.0.30+', 'FAIL', 'Se requiere PHP 8.0.30 o superior (actual: ' . PHP_VERSION . ')');
}

$requiredExtensions = [
    'pdo'        => 'Acceso unificado a base de datos',
    'pdo_mysql'  => 'Driver de base de datos MySQL para producción',
    'openssl'    => 'Criptografía, tokens CSRF y hashing seguro',
    'mbstring'   => 'Manipulación de strings multibyte / UTF-8',
    'json'       => 'Codificación y decodificación de payloads JSON',
    'fileinfo'   => 'Detección confiable de tipos MIME',
    'zip'        => 'Generación e inspección de paquetes ZIP'
];

foreach ($requiredExtensions as $ext => $purpose) {
    if (extension_loaded($ext)) {
        recordCheck('Fase 1', "Extensión PHP: {$ext}", 'PASS', $purpose);
    } else {
        recordCheck('Fase 1', "Extensión PHP: {$ext}", 'FAIL', "Extensión requerida no instalada: {$ext} ({$purpose})");
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// FASE 2: Linting de Sintaxis PHP (php -l)
// ─────────────────────────────────────────────────────────────────────────────
echo "\n--- 2. Linting de Sintaxis PHP (php -l) ---\n";

$phpFiles = [];
$iter = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($projectDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iter as $item) {
    if (!$item->isFile() || strtolower($item->getExtension()) !== 'php') {
        continue;
    }
    $norm = str_replace('\\', '/', $item->getPathname());
    $rel = substr($norm, strlen($projectDir));
    if (str_starts_with($rel, '/storage/cache/') || str_starts_with($rel, '/.git')) {
        continue;
    }
    $phpFiles[] = $norm;
}

$phpLintErrors = 0;
foreach ($phpFiles as $file) {
    $rel = ltrim(substr($file, strlen($projectDir)), '/');
    $cmd = escapeshellarg(PHP_BINARY) . " -l " . escapeshellarg($file) . " 2>&1";
    exec($cmd, $out, $code);
    if ($code !== 0) {
        $phpLintErrors++;
        $errMsg = implode(" ", $out);
        recordCheck('Fase 2', "Sintaxis PHP: {$rel}", 'FAIL', $errMsg);
    }
    unset($out);
}

if ($phpLintErrors === 0) {
    recordCheck('Fase 2', 'Sintaxis PHP en todos los archivos (' . count($phpFiles) . ' archivos)', 'PASS', 'Cero errores de sintaxis');
}

// ─────────────────────────────────────────────────────────────────────────────
// FASE 3: Linting de Sintaxis JavaScript (node --check)
// ─────────────────────────────────────────────────────────────────────────────
echo "\n--- 3. Linting de Sintaxis JavaScript (node --check) ---\n";

exec("node -v 2>&1", $nodeOut, $nodeCode);
$hasNode = ($nodeCode === 0 && !empty($nodeOut));

$jsFilesToCheck = [
    'js/main.js',
    'js/sw.js',
    'js/core/utils.js',
    'js/modules/soft-skills.js',
    'tests/browser_test.js'
];

if (!$hasNode) {
    recordCheck('Fase 3', 'Disponibilidad de Node.js', 'SKIP', 'Node.js no disponible en el sistema. Linting JS omitido sin emitir falso PASS.');
} else {
    $jsLintErrors = 0;
    foreach ($jsFilesToCheck as $jsRel) {
        $fullPath = $projectDir . '/' . $jsRel;
        if (!file_exists($fullPath)) {
            continue;
        }
        $cmd = "node --check " . escapeshellarg($fullPath) . " 2>&1";
        exec($cmd, $jsCheckOut, $jsCheckCode);
        if ($jsCheckCode === 0) {
            recordCheck('Fase 3', "Sintaxis JS: {$jsRel}", 'PASS', 'Sintaxis válida');
        } else {
            $jsLintErrors++;
            $errMsg = implode(" ", $jsCheckOut);
            recordCheck('Fase 3', "Sintaxis JS: {$jsRel}", 'FAIL', $errMsg);
        }
        unset($jsCheckOut);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// FASE 4: Auditoría de Seguridad, Archivos Prohibidos & Rutas Locales
// ─────────────────────────────────────────────────────────────────────────────
echo "\n--- 4. Auditoría de Seguridad & Archivos Prohibidos ---\n";

$forbiddenPatterns = [
    '/^\.env$/i',
    '/^\.env\.(local|production|dev|test)$/i',
    '/^.+\.(zip|rar|tar|gz|7z|bak|backup|dump|tmp)$/i',
];

$forbiddenFound = [];
$localPathsFound = [];

$scanIter = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($projectDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($scanIter as $item) {
    $norm = str_replace('\\', '/', $item->getPathname());
    $rel = substr($norm, strlen($projectDir));
    if (!str_starts_with($rel, '/')) {
        $rel = '/' . $rel;
    }

    $basename = basename($rel);

    // .env.example está EXPLÍCITAMENTE PERMITIDO
    if ($basename === '.env.example') {
        continue;
    }

    // Reportes de quality gate se guardan en storage/reports y se excluyen del ZIP
    if (str_starts_with($rel, '/storage/reports/')) {
        continue;
    }

    if ($item->isFile()) {
        foreach ($forbiddenPatterns as $pat) {
            if (preg_match($pat, $basename)) {
                $forbiddenFound[] = $rel;
            }
        }

        // Inspeccionar contenido en código de producción para rutas locales absolutas
        $isProdCode = str_starts_with($rel, '/app/') ||
                      str_starts_with($rel, '/artisan/') ||
                      str_starts_with($rel, '/css/') ||
                      (str_starts_with($rel, '/js/') && !str_starts_with($rel, '/js/vendor/LICENSE')) ||
                      $rel === '/index.php' ||
                      $rel === '/.htaccess';

        if ($isProdCode) {
            $content = safeReadFile($norm, 'Fase 4', "Lectura de archivo de producción: {$rel}");
            if ($content === null) {
                $localPathsFound[] = "{$rel} (error de lectura)";
                continue;
            }
            // Detectar rutas locales absolutas de Windows/Linux de desarrollador
            if (
                preg_match('/[a-zA-Z]:[\\\\\/](Users|HTML|xampp|wamp|home)[\\\\\/]/i', $content, $m) ||
                preg_match('/\.gemini[\/\\\\]antigravity/i', $content, $m) ||
                preg_match('/\.agent[\/\\\\]/i', $content, $m)
            ) {
                // Excluir auto-referencias seguras de paths dinámicos
                if (!str_contains($content, '__DIR__') || preg_match('/["\'][a-zA-Z]:[\\\\\/]/', $content)) {
                    $localPathsFound[] = "{$rel} (patrón detectado: " . ($m[0] ?? 'ruta absoluta') . ")";
                }
            }
        }
    }
}

if (empty($forbiddenFound)) {
    recordCheck('Fase 4', 'Ausencia de archivos prohibidos (.env, ZIPs, temporales, backups)', 'PASS', 'Ningún archivo sensible o temporal encontrado');
} else {
    recordCheck('Fase 4', 'Ausencia de archivos prohibidos', 'FAIL', 'Archivos prohibidos encontrados: ' . implode(', ', $forbiddenFound));
}

if (empty($localPathsFound)) {
    recordCheck('Fase 4', 'Ausencia de rutas absolutas locales en código de producción', 'PASS', 'Código completamente portable');
} else {
    recordCheck('Fase 4', 'Ausencia de rutas absolutas locales', 'FAIL', 'Rutas absolutas detectadas: ' . implode(', ', $localPathsFound));
}

// ─────────────────────────────────────────────────────────────────────────────
// FASE 5: Detección de Código Muerto y Referencias Obsoletas
// ─────────────────────────────────────────────────────────────────────────────
echo "\n--- 5. Detección de Código Muerto & Referencias Obsoletas ---\n";

$deprecatedTokens = [
    '/frontend/'                  => 'Rutas o assets antiguos de frontend/',
    'backend/send-contact.php'    => 'Script heredado de contacto',
    'backend/submit-claim.php'    => 'Script heredado de reclamos',
    'backend/Database.php'        => 'Clase Database legacy fuera de App\Core',
    'backend/Security.php'        => 'Clase Security legacy fuera de App\Core',
    'backend/SmtpMailer.php'      => 'Clase SmtpMailer legacy fuera de App\Core',
    'niubizCheckoutModal'         => 'Modal residual de pasarela Niubiz',
    'payment-brand-logos'         => 'Logos de tarjetas en footer deshabilitados',
    'payment-modal'               => 'Plantilla eliminada de pasarela de pago',
    'UserAuthController'          => 'Controlador de cuentas de usuario eliminado',
    'UserModel'                   => 'Modelo de usuarios eliminado',
    'UserSession'                 => 'Manejador de sesión de usuario eliminado',
    'user_accounts_enabled'       => 'Bandera obsoleta de cuentas de usuario',
    'PMO_USER_ACCOUNTS_ENABLED'   => 'Variable de entorno de cuentas de usuario eliminada'
];

$deadCodeErrors = 0;
foreach ($deprecatedTokens as $token => $desc) {
    $foundIn = [];
    foreach ($phpFiles as $file) {
        $rel = ltrim(substr($file, strlen($projectDir)), '/');
        // Excluir tests de verificación y scripts artisan de empaquetado/política/pre-deploy
        if (
            str_starts_with($rel, 'tests/') ||
            $rel === 'artisan/quality-gate.php' ||
            $rel === 'artisan/create_production_zip.php' ||
            $rel === 'artisan/PackagingPolicy.php' ||
            $rel === 'artisan/pre-deploy-check.php'
        ) {
            continue;
        }
        $content = safeReadFile($file, 'Fase 5', "Lectura de archivo PHP: {$rel}");
        if ($content === null) {
            $deadCodeErrors++;
            continue;
        }
        if (str_contains($content, $token)) {
            $foundIn[] = $rel;
        }
    }

    if (empty($foundIn)) {
        recordCheck('Fase 5', "Ausencia de '{$token}' ({$desc})", 'PASS');
    } else {
        $deadCodeErrors++;
        recordCheck('Fase 5', "Ausencia de '{$token}' ({$desc})", 'FAIL', 'Presente en: ' . implode(', ', $foundIn));
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// FASE 6: Integridad Criptográfica de Dependencias Vendor (SHA-256)
// ─────────────────────────────────────────────────────────────────────────────
echo "\n--- 6. Integridad Criptográfica de Dependencias Vendor (SHA-256) ---\n";

$vendorHashes = [
    'js/vendor/chart.umd.min.js' => '0e2326c6868072bec1592760c6729043caeea2960a2b46cee6a2192aac6abff0',
    'js/vendor/jspdf.umd.min.js' => '98ccf17aa10c20bb1301762618fcc9b6ab3a4e7f26b6071d64d0b41154df3875'
];

foreach ($vendorHashes as $vFile => $expectedHash) {
    $fullVPath = $projectDir . '/' . $vFile;
    if (!file_exists($fullVPath)) {
        recordCheck('Fase 6', "Existencia de {$vFile}", 'FAIL', 'Archivo vendor no encontrado');
        continue;
    }

    $actualHash = hash_file('sha256', $fullVPath);
    if ($actualHash === $expectedHash) {
        recordCheck('Fase 6', "Integridad SHA-256: {$vFile}", 'PASS', "Hash coincidente ({$actualHash})");
    } else {
        recordCheck('Fase 6', "Integridad SHA-256: {$vFile}", 'FAIL', "Hash no coincide (esperado: {$expectedHash}, actual: {$actualHash})");
    }
}

// Verificación de licencias vendor
$licenseFiles = [
    'js/vendor/LICENSE-chartjs.txt' => 'Licencia MIT de Chart.js',
    'js/vendor/LICENSE-jspdf.txt'   => 'Licencia MIT de jsPDF',
    'js/vendor/README.md'           => 'Documentación de versión y origen vendor'
];

foreach ($licenseFiles as $lFile => $lDesc) {
    if (file_exists($projectDir . '/' . $lFile)) {
        recordCheck('Fase 6', "Existencia de {$lFile}", 'PASS', $lDesc);
    } else {
        recordCheck('Fase 6', "Existencia de {$lFile}", 'FAIL', "Archivo requerido ausente: {$lFile}");
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// FASE 7: Existencia de Esquemas, Migraciones & Archivos Críticos
// ─────────────────────────────────────────────────────────────────────────────
echo "\n--- 7. Existencia de Esquemas, Migraciones & Archivos Críticos ---\n";

$essentialFiles = [
    'backend/schema.sql'                                       => 'Esquema base MySQL',
    'backend/schema_v2.sql'                                    => 'Esquema MySQL v2.0',
    'backend/migrations/001_add_report_access_token_hash.sql'  => 'Migración 001',
    'backend/migrations/001_rollback.sql'                      => 'Rollback Migración 001',
    'backend/migrations/002_add_idempotency_and_outbox.sql'    => 'Migración 002',
    'backend/migrations/002_rollback.sql'                      => 'Rollback Migración 002',
    'backend/.htaccess'                                        => 'Bloqueo estricto del directorio backend/',
    'storage/.htaccess'                                        => 'Bloqueo de almacenamiento interno',
    'storage/outbox/.htaccess'                                 => 'Protección de cola outbox',
    'modules/chatbot/chatbot-view.php'                         => 'Vista principal del chatbot',
    'modules/chatbot/chatbot.css'                              => 'Estilos del chatbot',
    'modules/chatbot/chatbot.js'                               => 'Lógica del chatbot',
    'modules/chatbot/config.php'                               => 'Configuración del chatbot',
    'modules/chatbot/knowledge.json'                           => 'Base de conocimiento del chatbot',
    'modules/chatbot/.htaccess'                                => 'Protección del chatbot',
    'artisan/process-outbox.php'                               => 'Worker de procesamiento outbox',
    'artisan/archive-logs.php'                                 => 'Worker de rotación de logs',
    'artisan/quality-gate.php'                                 => 'Control de calidad pre-despliegue',
    'artisan/pre-deploy-check.php'                             => 'Verificación pre-despliegue local',
    'artisan/create_production_zip.php'                        => 'Generador de paquetes de producción',
    'artisan/PackagingPolicy.php'                              => 'Política centralizada de empaquetado',
    'DEPLOYMENT.md'                                            => 'Guía de despliegue en producción',
    'robots.txt'                                               => 'Directivas SEO y rastreadores',
    'sitemap.xml'                                              => 'Mapa del sitio web XML',
    'README.md'                                                => 'Documentación del proyecto',
    '.env.example'                                             => 'Plantilla de configuración'
];

foreach ($essentialFiles as $eFile => $eDesc) {
    if (file_exists($projectDir . '/' . $eFile)) {
        recordCheck('Fase 7', "Archivo esencial: {$eFile}", 'PASS', $eDesc);
    } else {
        recordCheck('Fase 7', "Archivo esencial: {$eFile}", 'FAIL', "Archivo indispensable ausente: {$eFile}");
    }
}

// Validación de Política y Manifiesto Calculado de Producción
require_once $projectDir . '/artisan/PackagingPolicy.php';
$prodManifest = \Artisan\PackagingPolicy::calculateProductionManifest($projectDir);
$manifestErrors = \Artisan\PackagingPolicy::validateProductionEntries($prodManifest, $projectDir);

if (empty($manifestErrors)) {
    recordCheck('Fase 7', 'Política y manifiesto calculado de producción', 'PASS', 'Manifiesto de ' . count($prodManifest) . ' archivos cumple 100% las reglas de inclusión y exclusión');
} else {
    recordCheck('Fase 7', 'Política y manifiesto calculado de producción', 'FAIL', implode('; ', $manifestErrors));
}

// Verificación estricta de ausencia en disco de archivos del prototipo de usuarios eliminado
$forbiddenFoundOnDisk = [];
foreach (\Artisan\PackagingPolicy::FORBIDDEN_USER_FILES as $fFile) {
    if (file_exists($projectDir . '/' . $fFile)) {
        $forbiddenFoundOnDisk[] = $fFile;
    }
}
if (empty($forbiddenFoundOnDisk)) {
    recordCheck('Fase 7', 'Ausencia en disco de archivos de cuentas de usuario eliminadas', 'PASS', 'Los ' . count(\Artisan\PackagingPolicy::FORBIDDEN_USER_FILES) . ' archivos del prototipo no existen');
} else {
    recordCheck('Fase 7', 'Ausencia en disco de archivos de cuentas de usuario eliminadas', 'FAIL', 'Archivos eliminados encontrados en disco: ' . implode(', ', $forbiddenFoundOnDisk));
}

// ─────────────────────────────────────────────────────────────────────────────
// FASE 8: Estado Desactivado de Administración & Reportes Posteriores
// ─────────────────────────────────────────────────────────────────────────────
echo "\n--- 8. Estado Desactivado de Administración & Reportes ---\n";

$disabledRoutes = [
    ['GET', '/admin/login'],
    ['POST', '/admin/login'],
    ['POST', '/admin/logout'],
    ['GET', '/evaluacion-habilidades/resultados'],
    ['GET', '/api/evaluacion-habilidades/CODIGO-TEST-123'],
    ['GET', '/registro'],
    ['POST', '/registro'],
    ['GET', '/login'],
    ['POST', '/login'],
    ['GET', '/mi-cuenta'],
    ['POST', '/logout']
];

$configFile = $projectDir . '/app/Config/config.php';
$config = file_exists($configFile) ? require $configFile : [];

$adminEnabled = $config['features']['admin_enabled'] ?? null;
$reportRetrievalEnabled = $config['features']['report_retrieval_enabled'] ?? null;

if ($adminEnabled === false) {
    recordCheck('Fase 8', 'Feature Flag: admin_enabled deshabilitado', 'PASS', 'Configuración en false');
} else {
    recordCheck('Fase 8', 'Feature Flag: admin_enabled deshabilitado', 'FAIL', 'admin_enabled no es false');
}

if ($reportRetrievalEnabled === false) {
    recordCheck('Fase 8', 'Feature Flag: report_retrieval_enabled deshabilitado', 'PASS', 'Configuración en false');
} else {
    recordCheck('Fase 8', 'Feature Flag: report_retrieval_enabled deshabilitado', 'FAIL', 'report_retrieval_enabled no es false');
}

if (!array_key_exists('user_accounts_enabled', $config['features'] ?? [])) {
    recordCheck('Fase 8', 'Feature Flag: user_accounts_enabled eliminado de config.php', 'PASS', 'Configuración de cuentas de usuario no existe');
} else {
    recordCheck('Fase 8', 'Feature Flag: user_accounts_enabled eliminado de config.php', 'FAIL', 'user_accounts_enabled sigue presente en config.php');
}

foreach ($disabledRoutes as $dr) {
    [$m, $uri] = $dr;
    $script = '<?php
define("PMO_APP_ACCESS", true);
putenv("PMO_APP_ENV=development");
$_ENV["PMO_APP_ENV"] = "development";
$_SERVER["REQUEST_METHOD"] = "' . $m . '";
$_SERVER["REQUEST_URI"] = "' . $uri . '";
$_SERVER["SCRIPT_NAME"] = "/index.php";
$_SERVER["REMOTE_ADDR"] = "127.0.0.1";
$_SERVER["HTTP_HOST"] = "localhost";
$_SERVER["HTTP_ACCEPT"] = "application/json";

register_shutdown_function(function() {
    echo "\n___HTTP_STATUS___:" . http_response_code() . "\n";
});

require_once "' . $projectDir . '/app/Core/Autoloader.php";
\App\Core\Autoloader::register();

ob_start();
require "' . $projectDir . '/index.php";
ob_end_clean();
';
    $tmp = sys_get_temp_dir() . '/pmo_qg_route_' . md5($m . $uri) . '.php';
    file_put_contents($tmp, $script);
    $phpBin = PHP_BINARY;
    $out = (string)shell_exec("\"{$phpBin}\" \"{$tmp}\" 2>&1");
    if (file_exists($tmp)) {
        unlink($tmp);
    }

    preg_match('/___HTTP_STATUS___:(\d+)/', $out, $mCode);
    $statusCode = (int)($mCode[1] ?? 0);

    if ($statusCode === 404) {
        recordCheck('Fase 8', "Ruta deshabilitada: {$m} {$uri}", 'PASS', "HTTP 404 Not Found verificado");
    } else {
        recordCheck('Fase 8', "Ruta deshabilitada: {$m} {$uri}", 'FAIL', "Esperado HTTP 404, obtenido: {$statusCode}");
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// FASE 9: Coherencia Normalizada de Política CSP (Apache vs PHP)
// ─────────────────────────────────────────────────────────────────────────────
echo "\n--- 9. Coherencia Normalizada de Política CSP (Apache vs PHP) ---\n";

function parseCspDirectives(string $cspString): array {
    $directives = [];
    $parts = explode(';', $cspString);
    foreach ($parts as $part) {
        $part = trim($part);
        if ($part === '') continue;
        $tokens = preg_split('/\s+/', $part);
        $dirName = array_shift($tokens);
        if ($dirName) {
            sort($tokens);
            $directives[$dirName] = implode(' ', $tokens);
        }
    }
    ksort($directives);
    return $directives;
}

$htaccessContent = safeReadFile($projectDir . '/.htaccess', 'Fase 9', 'Lectura de .htaccess');
$securityContent = safeReadFile($projectDir . '/app/Core/Security.php', 'Fase 9', 'Lectura de app/Core/Security.php');
$controllerContent = safeReadFile($projectDir . '/app/Core/Controller.php', 'Fase 9', 'Lectura de app/Core/Controller.php');

if ($htaccessContent === null || $securityContent === null || $controllerContent === null) {
    recordCheck('Fase 9', 'Lectura de directivas CSP', 'FAIL', 'No se pudieron leer todos los archivos requeridos para auditar CSP (.htaccess, Security.php, Controller.php)');
} else {
    preg_match('/Header always set Content-Security-Policy\s+"([^"\r\n]+)"/', $htaccessContent, $mHt);
    preg_match('/header\(\s*["\']Content-Security-Policy:\s*(.+?)["\']\s*\)\s*;/s', $securityContent, $mSec);
    preg_match('/header\(\s*["\']Content-Security-Policy:\s*(.+?)["\']\s*\)\s*;/s', $controllerContent, $mCtrl);

    $htCspStr = $mHt[1] ?? '';
    $secCspStr = $mSec[1] ?? '';
    $ctrlCspStr = $mCtrl[1] ?? '';

    $htDirectives = parseCspDirectives($htCspStr);
    $secDirectives = parseCspDirectives($secCspStr);
    $ctrlDirectives = parseCspDirectives($ctrlCspStr);

    if (!empty($htDirectives) && $secDirectives === $ctrlDirectives && $secDirectives === $htDirectives) {
        recordCheck('Fase 9', 'Coherencia CSP entre Apache (.htaccess) y PHP (Security/Controller)', 'PASS', 'Directivas normalizadas 100% idénticas');
    } else {
        $diff = [];
        $allKeys = array_unique(array_merge(array_keys($htDirectives), array_keys($secDirectives), array_keys($ctrlDirectives)));
        foreach ($allKeys as $k) {
            $v1 = $htDirectives[$k] ?? '<ausente>';
            $v2 = $secDirectives[$k] ?? '<ausente>';
            if ($v1 !== $v2) {
                $diff[] = "Directiva '{$k}': Apache='{$v1}' vs PHP='{$v2}'";
            }
        }
        recordCheck('Fase 9', 'Coherencia CSP entre Apache y PHP', 'FAIL', !empty($diff) ? implode("; ", $diff) : 'No se detectaron directivas CSP válidas');
    }

    $frameSrcTokens = $htDirectives['frame-src'] ?? '';
    if (str_contains($frameSrcTokens, "'self'") && str_contains($frameSrcTokens, 'https://www.google.com') && str_contains($frameSrcTokens, 'https://maps.google.com')) {
        recordCheck('Fase 9', 'Directiva frame-src para Google Maps', 'PASS', $frameSrcTokens);
    } else {
        recordCheck('Fase 9', 'Directiva frame-src para Google Maps', 'FAIL', "frame-src incompleto: '{$frameSrcTokens}'");
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// FASE 10: Validación Física de PRECACHE_ASSETS en Service Worker
// ─────────────────────────────────────────────────────────────────────────────
echo "\n--- 10. Validación de PRECACHE_ASSETS en Service Worker ---\n";

$swContent = safeReadFile($projectDir . '/js/sw.js', 'Fase 10', 'Lectura de Service Worker (js/sw.js)');

if ($swContent === null) {
    recordCheck('Fase 10', 'Lectura de Service Worker (js/sw.js)', 'FAIL', 'No se pudo leer js/sw.js');
} else {
    preg_match('/const\s+PRECACHE_ASSETS\s*=\s*\[(.*?)\];/s', $swContent, $mSw);

    $swAssetsRaw = $mSw[1] ?? '';
    preg_match_all('/[\'"](\/[^\'"]+)[\'"]/', $swAssetsRaw, $mAssets);
    $swAssets = $mAssets[1] ?? [];

    $missingSwAssets = [];
    foreach ($swAssets as $assetPath) {
        $diskPath = $projectDir . $assetPath;
        if (!file_exists($diskPath)) {
            $missingSwAssets[] = $assetPath;
        }
    }

    if (empty($missingSwAssets) && !empty($swAssets)) {
        recordCheck('Fase 10', 'Existencia física de PRECACHE_ASSETS (' . count($swAssets) . ' assets)', 'PASS', 'Todos los assets existen en disco');
    } else {
        recordCheck('Fase 10', 'Existencia física de PRECACHE_ASSETS', 'FAIL', empty($swAssets) ? 'No se encontraron assets en PRECACHE_ASSETS' : 'Assets ausentes: ' . implode(', ', $missingSwAssets));
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// FASE 11: Ejecución y Agregación de Todas las Suites de Prueba
// ─────────────────────────────────────────────────────────────────────────────
echo "\n--- 11. Ejecución y Agregación de Suites de Prueba ---\n";

/**
 * Parsea la salida y estado de una suite de pruebas, eliminando códigos ANSI
 * y validando consistencia matemática entre métricas declaradas y aserciones.
 */
function parseTestSuiteMetrics(string $rawOutput, int $exitCode, string $suiteFile): array {
    // 1. Eliminar códigos ANSI de escape
    $cleanOutput = preg_replace('/\x1b\[[0-9;]*[a-zA-Z]/', '', $rawOutput);
    $cleanOutput = preg_replace('/\e\[[0-9;]*[a-zA-Z]/', '', (string)$cleanOutput);

    $passed = 0;
    $failed = 0;
    $skipped = 0;
    $declaredTotal = null;
    $hasSummary = false;

    // Formato 1 (SoftSkillsTest): "Tests ejecutados: 181 | Pasados: 176 | Fallados: 0 | Omitidos: 5"
    if (preg_match('/Tests\s+ejecutados:\s*(\d+)\s*\|\s*Pasados:\s*(\d+)\s*\|\s*Fallados:\s*(\d+)\s*\|\s*Omitidos:\s*(\d+)/i', (string)$cleanOutput, $m)) {
        $declaredTotal = (int)$m[1];
        $passed = (int)$m[2];
        $failed = (int)$m[3];
        $skipped = (int)$m[4];
        $hasSummary = true;
    }
    // Formato 2 (DatabaseSchemaTest / FrontendAssets): "Total tests: 55 | Pasados: 55 | Fallados: 0 | Omitidos (SKIP): 0"
    elseif (preg_match('/Total\s+tests:\s*(\d+)\s*\|\s*Pasados:\s*(\d+)\s*\|\s*Fallados:\s*(\d+)\s*\|\s*Omitidos[^:]*:\s*(\d+)/i', (string)$cleanOutput, $m)) {
        $declaredTotal = (int)$m[1];
        $passed = (int)$m[2];
        $failed = (int)$m[3];
        $skipped = (int)$m[4];
        $hasSummary = true;
    }
    // Formato 3 (PersistenceReliabilityTest): "Total de pruebas ejecutadas : 24 ... Pruebas exitosas (PASS) : 17 ... Pruebas omitidas (SKIP) : 7 ... Pruebas fallidas (FAIL) : 0"
    elseif (
        preg_match('/Total\s+de\s+pruebas\s+ejecutadas\s*:\s*(\d+)/i', (string)$cleanOutput, $mTot) &&
        preg_match('/Pruebas\s+exitosas[^\d:]*:\s*(\d+)/i', (string)$cleanOutput, $mPass) &&
        preg_match('/Pruebas\s+fallidas[^\d:]*:\s*(\d+)/i', (string)$cleanOutput, $mFail)
    ) {
        $declaredTotal = (int)$mTot[1];
        $passed = (int)$mPass[1];
        $failed = (int)$mFail[1];
        $skipped = preg_match('/Pruebas\s+omitidas[^\d:]*:\s*(\d+)/i', (string)$cleanOutput, $mSkip) ? (int)$mSkip[1] : 0;
        $hasSummary = true;
    }
    // Formato 4 (Conteo individual por marcadores)
    else {
        $passed = preg_match_all('/(✔\s*\[PASS\]|^\s*PASS\s+)/um', (string)$cleanOutput);
        $failed = preg_match_all('/(✖\s*\[FAIL\]|^\s*FAIL\s+)/um', (string)$cleanOutput);
        $skipped = preg_match_all('/((⚡|↷)\s*\[SKIP\]|^\s*SKIP\s+)/um', (string)$cleanOutput);

        if (preg_match('/Pasados:\s*(\d+)\s*\|\s*Fallados:\s*(\d+)/i', (string)$cleanOutput, $mRes)) {
            $passed = max($passed, (int)$mRes[1]);
            $failed = max($failed, (int)$mRes[2]);
            $hasSummary = true;
        }
    }

    // Detectar texto indicativo de fallo explícito
    $hasExplicitFailText = false;
    if (
        preg_match('/(?:✖\s*\[FAIL\]|Fallados:\s*[1-9]\d*|Pruebas\s+fallidas[^\d:]*:\s*[1-9]\d*|SE ENCONTRARON FALLOS|Fatal error:|Parse error:|✗\s+Algunos tests fallaron)/i', (string)$cleanOutput) ||
        preg_match('/^\s*FAIL\s+/m', (string)$cleanOutput)
    ) {
        $hasExplicitFailText = true;
    }

    $calculatedTotal = $passed + $failed + $skipped;

    // Validación 1: Código de salida del subproceso distinto de cero
    if ($exitCode !== 0) {
        return [
            'status'  => 'FAIL',
            'passed'  => $passed,
            'failed'  => max(1, $failed),
            'skipped' => $skipped,
            'reason'  => "El subproceso finalizó con código de salida {$exitCode}"
        ];
    }

    // Validación 2: Texto indica fallo explícito
    if ($hasExplicitFailText && $failed === 0) {
        return [
            'status'  => 'FAIL',
            'passed'  => $passed,
            'failed'  => 1,
            'skipped' => $skipped,
            'reason'  => "Se detectaron indicadores de fallo en la salida de la suite"
        ];
    }

    // Validación 3: Si se declaró un total, verificar coincidencia matemática
    if ($hasSummary && $declaredTotal !== null && $declaredTotal !== $calculatedTotal) {
        return [
            'status'  => 'FAIL',
            'passed'  => $passed,
            'failed'  => max(1, $failed),
            'skipped' => $skipped,
            'reason'  => "Inconsistencia en total de pruebas: declarado {$declaredTotal} vs calculado {$calculatedTotal} (PASS: {$passed}, FAIL: {$failed}, SKIP: {$skipped})"
        ];
    }

    // Validación 4: Cero métricas reconocidas (salida vacía o sin tests)
    if ($calculatedTotal === 0) {
        // Verificar si la suite declaró explícitamente 0 tests ejecutados
        if (!($hasSummary && $declaredTotal === 0)) {
            return [
                'status'  => 'FAIL',
                'passed'  => 0,
                'failed'  => 1,
                'skipped' => 0,
                'reason'  => "Cero métricas reconocidas en la salida de la suite sin declaración explícita de 0 pruebas"
            ];
        }
    }

    // Validación 5: Si hay fallos registrados
    if ($failed > 0) {
        return [
            'status'  => 'FAIL',
            'passed'  => $passed,
            'failed'  => $failed,
            'skipped' => $skipped,
            'reason'  => "La suite reportó {$failed} pruebas fallidas"
        ];
    }

    return [
        'status'  => 'PASS',
        'passed'  => $passed,
        'failed'  => 0,
        'skipped' => $skipped,
        'reason'  => "PASS: {$passed}" . ($skipped > 0 ? ", SKIP: {$skipped}" : "")
    ];
}

$testSuites = [
    'AuthTest.php'                   => 'Autenticación y Sesiones',
    'ConfigAndSecretsTest.php'       => 'Configuración y Secretos',
    'CsrfAndOriginTest.php'          => 'Protección CSRF y Origen',
    'DatabaseSchemaTest.php'         => 'Esquema de Base de Datos MySQL',
    'FrontendAssetsAndCacheTest.php' => 'Assets Frontend, CSP y Caché',
    'PersistenceReliabilityTest.php' => 'Persistencia, Idempotencia y Outbox',
    'PreDeployCheckTest.php'         => 'Verificación Pre-Despliegue (Tarea 11)',
    'ProjectCleanupTest.php'         => 'Limpieza de Proyecto y Empaquetado',
    'RateLimitAndValidationTest.php' => 'Rate Limiting y Validaciones',
    'ReportAuthorizationTest.php'    => 'Autorización de Reportes',
    'SecurityTest.php'               => 'Seguridad y Sanitización',
    'SoftSkillsTest.php'             => 'Evaluación de Habilidades Blandas',
    'route_test.php'                 => 'Cobertura Integral de Rutas MVC',
    'ProductionAuditTest.php'        => 'Auditoría de Dominio, Zonas Horarias y Seguridad',
    'modules/chatbot/tests/ChatbotTest.php' => 'Chatbot Guiado PMO Solutions'
];

// EXCLUIR QualityGateTest.php para evitar recursión circular infinita
$testSummary = [
    'suites_run'    => 0,
    'suites_passed' => 0,
    'suites_failed' => 0,
    'assertions'    => [
        'passed'  => 0,
        'failed'  => 0,
        'skipped' => 0
    ]
];

putenv('PMO_TEST_RUNNER=1');
$_ENV['PMO_TEST_RUNNER'] = '1';
putenv('PMO_APP_ENV=development');
$_ENV['PMO_APP_ENV'] = 'development';
$phpBin = escapeshellarg(PHP_BINARY) . ' -d extension=zip';

foreach ($testSuites as $suiteFile => $suiteTitle) {
    $suitePath = str_starts_with($suiteFile, 'modules/') ? $projectDir . '/' . $suiteFile : $projectDir . '/tests/' . $suiteFile;
    if (!file_exists($suitePath)) {
        recordCheck('Fase 11', "Suite {$suiteFile}", 'FAIL', 'Archivo de prueba no encontrado');
        $testSummary['suites_run']++;
        $testSummary['suites_failed']++;
        $testSummary['assertions']['failed']++;
        continue;
    }

    $cmd = "{$phpBin} " . escapeshellarg($suitePath) . " 2>&1";
    $output = [];
    $exitCode = 0;
    exec($cmd, $output, $exitCode);

    $testSummary['suites_run']++;

    $outText = implode("\n", $output);
    $parsed = parseTestSuiteMetrics($outText, $exitCode, $suiteFile);

    $testSummary['assertions']['passed'] += $parsed['passed'];
    $testSummary['assertions']['failed'] += $parsed['failed'];
    $testSummary['assertions']['skipped'] += $parsed['skipped'];

    if ($parsed['status'] === 'PASS') {
        $testSummary['suites_passed']++;
        recordCheck('Fase 11', "Suite: {$suiteFile} ({$suiteTitle})", 'PASS', $parsed['reason']);
    } else {
        $testSummary['suites_failed']++;
        recordCheck('Fase 11', "Suite: {$suiteFile} ({$suiteTitle})", 'FAIL', $parsed['reason']);
        $failedLines = array_filter(explode("\n", $outText), fn($l) => str_contains($l, '[FAIL]') || str_contains($l, 'Fatal error') || str_contains($l, 'Parse error'));
        if (!empty($failedLines)) {
            echo "    \033[31mFallos en {$suiteFile}:\n      " . implode("\n      ", array_slice($failedLines, 0, 5)) . "\033[0m\n";
        }
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// FASE 12: Generación de Reportes de Auditoría (storage/reports/)
// ─────────────────────────────────────────────────────────────────────────────
echo "\n--- 12. Generación de Reportes de Auditoría ---\n";

$reportsDir = $projectDir . '/storage/reports';
$reportDirCreated = true;
if (!is_dir($reportsDir)) {
    if (!mkdir($reportsDir, 0755, true) && !is_dir($reportsDir)) {
        $reportDirCreated = false;
        recordCheck('Fase 12', 'Creación de directorio storage/reports/', 'FAIL', "No se pudo crear: {$reportsDir}");
    }
}

$reportData = [
    'project'     => 'PMO Solutions',
    'timestamp'   => date('Y-m-d H:i:s P'),
    'environment' => [
        'php_version' => PHP_VERSION,
        'os'          => PHP_OS,
        'sapi'        => PHP_SAPI,
        'has_node'    => $hasNode
    ],
    'metrics'     => [
        'total_checks' => count($details),
        'passed'       => $metrics['passed'],
        'failed'       => $metrics['failed'],
        'skipped'      => $metrics['skipped'],
        'warnings'     => $metrics['warnings'],
        'assertions'   => $testSummary['assertions']
    ],
    'status'      => ($metrics['failed'] === 0) ? 'SUCCESS' : 'FAILED',
    'failures'    => $failures,
    'warnings'    => $warnings,
    'checks'      => $details
];

$jsonReportFile = $reportsDir . '/quality-gate.json';
$txtReportFile = $reportsDir . '/quality-gate.txt';

$jsonEncoded = json_encode($reportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
if ($jsonEncoded === false) {
    recordCheck('Fase 12', 'Codificación de reporte JSON', 'FAIL', 'json_encode falló: ' . json_last_error_msg());
} else {
    $bytesWrittenJson = file_put_contents($jsonReportFile, $jsonEncoded);
    if ($bytesWrittenJson === false || !file_exists($jsonReportFile) || filesize($jsonReportFile) === 0) {
        recordCheck('Fase 12', 'Generación de reporte JSON', 'FAIL', 'file_put_contents falló en ' . $jsonReportFile);
    } else {
        recordCheck('Fase 12', 'Generación de reporte JSON', 'PASS', 'storage/reports/quality-gate.json');
    }
}

$txtContent = "======================================================================\n";
$txtContent .= " PMO SOLUTIONS — REPORTE DE CONTROL DE CALIDAD (QUALITY GATE)\n";
$txtContent .= "======================================================================\n";
$txtContent .= "Fecha y Hora     : " . $reportData['timestamp'] . "\n";
$txtContent .= "Versión PHP      : " . PHP_VERSION . "\n";
$txtContent .= "Estado General   : " . $reportData['status'] . "\n";
$txtContent .= "Total Chequeos   : " . count($details) . "\n";
$txtContent .= "Chequeos PASS    : " . $metrics['passed'] . "\n";
$txtContent .= "Chequeos FAIL    : " . $metrics['failed'] . "\n";
$txtContent .= "Chequeos SKIP    : " . $metrics['skipped'] . "\n";
$txtContent .= "Advertencias     : " . $metrics['warnings'] . "\n";
$txtContent .= "Aserciones PASS  : " . $testSummary['assertions']['passed'] . "\n";
$txtContent .= "Aserciones FAIL  : " . $testSummary['assertions']['failed'] . "\n";
$txtContent .= "Aserciones SKIP  : " . $testSummary['assertions']['skipped'] . "\n\n";

if (!empty($failures)) {
    $txtContent .= "--- FALLOS DETECTADOS ---\n";
    foreach ($failures as $f) {
        $txtContent .= "  * {$f}\n";
    }
    $txtContent .= "\n";
}

if (!empty($warnings)) {
    $txtContent .= "--- ADVERTENCIAS ---\n";
    foreach ($warnings as $w) {
        $txtContent .= "  * {$w}\n";
    }
    $txtContent .= "\n";
}

$txtContent .= "--- DETALLE DE CHEQUEOS ---\n";
foreach ($details as $d) {
    $txtContent .= sprintf("[%s] [%-6s] %s %s\n", $d['phase'], $d['status'], $d['name'], $d['message'] ? "— {$d['message']}" : "");
}

$bytesWrittenTxt = file_put_contents($txtReportFile, $txtContent);
if ($bytesWrittenTxt === false || !file_exists($txtReportFile) || filesize($txtReportFile) === 0) {
    recordCheck('Fase 12', 'Generación de reporte TXT', 'FAIL', 'file_put_contents falló en ' . $txtReportFile);
} else {
    recordCheck('Fase 12', 'Generación de reporte TXT', 'PASS', 'storage/reports/quality-gate.txt');
}

// ─────────────────────────────────────────────────────────────────────────────
// RESUMEN FINAL & CÓDIGO DE SALIDA
// ─────────────────────────────────────────────────────────────────────────────
echo "\n======================================================================\n";
echo " RESUMEN FINAL DEL QUALITY GATE\n";
echo "======================================================================\n";
echo " Total de chequeos ejecutados : " . count($details) . "\n";
echo " \033[32m✔ Chequeos exitosos (PASS)    : {$metrics['passed']}\033[0m\n";
echo " \033[33m▲ Advertencias (WARN)         : {$metrics['warnings']}\033[0m\n";
echo " \033[33m⚡ Chequeos omitidos (SKIP)   : {$metrics['skipped']}\033[0m\n";
echo " \033[31m✖ Chequeos fallidos (FAIL)    : {$metrics['failed']}\033[0m\n";
echo "----------------------------------------------------------------------\n";
echo " Total aserciones en tests    : " . ($testSummary['assertions']['passed'] + $testSummary['assertions']['failed'] + $testSummary['assertions']['skipped']) . "\n";
echo "   * Aserciones PASS          : {$testSummary['assertions']['passed']}\n";
echo "   * Aserciones SKIP          : {$testSummary['assertions']['skipped']}\n";
echo "   * Aserciones FAIL          : {$testSummary['assertions']['failed']}\n";
echo "======================================================================\n";

if ($metrics['failed'] === 0) {
    echo "\n\033[32m✔ QUALITY GATE APROBADO: El proyecto cumple con todos los estándares para empaquetado y despliegue.\033[0m\n\n";
    exit(0);
} else {
    echo "\n\033[31m✖ QUALITY GATE RECHAZADO: Se detectaron {$metrics['failed']} fallos críticos. Empaquetado bloqueado.\033[0m\n\n";
    exit(1);
}
