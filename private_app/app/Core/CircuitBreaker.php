<?php

namespace App\Core;

class CircuitOpenException extends \Exception
{
}

/**
 * Circuit breaker pattern implementation.
 */
class CircuitBreaker
{
    public const STATE_CLOSED = 'closed';
    public const STATE_OPEN = 'open';
    public const STATE_HALF_OPEN = 'half_open';

    private string $cacheKey;

    public function __construct(
        private string $name,
        private int $failureThreshold = 5,
        private int $recoveryTimeout = 30,
        private int $successThreshold = 2
    ) {
        $this->cacheKey = 'circuit_breaker_' . $this->name;
    }

    public function call(callable $operation): mixed
    {
        $state = $this->getStats();

        if ($state['state'] === self::STATE_OPEN) {
            if (time() - $state['last_failure_at'] >= $this->recoveryTimeout) {
                $state['state'] = self::STATE_HALF_OPEN;
                $this->saveState($state);
            } else {
                throw new CircuitOpenException("Circuit breaker '{$this->name}' is OPEN.");
            }
        }

        try {
            $result = $operation();

            if ($state['state'] === self::STATE_HALF_OPEN) {
                $state['success_count']++;
                if ($state['success_count'] >= $this->successThreshold) {
                    $this->reset();
                    return $result;
                }
                $this->saveState($state);
            } elseif ($state['state'] === self::STATE_CLOSED) {
                if ($state['failures'] > 0) {
                    $this->reset();
                }
            }

            return $result;
        } catch (\Throwable $e) {
            $state = $this->getStats();
            $state['failures']++;
            $state['last_failure_at'] = time();

            if ($state['state'] === self::STATE_HALF_OPEN || $state['failures'] >= $this->failureThreshold) {
                $state['state'] = self::STATE_OPEN;
                $state['success_count'] = 0;
                error_log("[CircuitBreaker] {$this->name} changed state to OPEN.");
            }

            $this->saveState($state);
            throw $e;
        }
    }

    public function isAvailable(): bool
    {
        $state = $this->getStats();
        if ($state['state'] === self::STATE_CLOSED) {
            return true;
        }
        if ($state['state'] === self::STATE_OPEN && (time() - $state['last_failure_at'] >= $this->recoveryTimeout)) {
            return true;
        }
        return false;
    }

    public function getState(): string
    {
        $state = $this->getStats();
        if ($state['state'] === self::STATE_OPEN && (time() - $state['last_failure_at'] >= $this->recoveryTimeout)) {
            return self::STATE_HALF_OPEN;
        }
        return $state['state'];
    }

    public function reset(): void
    {
        $this->saveState([
            'state' => self::STATE_CLOSED,
            'failures' => 0,
            'last_failure_at' => 0,
            'success_count' => 0,
        ]);
    }

    public function getStats(): array
    {
        $state = Cache::get($this->cacheKey);
        if (!is_array($state)) {
            return [
                'state' => self::STATE_CLOSED,
                'failures' => 0,
                'last_failure_at' => 0,
                'success_count' => 0,
            ];
        }
        return $state;
    }

    private function saveState(array $state): void
    {
        Cache::set($this->cacheKey, $state, 86400); // 24 hours ttl
    }
}
