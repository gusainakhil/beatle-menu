<?php

namespace App\Core;

class Cache {
    private static string $cacheDir = __DIR__ . '/../../storage/cache';

    private static function init(): void {
        if (!is_dir(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0755, true);
        }
    }

    /**
     * Fetch key from the cache if it exists and is not expired.
     */
    public static function get(string $key, int $ttl = 300): mixed {
        self::init();
        $file = self::$cacheDir . '/' . md5($key) . '.cache';

        if (!file_exists($file)) {
            return null;
        }

        $raw = file_get_contents($file);
        $data = unserialize($raw);
        if ($data === false || !is_array($data) || !isset($data['time']) || !isset($data['value'])) {
            return null;
        }

        if (time() - $data['time'] > $ttl) {
            unlink($file);
            return null;
        }

        return $data['value'];
    }

    /**
     * Store item in the cache.
     */
    public static function set(string $key, mixed $value): void {
        self::init();
        $file = self::$cacheDir . '/' . md5($key) . '.cache';
        
        $data = [
            'time' => time(),
            'value' => $value
        ];

        file_put_contents($file, serialize($data));
    }

    /**
     * Invalidate specific cache key.
     */
    public static function forget(string $key): void {
        self::init();
        $file = self::$cacheDir . '/' . md5($key) . '.cache';
        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * Invalidate all cached data.
     */
    public static function clear(): void {
        self::init();
        $files = glob(self::$cacheDir . '/*.cache');
        if (is_array($files)) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }
}
