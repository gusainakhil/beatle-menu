<?php

namespace App\Models;

use App\Core\Model;

class ServiceUnit extends Model {
    public static function all(): array {
        $stmt = self::getDb()->prepare("
            SELECT *, number_label AS name
            FROM table_room
            WHERE business_id = ?
            ORDER BY type ASC, number_label ASC
        ");
        $stmt->execute([self::getBusinessId()]);
        return $stmt->fetchAll();
    }
}
