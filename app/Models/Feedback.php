<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class Feedback extends Model {
    public static function getRecent(int $limit = 10, int $offset = 0): array {
        $stmt = self::getDb()->prepare("
            SELECT
                f.*,
                f.overall_rating AS rating_overall,
                f.food_rating AS rating_food,
                f.service_rating AS rating_service,
                f.staff_rating AS rating_staff,
                f.cleanliness_rating AS rating_cleanliness,
                f.comment AS comments,
                'Guest' AS customer_name,
                NULL AS customer_phone,
                CONCAT('ORD-', UPPER(LEFT(REPLACE(o.id, '-', ''), 8))) AS order_number
            FROM feedback f 
            LEFT JOIN orders o ON f.order_id = o.id 
            WHERE f.business_id = ? 
            ORDER BY f.created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->bindValue(1, self::getBusinessId(), PDO::PARAM_STR);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getRecentCount(): int {
        $stmt = self::getDb()->prepare("SELECT COUNT(*) FROM feedback WHERE business_id = ?");
        $stmt->execute([self::getBusinessId()]);
        return (int)$stmt->fetchColumn();
    }

    public static function getAverageRatingsBetween(string $start, string $end): array {
        $stmt = self::getDb()->prepare("
            SELECT 
                AVG(overall_rating) as avg_overall,
                AVG(food_rating) as avg_food,
                AVG(service_rating) as avg_service,
                AVG(staff_rating) as avg_staff,
                AVG(cleanliness_rating) as avg_cleanliness,
                COUNT(*) as total_feedback
            FROM feedback 
            WHERE business_id = ? AND created_at BETWEEN ? AND ?
        ");
        $stmt->execute([self::getBusinessId(), $start, $end]);
        $res = $stmt->fetch();
        return $res ?: [
            'avg_overall' => 0.0,
            'avg_food' => 0.0,
            'avg_service' => 0.0,
            'avg_staff' => 0.0,
            'avg_cleanliness' => 0.0,
            'total_feedback' => 0
        ];
    }
}
