<?php

namespace App\Services;

use App\Core\DateRange;

class WaiterPerformanceReportService extends ReportService {
    public function getWaiterPerformance(DateRange $range): array {
        $start = $range->getStartString();
        $end = $range->getEndString();

        $stmt = $this->db->prepare("
            SELECT 
                w.id as waiter_id,
                w.name as waiter_name,
                UPPER(CONCAT(LEFT(w.name, 1), IF(LOCATE(' ', w.name) > 0, SUBSTRING(w.name, LOCATE(' ', w.name) + 1, 1), ''))) as waiter_avatar,
                w.email as waiter_email,
                COUNT(o.id) as orders_handled,
                COALESCE(AVG(o.total_amount), 0.00) as avg_order_value,
                COALESCE(AVG(f.overall_rating), 0.0) as avg_rating
            FROM app_user w
            LEFT JOIN orders o ON o.assigned_waiter_id = w.id AND o.status = 'completed' AND o.created_at BETWEEN ? AND ?
            LEFT JOIN feedback f ON f.order_id = o.id
            WHERE w.business_id = ?
              AND w.role = 'waiter'
              AND w.status = 'active'
            GROUP BY w.id, w.name, w.email
            ORDER BY orders_handled DESC
        ");
        $stmt->execute([$start, $end, $this->getBusinessId()]);
        return $stmt->fetchAll();
    }
}
