<?php

namespace App\Models;

use App\Core\Model;

class MenuItem extends Model {
    public static function getApiMenuItems(?string $categoryId = null, ?bool $available = null): array {
        $params = [self::getBusinessId()];
        $filters = '';

        if ($categoryId !== null && $categoryId !== '') {
            $filters .= ' AND m.category_id = ?';
            $params[] = $categoryId;
        }

        if ($available !== null) {
            $filters .= ' AND m.is_available = ?';
            $params[] = $available ? 1 : 0;
        }

        $stmt = self::getDb()->prepare("
            SELECT
                m.*,
                c.name AS category_name
            FROM menu_item m
            JOIN category c ON m.category_id = c.id
            WHERE m.business_id = ?
              AND m.is_active = 1
              {$filters}
            ORDER BY c.display_order ASC, c.name ASC, m.display_order ASC, m.name ASC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

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
