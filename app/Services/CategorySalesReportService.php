<?php

namespace App\Services;

use App\Core\DateRange;

class CategorySalesReportService extends ReportService {
    public function getCategorySales(DateRange $range): array {
        $start = $range->getStartString();
        $end = $range->getEndString();

        $stmt = $this->db->prepare("
            SELECT 
                c.name as category_name,
                'utensils' as category_icon,
                COUNT(DISTINCT o.id) as total_orders,
                SUM(oi.quantity * oi.unit_price) as total_revenue
            FROM order_item oi
            JOIN menu_item m ON oi.menu_item_id = m.id
            JOIN category c ON m.category_id = c.id
            JOIN orders o ON oi.order_id = o.id
            WHERE o.business_id = ? AND o.status = 'completed' AND o.created_at BETWEEN ? AND ?
            GROUP BY c.id, c.name
            ORDER BY total_revenue DESC
        ");
        $stmt->execute([$this->getBusinessId(), $start, $end]);
        return $stmt->fetchAll();
    }
}
