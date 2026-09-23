<?php
/**
 * PMO SOLUTIONS — Verificación Pre-Despliegue Local (Pre-Deploy Check)
 *
 * Valida localmente la preparación de archivos, entorno PHP, extensiones requeridas,
 * directorios escribibles, configuración y ausencia de secretos antes del despliegue.
 *
 * Las verificaciones que dependen exclusivamente del aprovisionamiento en el servidor
 * de hosting (GoDaddy / cPanel) se declaran explícitamente como PENDING.
 *
 * Uso CLI:
 *   php artisan/pre-deploy-check.php [ruta_proyecto_opcional]
 */

declare(strict_types=1);

$projectDir = isset($argv[1]) && trim($argv[1]) !== '' && is_dir(trim($argv[1]))
    ? realpath(trim($argv[1]))
    : dirname(__DIR__);

if (!$projectDir || !is_dir($projectDir)) {
    fwrite(STDERR, "Error: Directorio de proyecto inválido o inexistente: {$projectDir}\n");
    exit(1);
}

$projectDir = str_replace('\\', '/', $projectDir);

if (!defined('PMO_APP_ACCESS')) {
    define('PMO_APP_ACCESS', true);
}

if (file_exists($projectDir . '/app/Core/Autoloader.php')) {
    require_once $projectDir . '/app/Core/Autoloader.php';
    \App\Core\Autoloader::register();
}

echo "\n======================================================================\n";
echo " PMO SOLUTIONS — VERIFICACIÓN PRE-DESPLIEGUE (PRE-DEPLOY CHECK)\n";
echo " Raíz del proyecto : {$projectDir}\n";
echo " Fecha y Hora      : " . date('Y-m-d H:i:s P') . "\n";
echo " Versión PHP       : " . PHP_VERSION . "\n";
echo "======================================================================\n\n";

$metrics = [
    'passed'   => 0,
    'failed'   => 0,
    'pending'  => 0,
    'skipped'  => 0,
    'warnings' => 0,
];

$details = [];
$failures = [];

function recordPreDeployCheck(string $category, string $name, string $status, string $message = ''): void {
    global $metrics, $details, $failures;

    $status = strtoupper($status);
    $entry = [
        'category' => $category,
        'name'     => $name,
        'status'   => $status,
        'message'  => $message,
    ];
    $details[] = $entry;

    if ($status === 'PASS') {
        $metrics['passed']++;
        echo "  \033[32m✔ [PASS]\033[0m [{$category}] {$name}" . ($message ? " — {$message}" : "") . "\n";
    } elseif ($status === 'PENDING') {
        $metrics['pending']++;
        echo "  \033[36m⏳ [PENDING]\033[0m [{$category}] {$name}" . ($message ? " — {$message}" : "") . "\n";
    } elseif ($status === 'SKIP') {
        $metrics['skipped']++;
        echo "  \033[33m⚡ [SKIP]\033[0m [{$category}] {$name}" . ($message ? " — {$message}" : "") . "\n";
    } elseif ($status === 'WARN') {
        $metrics['warnings']++;
        echo "  \033[33m▲ [WARN]\033[0m [{$category}] {$name}" . ($message ? " — {$message}" : "") . "\n";
    } else {
        $metrics['failed']++;
        $failures[] = "[{$category}] {$name}: {$message}";
        echo "  \033[31m✖ [FAIL]\033[0m [{$category}] {$name}" . ($message ? " — {$message}" : "") . "\n";
    }
}

