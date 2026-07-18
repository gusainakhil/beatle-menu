<?php

namespace App\Core;

use PDO;

abstract class Model {
    protected static ?PDO $db = null;

    public static function getDb(): PDO {
        if (self::$db === null) {
            self::$db = Database::getInstance();
        }
        return self::$db;
    }

    /**
     * Retrieve the active business ID securely from the session.
     * Prevents business_id tampering from request payloads.
     */
    protected static function getBusinessId(): string {
        return Auth::businessId();
    }
}
