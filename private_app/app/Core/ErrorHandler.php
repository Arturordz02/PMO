<?php

namespace App\Core;

/**
 * Global application error handler.
 */
class ErrorHandler
{
    private static array $config = [];

    public static function register(array $config = []): void
    {
        self::$config = $config;
        
        set_error_handler(fn($errno, $errstr, $errfile, $errline) => self::handleError($errno, $errstr, $errfile, $errline));
        set_exception_handler(fn(\Throwable $e) => self::handleException($e));
        register_shutdown_function(fn() => self::handleShutdown());
    }

    private static function handleException(\Throwable $e): void
    {
        $level = $e instanceof \ErrorException ? 'ERROR' : 'CRITICAL';
        
        self::log($level, $e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);

        self::sendGracefulResponse($e);
    }

    private static function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        if (!(error_reporting() & $errno)) {
            return false;
        }

        throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    private static function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            self::handleException(new \ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']));
        }
    }

    private static function log(string $level, string $message, array $context = []): void
    {
        $date = date('Y-m-d');
        $logDir = dirname(__DIR__, 2) . '/storage/logs';
        $logFile = $logDir . '/app-' . $date . '.log';

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        self::rotateLogIfNeeded($logFile);

        $logEntry = json_encode([
            'timestamp' => date('c'),
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ]) . PHP_EOL;

        error_log($logEntry, 3, $logFile);
    }

    private static function isDebugMode(): bool
    {
        return self::$config['debug'] ?? false;
    }

    private static function sendGracefulResponse(\Throwable $e): void
    {
        if (!headers_sent()) {
            if ($e instanceof CircuitOpenException) {
                header('HTTP/1.1 503 Service Unavailable', true, 503);
                header('Retry-After: 30');
            } else {
                header('HTTP/1.1 500 Internal Server Error', true, 500);
            }
        }

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
                  || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'error' => true,
                'message' => self::isDebugMode() ? $e->getMessage() : 'An unexpected error occurred.',
                'trace' => self::isDebugMode() ? $e->getTrace() : null,
            ]);
            exit;
        }

        if (self::isDebugMode()) {
            echo "<h1>Exception</h1>";
            echo "<p><strong>" . htmlspecialchars($e->getMessage()) . "</strong></p>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        } else {
            echo "<h1>Something went wrong</h1>";
            echo "<p>We are experiencing technical difficulties. Please try again later.</p>";
        }
        
        exit;
    }

    private static function rotateLogIfNeeded(string $logFile): void
    {
        if (file_exists($logFile) && filesize($logFile) > 50 * 1024 * 1024) {
            rename($logFile, $logFile . '.' . time() . '.bak');
        }
    }
}
