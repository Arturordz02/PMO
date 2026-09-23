<?php
namespace App\Core;

/**
 * PMO SOLUTIONS — Archivador y Rotador de Logs (LogArchiver)
 *
 * Gestiona el ciclo de vida de los archivos de log de la aplicación:
 *  - Comprime logs con más de 7 días usando Gzip
 *  - Archiva logs con más de 30 días en storage/logs/archive/
 *  - Elimina logs archivados con más de 90 días
 *  - Reporta el espacio liberado en cada operación
 *
 * Puede ejecutarse:
 *  - Manualmente vía CLI: php -r "require 'app/Core/Autoloader.php'; App\Core\Autoloader::register(); App\Core\LogArchiver::run();"
 *  - En un cron job: 0 2 * * * php /var/www/pmo-solutions/artisan/archive-logs.php
 *  - Automáticamente al iniciar la aplicación (modo lightweight)
 */
class LogArchiver {

    /** Directorio base de logs (relativo al project root) */
    private const LOGS_DIR = 'storage/logs';

    /** Subdirectorio de archivo histórico */
    private const ARCHIVE_DIR = 'storage/logs/archive';

    /** Días tras los cuales comprimir el log */
    private const COMPRESS_AFTER_DAYS = 7;

    /** Días tras los cuales archivar el log comprimido */
    private const ARCHIVE_AFTER_DAYS = 30;

    /** Días tras los cuales eliminar el log archivado */
    private const DELETE_AFTER_DAYS = 90;

    /** Máximo tamaño de log activo antes de rotarlo (en bytes): 50MB */
    private const MAX_LOG_SIZE_BYTES = 52_428_800;

    /** Raíz del proyecto */
    private static string $projectRoot = '';

    /**
     * Ejecuta el ciclo completo de archivado y limpieza.
     *
     * @param bool $verbose Si true, imprime el reporte en pantalla
     * @return array        Reporte de operaciones realizadas
     */
    public static function run(bool $verbose = false): array {
        self::$projectRoot = self::resolveProjectRoot();
        self::ensureDirectories();

        $report = [
            'executed_at'      => date('Y-m-d H:i:s'),
            'compressed'       => [],
            'archived'         => [],
            'deleted'          => [],
            'bytes_freed'      => 0,
            'errors'           => [],
        ];

        $logsDir    = self::$projectRoot . DIRECTORY_SEPARATOR . self::LOGS_DIR;
        $archiveDir = self::$projectRoot . DIRECTORY_SEPARATOR . self::ARCHIVE_DIR;
        $now        = time();

        if (!is_dir($logsDir)) {
            return $report;
        }

        // ── Paso 1: Comprimir logs .log con más de 7 días ───────────────────
        foreach (glob($logsDir . DIRECTORY_SEPARATOR . '*.log') as $logFile) {
            $fileAge = $now - filemtime($logFile);

            // Rotar si supera el tamaño máximo, sin importar la edad
            if (filesize($logFile) > self::MAX_LOG_SIZE_BYTES) {
                $result = self::compressFile($logFile);
                if ($result['success']) {
                    $report['compressed'][]  = basename($logFile);
                    $report['bytes_freed']  += $result['bytes_freed'];
                } else {
                    $report['errors'][] = $result['error'];
                }
                continue;
            }

            if ($fileAge > (self::COMPRESS_AFTER_DAYS * 86400)) {
                $result = self::compressFile($logFile);
                if ($result['success']) {
                    $report['compressed'][]  = basename($logFile);
                    $report['bytes_freed']  += $result['bytes_freed'];
                } else {
                    $report['errors'][] = $result['error'];
                }
            }
        }

        // ── Paso 2: Archivar logs .gz con más de 30 días ────────────────────
        foreach (glob($logsDir . DIRECTORY_SEPARATOR . '*.gz') as $gzFile) {
            $fileAge = $now - filemtime($gzFile);

            if ($fileAge > (self::ARCHIVE_AFTER_DAYS * 86400)) {
                $result = self::archiveFile($gzFile, $archiveDir);
                if ($result['success']) {
                    $report['archived'][] = basename($gzFile);
                } else {
                    $report['errors'][] = $result['error'];
                }
            }
        }

        // ── Paso 3: Eliminar archivos con más de 90 días ────────────────────
        foreach (glob($archiveDir . DIRECTORY_SEPARATOR . '*.gz') as $archivedFile) {
            $fileAge = $now - filemtime($archivedFile);

            if ($fileAge > (self::DELETE_AFTER_DAYS * 86400)) {
                $originalSize = filesize($archivedFile);
                if (@unlink($archivedFile)) {
                    $report['deleted'][]     = basename($archivedFile);
                    $report['bytes_freed']  += $originalSize;
                } else {
                    $report['errors'][] = "No se pudo eliminar: " . basename($archivedFile);
                }
            }
        }

        // ── Paso 4: Registrar el reporte en el log de archivado ─────────────
        $report['bytes_freed_human'] = self::formatBytes($report['bytes_freed']);
        self::logArchiverReport($report, $logsDir);

        if ($verbose) {
            self::printReport($report);
        }

        return $report;
    }

