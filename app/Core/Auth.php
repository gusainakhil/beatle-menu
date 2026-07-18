<?php

namespace App\Core;

use App\Models\Business;
use App\Models\ActivityLog;

class Auth {
    public static function attempt(string $email, string $password): bool {
        $business = Business::findLoginByEmail($email);
        
        if ($business && password_verify($password, $business['password_hash'])) {
            Session::regenerate();
            Session::set('business_id', $business['id']);
            Session::set('user_id', $business['user_id']);
            Session::set('business_name', $business['name']);
            Session::set('owner_email', $business['email']);
            
            ActivityLog::log($business['id'], $business['user_id'], 'login', 'User logged in successfully.');
            return true;
        }

        return false;
    }

    public static function check(): bool {
        return Session::has('business_id');
    }

    public static function businessId(): string {
        $id = Session::get('business_id');
        if ($id === null) {
            throw new \Exception("Unauthorized: No authenticated business session.");
        }
        return (string)$id;
    }

    public static function userId(): ?string {
        $id = Session::get('user_id');
        return $id === null ? null : (string)$id;
    }

    public static function logout(): void {
        if (self::check()) {
            $businessId = self::businessId();
            try {
                ActivityLog::log($businessId, self::userId(), 'logout', 'User logged out.');
            } catch (\Exception $e) {
                // Ignore log errors during logout
            }
        }
        Session::destroy();
    }
}
