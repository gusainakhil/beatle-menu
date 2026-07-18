<?php

namespace App\Models;

use App\Core\Model;

class Waiter extends Model {
    public static function all(): array {
        $stmt = self::getDb()->prepare("
            SELECT *, UPPER(CONCAT(LEFT(name, 1), IF(LOCATE(' ', name) > 0, SUBSTRING(name, LOCATE(' ', name) + 1, 1), ''))) AS avatar
            FROM app_user
            WHERE business_id = ?
              AND role = 'waiter'
              AND status = 'active'
            ORDER BY name ASC
        ");
        $stmt->execute([self::getBusinessId()]);
        return $stmt->fetchAll();
    }
}
