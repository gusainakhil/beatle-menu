<?php

namespace App\Services;

use App\Core\DateRange;

class FeedbackReportService extends ReportService {
    public function getSummary(DateRange $range): array {
        $start = $range->getStartString();
        $end = $range->getEndString();

        $stmt = $this->db->prepare("
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
        $stmt->execute([$this->getBusinessId(), $start, $end]);
        $row = $stmt->fetch();

        return [
            'avg_overall' => round((float)($row['avg_overall'] ?? 0.0), 1),
            'avg_food' => round((float)($row['avg_food'] ?? 0.0), 1),
            'avg_service' => round((float)($row['avg_service'] ?? 0.0), 1),
            'avg_staff' => round((float)($row['avg_staff'] ?? 0.0), 1),
            'avg_cleanliness' => round((float)($row['avg_cleanliness'] ?? 0.0), 1),
            'total_feedback' => (int)($row['total_feedback'] ?? 0)
        ];
    }

    public function getRatingTrend(DateRange $range): array {
        $start = $range->getStartString();
        $end = $range->getEndString();

        $stmt = $this->db->prepare("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m-%d') as date,
                AVG(overall_rating) as avg_rating
            FROM feedback 
            WHERE business_id = ? AND created_at BETWEEN ? AND ?
            GROUP BY date 
            ORDER BY date ASC
        ");
        $stmt->execute([$this->getBusinessId(), $start, $end]);
        $rows = $stmt->fetchAll();

        $labels = [];
        $ratings = [];

        foreach ($rows as $row) {
            $labels[] = $row['date'];
            $ratings[] = round((float)$row['avg_rating'], 1);
        }

        return [
            'labels' => $labels,
            'ratings' => $ratings
        ];
    }

    public function getFeedbackList(DateRange $range, int $limit = 10, int $offset = 0): array {
        $start = $range->getStartString();
        $end = $range->getEndString();

        $stmt = $this->db->prepare("
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
            WHERE f.business_id = ? AND f.created_at BETWEEN ? AND ?
            ORDER BY f.created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->bindValue(1, $this->getBusinessId(), \PDO::PARAM_STR);
        $stmt->bindValue(2, $start, \PDO::PARAM_STR);
        $stmt->bindValue(3, $end, \PDO::PARAM_STR);
        $stmt->bindValue(4, $limit, \PDO::PARAM_INT);
        $stmt->bindValue(5, $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getFeedbackCount(DateRange $range): int {
        $start = $range->getStartString();
        $end = $range->getEndString();

        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM feedback 
            WHERE business_id = ? AND created_at BETWEEN ? AND ?
        ");
        $stmt->execute([$this->getBusinessId(), $start, $end]);
        return (int)$stmt->fetchColumn();
    }
}
