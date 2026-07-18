<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class Order extends Model {
    public static function getRecentOrders(int $limit = 10, int $offset = 0): array {
        $stmt = self::getDb()->prepare("
            SELECT
                o.*,
                CONCAT('ORD-', UPPER(LEFT(REPLACE(o.id, '-', ''), 8))) AS order_number,
                o.tax_amount AS gst_amount,
                o.total_amount AS total,
                IF(o.is_paid = 1, 'paid', 'pending') AS payment_method,
                tr.number_label as service_unit_name,
                w.name as waiter_name
            FROM orders o 
            LEFT JOIN table_room tr ON o.table_room_id = tr.id 
            LEFT JOIN app_user w ON o.assigned_waiter_id = w.id 
            WHERE o.business_id = ? 
            ORDER BY o.created_at DESC 
            LIMIT ? OFFSET ?
        ");
        
        $stmt->bindValue(1, self::getBusinessId(), PDO::PARAM_STR);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getRecentOrdersCount(): int {
        $stmt = self::getDb()->prepare("SELECT COUNT(*) FROM orders WHERE business_id = ?");
        $stmt->execute([self::getBusinessId()]);
        return (int)$stmt->fetchColumn();
    }

    public static function getRevenueBetween(string $start, string $end): float {
        $stmt = self::getDb()->prepare("
            SELECT SUM(total_amount) 
            FROM orders 
            WHERE business_id = ? AND status = 'completed' AND created_at BETWEEN ? AND ?
        ");
        $stmt->execute([self::getBusinessId(), $start, $end]);
        return (float)$stmt->fetchColumn();
    }

    public static function getAggregatesBetween(string $start, string $end): array {
        $stmt = self::getDb()->prepare("
            SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                SUM(CASE WHEN status = 'preparing' THEN 1 ELSE 0 END) as preparing_orders,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
                SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0.00 END) as total_revenue,
                SUM(CASE WHEN status = 'completed' THEN tax_amount ELSE 0.00 END) as total_gst,
                SUM(CASE WHEN status = 'completed' THEN subtotal ELSE 0.00 END) as total_taxable,
                SUM(CASE WHEN status = 'completed' THEN service_charge_amount ELSE 0.00 END) as total_discount,
                AVG(CASE WHEN status = 'completed' THEN total_amount ELSE NULL END) as avg_order_value
            FROM orders 
            WHERE business_id = ? AND created_at BETWEEN ? AND ?
        ");
        $stmt->execute([self::getBusinessId(), $start, $end]);
        $res = $stmt->fetch();
        return $res ?: [
            'total_orders' => 0,
            'completed_orders' => 0,
            'pending_orders' => 0,
            'preparing_orders' => 0,
            'cancelled_orders' => 0,
            'total_revenue' => 0.00,
            'total_gst' => 0.00,
            'total_taxable' => 0.00,
            'total_discount' => 0.00,
            'avg_order_value' => 0.00
        ];
    }
}
