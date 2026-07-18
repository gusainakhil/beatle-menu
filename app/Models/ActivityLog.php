<?php

namespace App\Models;

use App\Core\Model;

class ActivityLog extends Model {
    public static function log(string $businessId, ?string $userId, string $action, string $description): bool {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'CLI';
        
        $db = self::getDb();
        $stmt = $db->prepare("
            INSERT INTO activity_log (business_id, actor_user_id, action, entity_type, before_state, ip_address, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        return $stmt->execute([$businessId, $userId, $action, $ua, json_encode(['description' => $description]), $ip]);
    }

    public static function getRecent(int $limit = 10): array {
        $stmt = self::getDb()->prepare("
            SELECT *, entity_type AS user_agent
            FROM activity_log 
            WHERE business_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->bindValue(1, self::getBusinessId(), \PDO::PARAM_STR);
        $stmt->bindValue(2, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