    /**
     * Limpieza ligera: solo elimina archivos expirados y logs de caché vacíos.
     * Pensado para ejecutarse al inicio de cada request (muy rápido).
     */
    public static function quickClean(): void {
        self::$projectRoot = self::resolveProjectRoot();
        $cacheDir = self::$projectRoot . DIRECTORY_SEPARATOR . 'storage/cache';

        if (!is_dir($cacheDir)) {
            return;
        }

        // Limpiar entradas de caché expiradas (solo 5% de los requests, para no sobrecargar)
        if (rand(1, 20) !== 1) {
            return;
        }

        foreach (glob($cacheDir . DIRECTORY_SEPARATOR . '*.cache') as $cacheFile) {
            $content = @file_get_contents($cacheFile);
            if ($content === false) {
                continue;
            }

            $data = json_decode($content, true);
            if (!is_array($data) || (isset($data['expires_at']) && $data['expires_at'] < time())) {
                @unlink($cacheFile);
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE METHODS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Comprime un archivo .log a .gz usando gzencode (sin extensión gz requerida).
     *
     * @param  string $filePath Ruta absoluta al archivo .log
     * @return array            ['success', 'bytes_freed', 'error']
     */
    private static function compressFile(string $filePath): array {
        $gzPath = $filePath . '.gz';
        $originalSize = filesize($filePath);

        $content = @file_get_contents($filePath);
        if ($content === false) {
            return ['success' => false, 'bytes_freed' => 0, 'error' => "No se pudo leer: " . basename($filePath)];
        }

        $compressed = gzencode($content, 6); // Nivel 6: balance velocidad/compresión
        if ($compressed === false) {
            return ['success' => false, 'bytes_freed' => 0, 'error' => "Error al comprimir: " . basename($filePath)];
        }

        if (@file_put_contents($gzPath, $compressed) === false) {
            return ['success' => false, 'bytes_freed' => 0, 'error' => "No se pudo escribir: " . basename($gzPath)];
        }

        // Preservar fecha de modificación original en el .gz
        @touch($gzPath, filemtime($filePath));

        // Eliminar el original si el .gz se creó correctamente
        @unlink($filePath);

        $compressedSize = filesize($gzPath);
        $bytesSaved     = $originalSize - $compressedSize;

        return ['success' => true, 'bytes_freed' => max(0, $bytesSaved), 'error' => ''];
    }

    /**
     * Mueve un archivo .gz al directorio de archivo histórico.
     *
     * @param  string $filePath   Ruta actual del archivo
     * @param  string $archiveDir Directorio de destino
     * @return array              ['success', 'error']
     */
    private static function archiveFile(string $filePath, string $archiveDir): array {
        $destination = $archiveDir . DIRECTORY_SEPARATOR . basename($filePath);

        if (!@rename($filePath, $destination)) {
            // Fallback: copy + delete
            if (@copy($filePath, $destination)) {
                @unlink($filePath);
                return ['success' => true, 'error' => ''];
            }
            return ['success' => false, 'error' => "No se pudo archivar: " . basename($filePath)];
        }

        return ['success' => true, 'error' => ''];
    }

    /**
     * Crea los directorios necesarios si no existen.
     */
    private static function ensureDirectories(): void {
        $dirs = [
            self::$projectRoot . DIRECTORY_SEPARATOR . self::LOGS_DIR,
            self::$projectRoot . DIRECTORY_SEPARATOR . self::ARCHIVE_DIR,
            self::$projectRoot . DIRECTORY_SEPARATOR . 'storage/cache',
            self::$projectRoot . DIRECTORY_SEPARATOR . 'storage/img/optimized',
        ];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
        }
    }

    /**
     * Registra el reporte del archivador en el log del sistema.
     */
    private static function logArchiverReport(array $report, string $logsDir): void {
        $logFile = $logsDir . DIRECTORY_SEPARATOR . 'archiver-' . date('Y-m') . '.log';
        $line    = json_encode($report, JSON_UNESCAPED_UNICODE) . PHP_EOL;
        @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * Imprime el reporte formateado en CLI.
     */
    private static function printReport(array $report): void {
        echo "\n=== PMO Log Archiver Report — {$report['executed_at']} ===\n";
        echo "Comprimidos : " . count($report['compressed']) . " archivos\n";
        echo "Archivados  : " . count($report['archived'])   . " archivos\n";
        echo "Eliminados  : " . count($report['deleted'])    . " archivos\n";
        echo "Espacio lib.: " . ($report['bytes_freed_human'] ?? '0 B') . "\n";

        if (!empty($report['errors'])) {
            echo "Errores     :\n";
            foreach ($report['errors'] as $error) {
                echo "  - {$error}\n";
            }
        }
        echo "==================================================\n\n";
    }

    /**
     * Formatea bytes a unidad legible (KB, MB, GB).
     */
    private static function formatBytes(int $bytes): string {
        if ($bytes >= 1_073_741_824) return round($bytes / 1_073_741_824, 2) . ' GB';
        if ($bytes >= 1_048_576)     return round($bytes / 1_048_576, 2)     . ' MB';
        if ($bytes >= 1_024)         return round($bytes / 1_024, 2)         . ' KB';
        return $bytes . ' B';
    }

    /**
     * Resuelve la ruta raíz del proyecto.
     */
    private static function resolveProjectRoot(): string {
        return dirname(__DIR__, 2); // app/Core/LogArchiver.php → project root
    }
}

