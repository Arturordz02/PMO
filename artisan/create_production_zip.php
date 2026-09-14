<?php
/**
 * PMO SOLUTIONS — Generador de Paquetes ZIP Dual con Quality Gate Integrado (Tarea 10)
 *
 * 1. PMO-Solutions-Produccion-Tarea9.zip (Producción Limpia)
 * 2. PMO-Solutions-Tarea9-Cierre.zip (Código Fuente Completo con Pruebas y Herramientas)
 *
 * Uso CLI:
 *   php artisan/create_production_zip.php [ruta_directorio_salida_opcional] [--skip-quality-gate]
 */

declare(strict_types=1);

$projectDir = dirname(__DIR__);

if (!is_dir($projectDir)) {
    fwrite(STDERR, "Error: Directorio del proyecto no encontrado: {$projectDir}\n");
    exit(1);
}

// ─────────────────────────────────────────────────────────────────────────────
// 0. PROCESAMIENTO DE ARGUMENTOS CLI
// ─────────────────────────────────────────────────────────────────────────────
$skipQualityGate = false;
$outputDir = dirname($projectDir);

for ($i = 1; $i < $argc; $i++) {
    $arg = trim($argv[$i]);
    if ($arg === '--skip-quality-gate') {
        $skipQualityGate = true;
    } elseif (!str_starts_with($arg, '--') && $arg !== '') {
        $outputDir = rtrim($arg, '/\\');
    }
}

if (!is_dir($outputDir)) {
    if (!mkdir($outputDir, 0777, true) && !is_dir($outputDir)) {
        fwrite(STDERR, "Error: No se pudo crear el directorio de salida: {$outputDir}\n");
        exit(1);
    }
}

$prodZipFile = $outputDir . DIRECTORY_SEPARATOR . 'PMO-Solutions-Produccion-Tarea9.zip';
$cierreZipFile = $outputDir . DIRECTORY_SEPARATOR . 'PMO-Solutions-Tarea9-Cierre.zip';

// Normalizar rutas para comparaciones
$normProjectDir = str_replace('\\', '/', $projectDir);
$normProdZip = str_replace('\\', '/', $prodZipFile);
$normCierreZip = str_replace('\\', '/', $cierreZipFile);

// ─────────────────────────────────────────────────────────────────────────────
// 0.1 CONTROL DE CALIDAD PREVIO (QUALITY GATE)
// ─────────────────────────────────────────────────────────────────────────────
$isTestRunner = defined('PMO_TEST_RUNNER') || getenv('PMO_TEST_RUNNER') === '1' || (!empty($_ENV['PMO_TEST_RUNNER']));
$appEnv = strtolower(trim((string)(getenv('PMO_APP_ENV') ?: (getenv('APP_ENV') ?: ($_ENV['PMO_APP_ENV'] ?? ($_ENV['APP_ENV'] ?? 'production'))))));
$isDevelopment = ($appEnv === 'development');

