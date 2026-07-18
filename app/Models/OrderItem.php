<?php

namespace App\Models;

use App\Core\Model;

class OrderItem extends Model {
    public static function getItemsForOrder(string $orderId): array {
        $stmt = self::getDb()->prepare("
            SELECT oi.*, m.name as item_name 
            FROM order_item oi 
            JOIN menu_item m ON oi.menu_item_id = m.id 
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }
}
