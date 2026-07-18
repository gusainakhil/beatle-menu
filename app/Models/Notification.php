<?php

namespace App\Models;

use App\Core\Model;

class Notification extends Model {
    public static function getUnread(): array {
        $stmt = self::getDb()->prepare("
            SELECT *, body AS message
            FROM notification 
            WHERE business_id = ? AND is_read = 0 
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        $stmt->execute([self::getBusinessId()]);
        return $stmt->fetchAll();
    }

    public static function getUnreadCount(): int {
        $stmt = self::getDb()->prepare("SELECT COUNT(*) FROM notification WHERE business_id = ? AND is_read = 0");
        $stmt->execute([self::getBusinessId()]);
        return (int)$stmt->fetchColumn();
    }

    public static function markAllRead(): bool {
        $stmt = self::getDb()->prepare("UPDATE notification SET is_read = 1 WHERE business_id = ?");
        return $stmt->execute([self::getBusinessId()]);
    }
}
