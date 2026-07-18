<?php

namespace App\Core;

class Csrf {
    public static function token(): string {
        $token = Session::get('csrf_token');
        if ($token === null) {
            $token = bin2hex(random_bytes(32));
            Session::set('csrf_token', $token);
        }
        return $token;
    }

    public static function validate(?string $token): bool {
        if ($token === null) {
            return false;
        }
        $stored = Session::get('csrf_token');
        return $stored !== null && hash_equals($stored, $token);
    }
}
