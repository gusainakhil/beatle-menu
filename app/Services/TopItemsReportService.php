<?php

namespace App\Services;

use App\Core\DateRange;

class TopItemsReportService extends ReportService {
    public function getTopSelling(DateRange $range, int $limit = 10): array {
        $start = $range->getStartString();
        $end = $range->getEndString();

        $stmt = $this->db->prepare("
            SELECT 
                m.name as item_name,
                c.name as category_name,
                SUM(oi.quantity) as quantity_sold,
                SUM(oi.quantity * oi.unit_price) as revenue_generated
            FROM order_item oi
            JOIN menu_item m ON oi.menu_item_id = m.id
            JOIN category c ON m.category_id = c.id
            JOIN orders o ON oi.order_id = o.id
            WHERE o.business_id = ? AND o.status = 'completed' AND o.created_at BETWEEN ? AND ?
            GROUP BY oi.menu_item_id, m.name, c.name
            ORDER BY quantity_sold DESC
            LIMIT ?
        ");
        
        $stmt->bindValue(1, $this->getBusinessId(), \PDO::PARAM_STR);
        $stmt->bindValue(2, $start, \PDO::PARAM_STR);
        $stmt->bindValue(3, $end, \PDO::PARAM_STR);
        $stmt->bindValue(4, $limit, \PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
