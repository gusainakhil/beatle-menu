<?php

namespace App\Models;

use App\Core\Model;

class Category extends Model {
    public static function all(): array {
        $stmt = self::getDb()->prepare("SELECT *, 'utensils' AS icon FROM category WHERE business_id = ? AND is_active = 1 ORDER BY display_order ASC, name ASC");
        $stmt->execute([self::getBusinessId()]);
        return $stmt->fetchAll();
    }
}
