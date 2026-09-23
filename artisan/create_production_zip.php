<?php
/**
 * PMO SOLUTIONS — Generador de Paquetes ZIP Dual con Quality Gate Integrado (Tarea 10)
 *
 * 1. PMO-Solutions-Produccion.zip (Producción Limpia)
 * 2. PMO-Solutions-Codigo-Fuente.zip (Código Fuente Completo con Pruebas y Herramientas)
 *
 * Uso CLI:
 *   php artisan/create_production_zip.php [ruta_directorio_salida_opcional] [--skip-quality-gate]
 */

declare(strict_types=1);

if (!class_exists('ZipArchive') && file_exists(dirname(PHP_BINARY) . '/ext/php_zip.dll')) {
    $args = array_slice($argv, 1);
    $cmd = sprintf('"%s" -d extension=zip "%s" %s', PHP_BINARY, __FILE__, implode(' ', array_map('escapeshellarg', $args)));
    passthru($cmd, $exitCode);
    exit($exitCode);
}

$projectDir = dirname(__DIR__);

if (!is_dir($projectDir)) {
    fwrite(STDERR, "Error: Directorio del proyecto no encontrado: {$projectDir}\n");
    exit(1);
}

require_once __DIR__ . '/PackagingPolicy.php';

// ─────────────────────────────────────────────────────────────────────────────
// 0. PROCESAMIENTO DE ARGUMENTOS CLI
// ─────────────────────────────────────────────────────────────────────────────
$skipQualityGate = false;
$isFinalNaming = false;
$outputDir = dirname($projectDir);

for ($i = 1; $i < $argc; $i++) {
    $arg = trim($argv[$i]);
    if ($arg === '--skip-quality-gate') {
        $skipQualityGate = true;
    } elseif ($arg === '--final') {
        $isFinalNaming = true;
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

$prodZipName   = $isFinalNaming ? 'PMO-Solutions-Produccion-Final.zip' : 'PMO-Solutions-Produccion.zip';
$cierreZipName = $isFinalNaming ? 'PMO-Solutions-Codigo-Fuente-Final.zip' : 'PMO-Solutions-Codigo-Fuente.zip';

$prodZipFile   = $outputDir . DIRECTORY_SEPARATOR . $prodZipName;
$cierreZipFile = $outputDir . DIRECTORY_SEPARATOR . $cierreZipName;

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
    $qgScript = $projectDir . '/artisan/quality-gate.php';
    $phpBin = escapeshellarg(PHP_BINARY) . ' -d extension=zip';
    $cmd = "{$phpBin} " . escapeshellarg($qgScript) . " " . escapeshellarg($projectDir);
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

    if ($fullPath === $normProdZip || $fullPath === $normCierreZip) {
        continue;
    }

    if (\Artisan\PackagingPolicy::shouldExcludeFromProduction($relativePath, $item->isDir())) {
        continue;
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

    if ($fullPath === $normProdZip || $fullPath === $normCierreZip) {
        continue;
    }

    if (\Artisan\PackagingPolicy::shouldExcludeFromClosure($relativePath, $item->isDir())) {
        continue;
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


// ─────────────────────────────────────────────────────────────────────────────
// 3. CONTROL DE CALIDAD POSTERIOR (VERIFICACIÓN CON ZIPARCHIVE)
// ─────────────────────────────────────────────────────────────────────────────
echo "\n>>> 3. Verificando integridad física de los paquetes generados con ZipArchive...\n";
$validationErrors = [];

$prodErrors = \Artisan\PackagingPolicy::validateProductionZipArchive($prodZipFile);
foreach ($prodErrors as $err) {
    $validationErrors[] = "[Producción] {$err}";
}

$cierreErrors = \Artisan\PackagingPolicy::validateClosureZipArchive($cierreZipFile);
foreach ($cierreErrors as $err) {
    $validationErrors[] = "[Código Fuente] {$err}";
}

if (!empty($validationErrors)) {
    if (file_exists($prodZipFile)) {
        @unlink($prodZipFile);
    }
    if (file_exists($cierreZipFile)) {
        @unlink($cierreZipFile);
    }
    fwrite(STDERR, "\n[ERROR CRÍTICO] La validación post-empaquetado con ZipArchive falló con " . count($validationErrors) . " error(es):\n");
    foreach ($validationErrors as $vErr) {
        fwrite(STDERR, "  - {$vErr}\n");
    }
    fwrite(STDERR, "Los paquetes ZIP defectuosos fueron eliminados automáticamente.\n");
    exit(1);
}

echo "✔ Verificación post-empaquetado aprobada al 100%: Ambos paquetes cumplen todas las reglas de inclusión y exclusión.\n\n";

echo "======================================================================\n";
echo " RESUMEN DE PAQUETES GENERADOS\n";
echo "======================================================================\n";
$packages = [
    'Producción'    => $prodZipFile,
    'Código Fuente' => $cierreZipFile,
];
foreach ($packages as $type => $filePath) {
    $size = filesize($filePath);
    $sizeMb = round($size / (1024 * 1024), 2);
    $hash = hash_file('sha256', $filePath);
    $za = new ZipArchive();
    $za->open($filePath);
    $numFiles = $za->numFiles;
    $za->close();

    echo " [Paquete: {$type}]\n";
    echo "  - Nombre  : " . basename($filePath) . "\n";
    echo "  - Ruta    : " . str_replace('\\', '/', $filePath) . "\n";
    echo "  - Tamaño  : {$size} bytes ({$sizeMb} MB)\n";
    echo "  - Archivos: {$numFiles}\n";
    echo "  - SHA-256 : {$hash}\n\n";
}