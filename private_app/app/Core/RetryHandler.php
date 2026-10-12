<?php

namespace App\Core;

/**
 * RetryHandler for transient failures.
 */
class RetryHandler
{
    public static function execute(
        callable $operation,
        int $maxRetries = 3,
        int $baseDelayMs = 100,
        float $multiplier = 2.0,
        int $maxDelayMs = 5000,
        array $retryableExceptions = [\PDOException::class, \RuntimeException::class]
    ): mixed {
        $attempt = 0;

        while (true) {
            try {
                return $operation();
            } catch (\Throwable $e) {
                $attempt++;
                
                $isRetryable = false;
                foreach ($retryableExceptions as $exceptionClass) {
                    if ($e instanceof $exceptionClass) {
                        $isRetryable = true;
                        break;
                    }
                }

                if (!$isRetryable || $attempt > $maxRetries) {
                    throw $e;
                }

                $delay = self::calculateDelay($attempt, $baseDelayMs, $multiplier, $maxDelayMs);
                error_log("[RetryHandler] Attempt {$attempt}/{$maxRetries}: {$e->getMessage()}");
                usleep($delay);
            }
        }
    }

    private static function calculateDelay(int $attempt, int $baseDelayMs, float $multiplier, int $maxDelayMs): int
    {
        $delay = $baseDelayMs * pow($multiplier, $attempt - 1);
        if ($delay > $maxDelayMs) {
            $delay = $maxDelayMs;
        }

        // Add ±20% jitter
        $jitter = $delay * 0.2;
        $delay = $delay + mt_rand((int) -$jitter, (int) $jitter);

        // Convert ms to microseconds
        return (int) ($delay * 1000);
    }

    public static function withDefaults(callable $operation): mixed
    {
        return self::execute($operation);
    }
}
