<?php

namespace App\Services;

use App\Core\DateRange;
use PDO;

class SalesReportService extends ReportService {
    public function getSummary(DateRange $range): array {
        $start = $range->getStartString();
        $end = $range->getEndString();

        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
                SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0.00 END) as total_revenue,
                AVG(CASE WHEN status = 'completed' THEN total_amount ELSE NULL END) as avg_order_value
            FROM orders 
            WHERE business_id = ? AND created_at BETWEEN ? AND ?
        ");
        $stmt->execute([$this->getBusinessId(), $start, $end]);
        $current = $stmt->fetch() ?: [
            'total_orders' => 0,
            'completed_orders' => 0,
            'total_revenue' => 0.00,
            'avg_order_value' => 0.00
        ];

        list($prevStart, $prevEnd) = $this->getPreviousPeriod($start, $end);
        $stmt->execute([$this->getBusinessId(), $prevStart, $prevEnd]);
        $previous = $stmt->fetch() ?: [
            'total_orders' => 0,
            'completed_orders' => 0,
            'total_revenue' => 0.00,
            'avg_order_value' => 0.00
        ];

        $revDelta = $this->calculateDelta((float)($current['total_revenue'] ?? 0.00), (float)($previous['total_revenue'] ?? 0.00));
        $ordersDelta = $this->calculateDelta((float)($current['completed_orders'] ?? 0), (float)($previous['completed_orders'] ?? 0));
        $aovDelta = $this->calculateDelta((float)($current['avg_order_value'] ?? 0.00), (float)($previous['avg_order_value'] ?? 0.00));

        return [
            'current' => $current,
            'previous' => $previous,
            'deltas' => [
                'revenue' => $revDelta,
                'orders' => $ordersDelta,
                'aov' => $aovDelta
            ]
        ];
    }

    private function getPreviousPeriod(string $start, string $end): array {
        $startDate = new \DateTime($start);
        $endDate = new \DateTime($end);
        $interval = $startDate->diff($endDate);
        
        $days = $interval->days + 1;
        
        $prevStart = clone $startDate;
        $prevStart->modify("-{$days} days");
        
        $prevEnd = clone $endDate;
        $prevEnd->modify("-{$days} days");
        
        return [
            $prevStart->format('Y-m-d H:i:s'),
            $prevEnd->format('Y-m-d H:i:s')
        ];
    }

    private function calculateDelta(float $current, float $previous): float {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }
        return round((($current - $previous) / $previous) * 100, 1);
    }

    public function getSalesTrend(DateRange $range, string $interval = 'daily'): array {
        $start = $range->getStartString();
        $end = $range->getEndString();

        $format = '%Y-%m-%d';
        if ($interval === 'weekly') {
            $format = '%Y-W%v';
        } elseif ($interval === 'monthly') {
            $format = '%Y-%m';
        }

        $stmt = $this->db->prepare("
            SELECT 
                DATE_FORMAT(created_at, ?) as period,
                SUM(total_amount) as revenue,
                COUNT(*) as orders
            FROM orders 
            WHERE business_id = ? AND status = 'completed' AND created_at BETWEEN ? AND ?
            GROUP BY period 
            ORDER BY period ASC
        ");
        $stmt->execute([$format, $this->getBusinessId(), $start, $end]);
        $rows = $stmt->fetchAll();

        $labels = [];
        $revenue = [];
        $orders = [];

        foreach ($rows as $row) {
            $labels[] = $row['period'];
            $revenue[] = (float)$row['revenue'];
            $orders[] = (int)$row['orders'];
        }

        return [
            'labels' => $labels,
            'revenue' => $revenue,
            'orders' => $orders
        ];
    }

    public function getOrderItemsSummary(DateRange $range): array {
        $start = $range->getStartString();
        $end = $range->getEndString();

        $stmt = $this->db->prepare("
            SELECT 
                CONCAT('ORD-', UPPER(LEFT(REPLACE(o.id, '-', ''), 8))) AS order_number,
                o.created_at,
                o.status,
                IF(o.is_paid = 1, 'paid', 'pending') AS payment_method,
                o.subtotal,
                o.tax_amount AS gst_amount,
                o.total_amount AS total,
                tr.number_label as service_unit,
                w.name as waiter
            FROM orders o
            LEFT JOIN table_room tr ON o.table_room_id = tr.id
            LEFT JOIN app_user w ON o.assigned_waiter_id = w.id
            WHERE o.business_id = ? AND o.created_at BETWEEN ? AND ?
            ORDER BY o.created_at DESC
        ");
        $stmt->execute([$this->getBusinessId(), $start, $end]);
        return $stmt->fetchAll();
    }
}
