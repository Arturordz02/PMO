#!/usr/bin/env php
<?php
/**
 * PMO SOLUTIONS — Script CLI de Archivado de Logs
 *
 * Ejecutar manualmente:
 *   php artisan/archive-logs.php
 *   php artisan/archive-logs.php --verbose
 *
 * Configurar como cron job (diariamente a las 2:00 AM):
 *   0 2 * * * /usr/bin/php /home/user/public_html/artisan/archive-logs.php >> /dev/null 2>&1
 *
 * También convierte imágenes a WebP si se pasa --optimize-images:
 *   php artisan/archive-logs.php --optimize-images
 */

declare(strict_types=1);

define('PMO_APP_ACCESS', true);
require_once dirname(__DIR__) . '/app/Core/Autoloader.php';
App\Core\Autoloader::register();

$verbose        = in_array('--verbose', $argv, true);
$optimizeImages = in_array('--optimize-images', $argv, true);

echo "╔══════════════════════════════════════════════╗\n";
echo "║  PMO Solutions — Mantenimiento del Sistema   ║\n";
echo "║  " . date('Y-m-d H:i:s') . " — Lima, Perú        ║\n";
echo "╚══════════════════════════════════════════════╝\n\n";

// ── Archivado de Logs ─────────────────────────────────────────────────────────
echo "📁 Ejecutando archivado de logs...\n";
$report = App\Core\LogArchiver::run($verbose);

echo "  Comprimidos : " . count($report['compressed']) . " archivos\n";
echo "  Archivados  : " . count($report['archived'])   . " archivos\n";
echo "  Eliminados  : " . count($report['deleted'])    . " archivos\n";
echo "  Espacio lib.: " . ($report['bytes_freed_human'] ?? '0 B') . "\n";

if (!empty($report['errors'])) {
    echo "  Errores     :\n";
    foreach ($report['errors'] as $err) {
        echo "    ⚠ {$err}\n";
    }
}

// ── Limpieza de Caché Expirada ────────────────────────────────────────────────
echo "\n🗄 Limpiando caché expirada...\n";
$flushed = App\Core\Cache::flush();
echo "  Caché limpiada: " . ($flushed ? 'OK' : 'Sin cambios o error') . "\n";

// ── Optimización de Imágenes (opcional) ──────────────────────────────────────
if ($optimizeImages) {
    echo "\n🖼 Optimizando imágenes a WebP...\n";
    $imgDir    = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'img';
    $imgReport = App\Core\ImageOptimizer::convertDirectory($imgDir, false);

    echo "  Convertidas : " . $imgReport['converted']   . " imágenes\n";
    echo "  Omitidas    : " . $imgReport['skipped']     . " imágenes\n";
    echo "  Falladas    : " . $imgReport['failed']      . " imágenes\n";
    echo "  Ahorro total: " . ($imgReport['total_saved_human'] ?? '0 B') . "\n";

    if (!empty($imgReport['errors'])) {
        foreach ($imgReport['errors'] as $err) {
            echo "  ⚠ {$err}\n";
        }
    }
}

echo "\n✅ Mantenimiento completado.\n";
exit(0);

