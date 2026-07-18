<?php

namespace App\Models;

use App\Core\Model;

class MenuItem extends Model {
    public static function all(): array {
        $stmt = self::getDb()->prepare("
            SELECT m.*, c.name as category_name 
            FROM menu_item m 
            JOIN category c ON m.category_id = c.id 
            WHERE m.business_id = ? 
              AND m.is_active = 1
            ORDER BY c.display_order ASC, c.name ASC, m.display_order ASC, m.name ASC
        ");
        $stmt->execute([self::getBusinessId()]);
        return $stmt->fetchAll();
    }
}
