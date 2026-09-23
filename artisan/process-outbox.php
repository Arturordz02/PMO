#!/usr/bin/env php
<?php
/**
 * PMO SOLUTIONS — Worker CLI de Procesamiento de Outbox y Reintentos
 *
 * Ejecutar manualmente:
 *   php artisan/process-outbox.php
 *   php artisan/process-outbox.php --limit=50 --verbose
 *   php artisan/process-outbox.php --purge
 *
 * Configurar como cron job en cPanel (cada 5 minutos):
 *   *\/5 * * * * /usr/bin/php /home/user/public_html/artisan/process-outbox.php >> /dev/null 2>&1
 */

declare(strict_types=1);

define('PMO_APP_ACCESS', true);
require_once dirname(__DIR__) . '/app/Core/Autoloader.php';
App\Core\Autoloader::register();

$verbose = in_array('--verbose', $argv, true);
$doPurge = in_array('--purge', $argv, true);

$limit = 20;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--limit=')) {
        $limit = max(1, (int)substr($arg, 8));
    }
}

if ($verbose) {
    echo "╔══════════════════════════════════════════════╗\n";
    echo "║  PMO Solutions — Worker Email Outbox         ║\n";
    echo "║  " . date('Y-m-d H:i:s') . " — Lima, Perú        ║\n";
    echo "╚══════════════════════════════════════════════╝\n\n";
    echo "📧 Procesando cola de correos pendientes (límite: {$limit})...\n";
}

$results = App\Core\EmailOutbox::processPending($limit);

if ($verbose) {
    echo "  MySQL Procesados: {$results['db_processed']}\n";
    echo "  MySQL Enviados   : {$results['db_sent']}\n";
    echo "  MySQL Fallidos   : {$results['db_failed']}\n";
    echo "  Archivos Proc.  : {$results['files_processed']}\n";
    echo "  Archivos Enviad.: {$results['files_sent']}\n";
    echo "  Archivos Fallid.: {$results['files_failed']}\n";
}

if ($doPurge) {
    if ($verbose) {
        echo "\n🧹 Purgando registros antiguos de email_outbox...\n";
    }
    $purged = App\Core\EmailOutbox::purgeOldRecords(null, 7, 30);
    if ($verbose) {
        echo "  Registros purgados: {$purged}\n";
    }
}

if ($verbose) {
    echo "\n✅ Proceso de outbox finalizado.\n";
}

exit(0);

