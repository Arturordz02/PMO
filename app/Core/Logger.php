<?php
namespace App\Core;

/**
 * PMO SOLUTIONS - Sistema de Logging Estructurado
 * 
 * Registra eventos del sistema (INFO, WARNING, ERROR) con rotación automática.
 */
class Logger {

    private static string $logDir = '';
    private static int $maxFileSize = 5242880; // 5 MB

    private static function init(): void {
        if (empty(self::$logDir)) {
            self::$logDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
            if (!is_dir(self::$logDir)) {
                @mkdir(self::$logDir, 0755, true);
            }
        }
    }

    public static function info(string $message, array $context = []): void {
        self::write('INFO', $message, $context);
    }

    public static function warning(string $message, array $context = []): void {
        self::write('WARNING', $message, $context);
    }

    public static function error(string $message, array $context = []): void {
        self::write('ERROR', $message, $context);
    }

    private static function write(string $level, string $message, array $context = []): void {
        self::init();

        $logFile = self::$logDir . DIRECTORY_SEPARATOR . 'app.log';

        // Rotación de logs si supera los 5 MB
        if (file_exists($logFile) && filesize($logFile) > self::$maxFileSize) {
            $archived = self::$logDir . DIRECTORY_SEPARATOR . 'app_' . date('Y-m-d_His') . '.log';
            @rename($logFile, $archived);
        }

        $timestamp = date('Y-m-d H:i:s');
        $ip = Security::getClientIp();
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $logLine = sprintf("[%s] [%s] [IP: %s] %s%s%s", $timestamp, $level, $ip, $message, $contextStr, PHP_EOL);

        @file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
}

