<?php

// Global helper functions

use App\Core\Csrf;

if (!function_exists('e')) {
    function e(mixed $value): string {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('brand')) {
    function brand(string $key, mixed $default = null): mixed {
        static $brand = null;
        if ($brand === null) {
            $brand = require __DIR__ . '/../config/brand.php';
        }
        
        if (strpos($key, '.') !== false) {
            $parts = explode('.', $key);
            $temp = $brand;
            foreach ($parts as $part) {
                if (is_array($temp) && isset($temp[$part])) {
                    $temp = $temp[$part];
                } else {
                    return $default;
                }
            }
            return $temp;
        }
        
        return $brand[$key] ?? $default;
    }
}

if (!function_exists('route')) {
    function route(string $path): string {
        return '/' . ltrim($path, '/');
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        return Csrf::token();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string {
        return '<input type="hidden" name="_token" value="' . csrf_token() . '">';
    }
}