function safeReadPreDeployFile(string $filePath): ?string {
    if (!file_exists($filePath) || !is_readable($filePath)) {
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
    return ($content === false || $lastError !== null) ? null : $content;
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. Verificación de Plataforma PHP & Extensiones Requeridas
// ─────────────────────────────────────────────────────────────────────────────
echo "--- 1. Plataforma PHP & Extensiones ---\n";

if (version_compare(PHP_VERSION, '8.0.30', '>=')) {
    recordPreDeployCheck('Plataforma', 'Versión compatible PHP 8.0.30+', 'PASS', 'PHP ' . PHP_VERSION . ' detectado');
} else {
    recordPreDeployCheck('Plataforma', 'Versión compatible PHP 8.0.30+', 'FAIL', 'Se requiere PHP 8.0.30 o superior (actual: ' . PHP_VERSION . ')');
}

$requiredExtensions = [
    'pdo'        => 'Acceso a base de datos PDO',
    'pdo_mysql'  => 'Driver MySQL para producción',
    'openssl'    => 'Criptografía y seguridad CSRF/Tokens',
    'mbstring'   => 'Manipulación de strings UTF-8',
    'json'       => 'Soporte para payloads JSON',
    'fileinfo'   => 'Detección de tipos MIME',
    'zip'        => 'Generación y lectura de paquetes ZIP'
];

foreach ($requiredExtensions as $ext => $desc) {
    if (extension_loaded($ext)) {
        recordPreDeployCheck('Extensiones', "Extensión {$ext}", 'PASS', $desc);
    } else {
        recordPreDeployCheck('Extensiones', "Extensión {$ext}", 'FAIL', "Extensión requerida no instalada ({$desc})");
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 2. Directorios de Almacenamiento & Permisos de Escritura
// ─────────────────────────────────────────────────────────────────────────────
echo "\n--- 2. Directorios de Almacenamiento & Permisos ---\n";

$storageDirs = [
    'storage'          => 'Directorio raíz de almacenamiento',
    'storage/cache'    => 'Caché de aplicación',
    'storage/logs'     => 'Logs estructurados de auditoría',
    'storage/outbox'   => 'Cola transaccional de correos outbox',
    'storage/reports'  => 'Reportes locales de control de calidad'
];

foreach ($storageDirs as $dirRel => $desc) {
    $fullDir = $projectDir . '/' . $dirRel;
    if (!is_dir($fullDir)) {
        @mkdir($fullDir, 0777, true);
    }

    if (is_dir($fullDir) && is_writable($fullDir)) {
        recordPreDeployCheck('Permisos', "Directorio escribible: {$dirRel}", 'PASS', "{$desc} (escribible)");
    } else {
        recordPreDeployCheck('Permisos', "Directorio escribible: {$dirRel}", 'FAIL', "{$desc} no existe o no tiene permisos de escritura");
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 3. Archivos Críticos, Configuración & Documentación
// ─────────────────────────────────────────────────────────────────────────────
echo "\n--- 3. Archivos Críticos, Configuración & Documentación ---\n";

$criticalFiles = [
    'index.php'                   => 'Front Controller principal',
    '.htaccess'                   => 'Configuración Apache y seguridad HTTP',
    'backend/.htaccess'           => 'Protección estricta de directorio backend/',
    'storage/.htaccess'           => 'Bloqueo de acceso HTTP a storage/',
    'storage/outbox/.htaccess'    => 'Protección de cola outbox',
    'backend/schema_v2.sql'       => 'Esquema canónico de base de datos MySQL',
    'app/Config/config.php'       => 'Configuración central del sistema',
    '.env.example'                => 'Plantilla de variables de entorno',
    'DEPLOYMENT.md'               => 'Instrucciones completas de despliegue',
    'README.md'                   => 'Documentación general del proyecto',
    'robots.txt'                  => 'Directivas para motores de búsqueda',
    'sitemap.xml'                 => 'Mapa del sitio web XML',
    'artisan/process-outbox.php'  => 'Script de procesamiento outbox por cron',
    'artisan/archive-logs.php'    => 'Script de rotación de logs',
    'artisan/quality-gate.php'    => 'Quality Gate de pre-empaquetado'
];

foreach ($criticalFiles as $fileRel => $desc) {
    $fullFile = $projectDir . '/' . $fileRel;
    if (file_exists($fullFile) && is_readable($fullFile)) {
        recordPreDeployCheck('Archivos', "Archivo crítico: {$fileRel}", 'PASS', $desc);
    } else {
        recordPreDeployCheck('Archivos', "Archivo crítico: {$fileRel}", 'FAIL', "Archivo ausente o ilegible ({$desc})");
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 4. Feature Flags de Seguridad & Rutas Administrativas
// ─────────────────────────────────────────────────────────────────────────────
echo "\n--- 4. Feature Flags & Parámetros de Seguridad ---\n";

$configFile = $projectDir . '/app/Config/config.php';
$config = file_exists($configFile) ? require $configFile : [];

$adminEnabled = $config['features']['admin_enabled'] ?? null;
$reportRetrievalEnabled = $config['features']['report_retrieval_enabled'] ?? null;

if ($adminEnabled === false) {
    recordPreDeployCheck('Seguridad', 'admin_enabled desactivado', 'PASS', 'Acceso a panel de administración bloqueado');
} else {
    recordPreDeployCheck('Seguridad', 'admin_enabled desactivado', 'FAIL', 'admin_enabled debe ser false');
}

if ($reportRetrievalEnabled === false) {
    recordPreDeployCheck('Seguridad', 'report_retrieval_enabled desactivado', 'PASS', 'Endpoints de consulta externa bloqueados');
} else {
    recordPreDeployCheck('Seguridad', 'report_retrieval_enabled desactivado', 'FAIL', 'report_retrieval_enabled debe ser false');
}

// ─────────────────────────────────────────────────────────────────────────────
// 5. Ausencia de Archivos Prohibidos & Secretos en Repositorio
// ─────────────────────────────────────────────────────────────────────────────
echo "\n--- 5. Auditoría de Secretos & Archivos Prohibidos ---\n";

$forbiddenFound = [];
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

    if ($basename === '.env.example') {
        continue;
    }
    if (str_starts_with($rel, '/storage/reports/')) {
        continue;
    }

    if ($item->isFile()) {
        if (preg_match('/^\.env(\.(local|production|dev|test))?$/i', $basename)) {
            $forbiddenFound[] = $rel;
        }
        if (preg_match('/^.+\.(zip|rar|tar|gz|7z|bak|backup|dump|tmp)$/i', $basename)) {
            $forbiddenFound[] = $rel;
        }
    }
}

if (empty($forbiddenFound)) {
    recordPreDeployCheck('Limpieza', 'Ausencia de archivos .env y backups', 'PASS', 'Repositorio limpio de credenciales y paquetes residuales');
} else {
    recordPreDeployCheck('Limpieza', 'Ausencia de archivos .env y backups', 'FAIL', 'Archivos prohibidos encontrados: ' . implode(', ', $forbiddenFound));
}

// ─────────────────────────────────────────────────────────────────────────────
// 6. Comprobaciones de Aprovisionamiento en Servidor Hosting (PENDING)
// ─────────────────────────────────────────────────────────────────────────────
echo "\n--- 6. Estado de Aprovisionamiento en Servidor Hosting (GoDaddy / cPanel) ---\n";

$hostingPendingChecks = [
    'Aprovisionamiento de Hosting' => 'Contratación y activación de cuenta de hosting cPanel / Apache en GoDaddy',
    'Certificado SSL/TLS'          => 'Instalación de certificado SSL/TLS y forzado de protocolo HTTPS en producción',
    'Base de Datos MySQL Remota'   => 'Creación de base de datos MySQL en producción e importación de backend/schema_v2.sql',
    'Servidor de Correo SMTP'      => 'Configuración de credenciales de envío SMTP corporativo y validación de relay',
    'Tareas Programadas (Cron)'    => 'Configuración del cron job en cPanel para ejecución periódica de artisan/process-outbox.php',
    'DNS & Dominio'                => 'Delegación de nameservers y propagación de registros DNS hacia el servidor de producción'
];

foreach ($hostingPendingChecks as $hName => $hDesc) {
    recordPreDeployCheck('Hosting GoDaddy', $hName, 'PENDING', "Pendiente de contratación y configuración en servidor en vivo ({$hDesc})");
}

// ─────────────────────────────────────────────────────────────────────────────
// Resumen Final
// ─────────────────────────────────────────────────────────────────────────────
$totalChecks = $metrics['passed'] + $metrics['failed'] + $metrics['pending'] + $metrics['skipped'];

echo "\n======================================================================\n";
echo " RESUMEN: VERIFICACIÓN PRE-DESPLIEGUE\n";
echo "======================================================================\n";
echo " Total verificaciones : {$totalChecks}\n";
echo " \033[32m✔ Chequeos exitosos (PASS)     : {$metrics['passed']}\033[0m\n";
echo " \033[36m⏳ Chequeos pendientes (PENDING) : {$metrics['pending']}\033[0m\n";
echo " \033[33m⚡ Chequeos omitidos (SKIP)     : {$metrics['skipped']}\033[0m\n";
echo " \033[31m✖ Chequeos fallidos (FAIL)     : {$metrics['failed']}\033[0m\n";
echo "======================================================================\n\n";

if ($metrics['failed'] === 0) {
    echo "\033[32m✔ VERIFICACIÓN LOCAL COMPLETADA EXITOSAMENTE.\033[0m\n";
    echo "  Todos los requisitos locales cumplen con el estándar para empaquetado.\n";
    echo "  Los puntos marcados como \033[36mPENDING\033[0m deberán configurarse una vez contratado el hosting.\n\n";
    exit(0);
} else {
    echo "\033[31m✖ SE ENCONTRARON FALLOS EN LA VERIFICACIÓN LOCAL PREVIA AL DESPLIEGUE.\033[0m\n";
    foreach ($failures as $f) {
        echo "  - {$f}\n";
    }
    echo "\n";
    exit(1);
}
