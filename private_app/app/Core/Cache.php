<?php

namespace App\Core;

/**
 * Cache abstraction layer.
 */
class Cache
{
    private static ?string $driver = null;
    private static ?\Redis $redis = null;
    private static string $cacheDir = __DIR__ . '/../../storage/cache/';

    public static function getDriver(): string
    {
        if (self::$driver !== null) {
            return self::$driver;
        }

        if (class_exists('Redis')) {
            $redis = self::getRedisConnection();
            if ($redis !== null) {
                return self::$driver = 'redis';
            }
        }

        if (function_exists('apcu_enabled') && apcu_enabled()) {
            return self::$driver = 'apcu';
        }

        return self::$driver = 'file';
    }

    private static function getRedisConnection(): ?\Redis
    {
        if (self::$redis !== null) {
            return self::$redis;
        }

        try {
            $redis = new \Redis();
            if ($redis->connect('127.0.0.1', 6379, 1)) {
                self::$redis = $redis;
                return self::$redis;
            }
        } catch (\Throwable $e) {
            // Ignore connection errors
        }

        return null;
    }

    private static function sanitizeKey(string $key): string
    {
        return md5($key);
    }

    public static function get(string $key): mixed
    {
        $driver = self::getDriver();
        if ($driver === 'redis') {
            $value = self::$redis->get($key);
            return $value !== false ? unserialize($value) : null;
        }

        if ($driver === 'apcu') {
            $success = false;
            $value = apcu_fetch($key, $success);
            return $success ? $value : null;
        }

        $sanitizedKey = self::sanitizeKey($key);
        $file = self::$cacheDir . $sanitizedKey . '.cache';

        if (!file_exists($file)) {
            return null;
        }

        $content = file_get_contents($file);
        if ($content === false) {
            return null;
        }

        $data = json_decode($content, true);
        if (!is_array($data) || !isset($data['expires_at'], $data['data'])) {
            return null;
        }

        if ($data['expires_at'] < time()) {
            unlink($file);
            return null;
        }

        return unserialize(base64_decode($data['data']));
    }

    public static function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        $driver = self::getDriver();
        if ($driver === 'redis') {
            return self::$redis->set($key, serialize($value), $ttl);
        }

        if ($driver === 'apcu') {
            return apcu_store($key, $value, $ttl);
        }

        if (!is_dir(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0755, true);
        }

        $sanitizedKey = self::sanitizeKey($key);
        $file = self::$cacheDir . $sanitizedKey . '.cache';

        $data = [
            'expires_at' => time() + $ttl,
            'data' => base64_encode(serialize($value)),
        ];

        return file_put_contents($file, json_encode($data)) !== false;
    }

    public static function delete(string $key): bool
    {
        $driver = self::getDriver();
        if ($driver === 'redis') {
            return self::$redis->del($key) > 0;
        }

        if ($driver === 'apcu') {
            return apcu_delete($key);
        }

        $sanitizedKey = self::sanitizeKey($key);
        $file = self::$cacheDir . $sanitizedKey . '.cache';

        if (file_exists($file)) {
            return unlink($file);
        }

        return false;
    }

    public static function flush(): bool
    {
        $driver = self::getDriver();
        if ($driver === 'redis') {
            return self::$redis->flushDB();
        }

        if ($driver === 'apcu') {
            return apcu_clear_cache();
        }

        $success = true;
        if (is_dir(self::$cacheDir)) {
            $files = glob(self::$cacheDir . '*.cache');
            if ($files !== false) {
                foreach ($files as $file) {
                    if (!unlink($file)) {
                        $success = false;
                    }
                }
            }
        }

        return $success;
    }

    /**
     * Get an item from the cache, or execute the given callback and store the result.
     *
     * @param string $key
     * @param int $ttl
     * @param callable $callback
     * @return mixed
     *
     * @example
     * $users = Cache::remember('users_list', 3600, function () {
     *     return DB::query('SELECT * FROM users');
     * });
     */
    public static function remember(string $key, int $ttl, callable $callback): mixed
    {
        $value = self::get($key);
        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        self::set($key, $value, $ttl);

        return $value;
    }
}