if ($skipQualityGate) {
    if (!$isDevelopment) {
        fwrite(STDERR, "\n[ERROR] El parámetro --skip-quality-gate solo está permitido en entorno de desarrollo (PMO_APP_ENV=development). En producción es obligatorio ejecutar el Quality Gate.\n");
        if (file_exists($prodZipFile)) {
            unlink($prodZipFile);
        }
        if (file_exists($cierreZipFile)) {
            unlink($cierreZipFile);
        }
        exit(1);
    }
    echo "\n\033[33m▲ [WARNING] --skip-quality-gate activo. Saltando control de calidad previo al empaquetado (Modo desarrollo).\033[0m\n\n";
} elseif ($isTestRunner && $isDevelopment) {
    // Solo en entorno de desarrollo se permite saltar la invocación anidada del gate para evitar recursión
    echo "\n>>> [INFO] Ejecución desde entorno de pruebas de desarrollo (PMO_TEST_RUNNER=1, PMO_APP_ENV=development). Saltando Quality Gate anidado para evitar recursión.\n\n";
} else {
    echo "\n>>> Ejecutando Control de Calidad (Quality Gate) previo al empaquetado...\n";
    $qgScript = $projectDir . '/artisan/quality-gate.php';
    $phpBin = PHP_BINARY;
    $cmd = "\"{$phpBin}\" " . escapeshellarg($qgScript) . " " . escapeshellarg($projectDir);
    passthru($cmd, $qgExitCode);

    if ($qgExitCode !== 0) {
        // Eliminar cualquier ZIP parcial o anterior para que no pueda confundirse con una entrega válida
        if (file_exists($prodZipFile)) {
            unlink($prodZipFile);
        }
        if (file_exists($cierreZipFile)) {
            unlink($cierreZipFile);
        }
        fwrite(STDERR, "\n[ERROR] El control de calidad (Quality Gate) falló con código {$qgExitCode}. Empaquetado abortado y paquetes de salida eliminados.\n");
        exit(1);
    }
    echo "\n>>> Quality Gate aprobado satisfactoriamente. Procediendo con el empaquetado dual.\n\n";
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. GENERAR PAQUETE DE PRODUCCIÓN LIMPIO
// ─────────────────────────────────────────────────────────────────────────────
if (file_exists($prodZipFile)) {
    if (!unlink($prodZipFile)) {
        fwrite(STDERR, "Error: No se pudo eliminar el archivo existente {$prodZipFile}\n");
        exit(1);
    }
}

$zipProd = new ZipArchive();
$openResult = $zipProd->open($prodZipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
if ($openResult !== true) {
    fwrite(STDERR, "Error al crear paquete de producción: {$prodZipFile} (Código: {$openResult})\n");
    exit(1);
}

$blacklistProd = [
    '/.env',
    '/tests',
    '/frontend',
    '/.git',
    '/.agent',
    '/.vscode',
    '/.idea',
    '/storage/reports',
    '/artisan/create_production_zip.php'
];

$filesAddedProd = 0;
$iteratorProd = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($projectDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iteratorProd as $item) {
    $fullPath = str_replace('\\', '/', $item->getPathname());
    $relativePath = substr($fullPath, strlen($normProjectDir));
    if (!str_starts_with($relativePath, '/')) {
        $relativePath = '/' . $relativePath;
    }

    // Evitar que los archivos ZIP de salida o temporales se incluyan dentro de sí mismos
    if (
        str_ends_with(strtolower($relativePath), '.zip') ||
        str_ends_with(strtolower($relativePath), '.rar') ||
        str_ends_with(strtolower($relativePath), '.tmp') ||
        str_ends_with(strtolower($relativePath), '.bak') ||
        $fullPath === $normProdZip ||
        $fullPath === $normCierreZip
    ) {
        continue;
    }

    $isBlacklisted = false;
    foreach ($blacklistProd as $prefix) {
        if ($relativePath === $prefix || str_starts_with($relativePath, $prefix . '/')) {
            $isBlacklisted = true;
            break;
        }
    }

    if ($isBlacklisted) {
        continue;
    }

    if (str_starts_with($relativePath, '/storage/')) {
        $filename = basename($relativePath);
        if ($filename !== '.gitkeep' && $filename !== '.htaccess') {
            if (
                str_starts_with($relativePath, '/storage/cache/') ||
                str_starts_with($relativePath, '/storage/logs/') ||
                str_starts_with($relativePath, '/storage/outbox/') ||
                str_starts_with($relativePath, '/storage/framework/') ||
                str_starts_with($relativePath, '/storage/reports/')
            ) {
                continue;
            }
        }
    }

    $zipEntryName = ltrim($relativePath, '/');

    if ($item->isDir()) {
        $zipProd->addEmptyDir($zipEntryName);
    } elseif ($item->isFile()) {
        $zipProd->addFile($item->getPathname(), $zipEntryName);
        $filesAddedProd++;
    }
}

if (!$zipProd->close()) {
    fwrite(STDERR, "Error al cerrar el paquete de producción: {$prodZipFile}\n");
    exit(1);
}
echo "1. ZIP de Producción generado: {$prodZipFile} ({$filesAddedProd} archivos)\n";


// ─────────────────────────────────────────────────────────────────────────────
// 2. GENERAR PAQUETE DE CÓDIGO FUENTE DE CIERRE (CON PRUEBAS Y HERRAMIENTAS)
// ─────────────────────────────────────────────────────────────────────────────
if (file_exists($cierreZipFile)) {
    if (!unlink($cierreZipFile)) {
        fwrite(STDERR, "Error: No se pudo eliminar el archivo existente {$cierreZipFile}\n");
        exit(1);
    }
}

$zipCierre = new ZipArchive();
$openCierreResult = $zipCierre->open($cierreZipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
if ($openCierreResult !== true) {
    fwrite(STDERR, "Error al crear paquete de cierre: {$cierreZipFile} (Código: {$openCierreResult})\n");
    exit(1);
}

$blacklistCierre = [
    '/.env',
    '/.git',
    '/.agent',
    '/.vscode',
    '/.idea'
];

$filesAddedCierre = 0;
$iteratorCierre = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($projectDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iteratorCierre as $item) {
    $fullPath = str_replace('\\', '/', $item->getPathname());
    $relativePath = substr($fullPath, strlen($normProjectDir));
    if (!str_starts_with($relativePath, '/')) {
        $relativePath = '/' . $relativePath;
    }

    // Evitar que los archivos ZIP de salida o temporales se incluyan dentro de sí mismos
    if (
        str_ends_with(strtolower($relativePath), '.zip') ||
        str_ends_with(strtolower($relativePath), '.rar') ||
        str_ends_with(strtolower($relativePath), '.tmp') ||
        str_ends_with(strtolower($relativePath), '.bak') ||
        $fullPath === $normProdZip ||
        $fullPath === $normCierreZip
    ) {
        continue;
    }

    $isBlacklisted = false;
    foreach ($blacklistCierre as $prefix) {
        if ($relativePath === $prefix || str_starts_with($relativePath, $prefix . '/')) {
            $isBlacklisted = true;
            break;
        }
    }

    if ($isBlacklisted) {
        continue;
    }

    if (str_starts_with($relativePath, '/storage/')) {
        $filename = basename($relativePath);
        if ($filename !== '.gitkeep' && $filename !== '.htaccess') {
            if (
                str_starts_with($relativePath, '/storage/cache/') ||
                str_starts_with($relativePath, '/storage/logs/') ||
                str_starts_with($relativePath, '/storage/outbox/') ||
                str_starts_with($relativePath, '/storage/framework/') ||
                str_starts_with($relativePath, '/storage/reports/')
            ) {
                continue;
            }
        }
    }

    $zipEntryName = ltrim($relativePath, '/');

    if ($item->isDir()) {
        $zipCierre->addEmptyDir($zipEntryName);
    } elseif ($item->isFile()) {
        $zipCierre->addFile($item->getPathname(), $zipEntryName);
        $filesAddedCierre++;
    }
}

if (!$zipCierre->close()) {
    fwrite(STDERR, "Error al cerrar el paquete de cierre: {$cierreZipFile}\n");
    exit(1);
}
echo "2. ZIP de Cierre (Fuente + Tests) generado: {$cierreZipFile} ({$filesAddedCierre} archivos)\n";