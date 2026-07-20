<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class Order extends Model {
    private const ACTIVE_STATUSES = ['pending', 'accepted', 'preparing', 'ready', 'served'];
    private const CLOSED_STATUSES = ['completed', 'cancelled'];

    public static function getApiOrderDetail(string $orderId): ?array {
        $stmt = self::getDb()->prepare("
            SELECT
                o.id,
                CONCAT('ORD-', UPPER(LEFT(REPLACE(o.id, '-', ''), 8))) AS order_number,
                o.table_room_id,
                tr.number_label AS table_label,
                o.assigned_waiter_id,
                w.name AS waiter_name,
                o.status,
                o.subtotal,
                o.tax_amount,
                o.service_charge_amount,
                o.total_amount,
                o.is_paid,
                o.paid_at,
                o.customer_instructions,
                o.created_at,
                o.updated_at
            FROM orders o
            LEFT JOIN table_room tr ON o.table_room_id = tr.id
            LEFT JOIN app_user w ON o.assigned_waiter_id = w.id
            WHERE o.business_id = ? AND o.id = ?
            LIMIT 1
        ");
        $stmt->execute([self::getBusinessId(), $orderId]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public static function findApiOrderIdByNumber(string $orderNumber): ?string {
        $stmt = self::getDb()->prepare("
            SELECT id
            FROM orders
            WHERE business_id = ?
              AND CONCAT('ORD-', UPPER(LEFT(REPLACE(id, '-', ''), 8))) = ?
            LIMIT 1
        ");
        $stmt->execute([self::getBusinessId(), strtoupper($orderNumber)]);
        $id = $stmt->fetchColumn();
        return $id ? (string)$id : null;
    }

    public static function createApiOrder(array $data): array {
        $db = self::getDb();
        $businessId = self::getBusinessId();
        $orderId = self::uuid();
        $items = is_array($data['items'] ?? null) ? $data['items'] : [];

        $db->beginTransaction();

        try {
            self::assertTableBelongsToBusiness((string)$data['table_room_id'], $businessId);
            $totals = self::calculateOrderTotals($items, $businessId);

            $stmt = $db->prepare("
                INSERT INTO orders (
                    id, business_id, table_room_id, assigned_waiter_id, status, subtotal,
                    tax_amount, service_charge_amount, total_amount, customer_instructions,
                    created_at, updated_at
                ) VALUES (?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([
                $orderId,
                $businessId,
                $data['table_room_id'],
                $data['assigned_waiter_id'] ?? null,
                $totals['subtotal'],
                $totals['tax_amount'],
                $totals['service_charge_amount'],
                $totals['total_amount'],
                $data['customer_instructions'] ?? null,
            ]);

            self::insertOrderItems($orderId, $totals['items']);
            self::markTableForOrder((string)$data['table_room_id'], $orderId, true);

            $db->commit();
            return self::getApiOrderDetail($orderId) ?: ['id' => $orderId];
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function updateApiOrderStatus(string $orderId, string $status): ?array {
        $db = self::getDb();
        $businessId = self::getBusinessId();
        $order = self::getApiOrderDetail($orderId);

        if (!$order) {
            return null;
        }

        $db->beginTransaction();

        try {
            $stmt = $db->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE business_id = ? AND id = ?");
            $stmt->execute([$status, $businessId, $orderId]);

            if (in_array($status, self::CLOSED_STATUSES, true)) {
                self::markTableForOrder((string)$order['table_room_id'], null, false);
            } elseif (in_array($status, self::ACTIVE_STATUSES, true)) {
                self::markTableForOrder((string)$order['table_room_id'], $orderId, true);
            }

            $db->commit();
            return self::getApiOrderDetail($orderId);
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function addApiOrderItems(string $orderId, array $items): ?array {
        $db = self::getDb();
        $businessId = self::getBusinessId();
        $order = self::getApiOrderDetail($orderId);

        if (!$order) {
            return null;
        }

        $db->beginTransaction();

        try {
            $totals = self::calculateOrderTotals($items, $businessId);
            self::insertOrderItems($orderId, $totals['items']);

            $subtotal = (float)$order['subtotal'] + $totals['subtotal'];
            $taxAmount = (float)$order['tax_amount'] + $totals['tax_amount'];
            $serviceCharge = (float)$order['service_charge_amount'] + $totals['service_charge_amount'];
            $totalAmount = (float)$order['total_amount'] + $totals['total_amount'];

            $stmt = $db->prepare("
                UPDATE orders
                SET subtotal = ?, tax_amount = ?, service_charge_amount = ?, total_amount = ?, updated_at = NOW()
                WHERE business_id = ? AND id = ?
            ");
            $stmt->execute([$subtotal, $taxAmount, $serviceCharge, $totalAmount, $businessId, $orderId]);

            $db->commit();
            return self::getApiOrderDetail($orderId);
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function updateApiOrderItemStatus(string $orderItemId, string $status): bool {
        $stmt = self::getDb()->prepare("
            UPDATE order_item oi
            JOIN orders o ON oi.order_id = o.id
            SET oi.item_status = ?, oi.updated_at = NOW()
            WHERE oi.id = ? AND o.business_id = ?
        ");
        $stmt->execute([$status, $orderItemId, self::getBusinessId()]);
        return $stmt->rowCount() > 0;
    }

    public static function getApiOrders(?string $status = null, int $limit = 20, int $offset = 0): array {
        $params = [self::getBusinessId()];
        $statusSql = '';

        if ($status !== null && $status !== '') {
            $statusSql = ' AND o.status = ?';
            $params[] = $status;
        }

        $stmt = self::getDb()->prepare("
            SELECT
                o.id,
                CONCAT('ORD-', UPPER(LEFT(REPLACE(o.id, '-', ''), 8))) AS order_number,
                o.table_room_id,
                tr.number_label AS table_label,
                o.assigned_waiter_id,
                w.name AS waiter_name,
                o.status,
                o.subtotal,
                o.tax_amount,
                o.service_charge_amount,
                o.total_amount,
                o.is_paid,
                o.paid_at,
                o.customer_instructions,
                o.created_at,
                o.updated_at,
                COALESCE(
                    GROUP_CONCAT(
                        CONCAT(oi.quantity, 'x ', mi.name)
                        ORDER BY oi.created_at ASC
                        SEPARATOR ', '
                    ),
                    ''
                ) AS items_summary
            FROM orders o
            LEFT JOIN table_room tr ON o.table_room_id = tr.id
            LEFT JOIN app_user w ON o.assigned_waiter_id = w.id
            LEFT JOIN order_item oi ON oi.order_id = o.id
            LEFT JOIN menu_item mi ON oi.menu_item_id = mi.id
            WHERE o.business_id = ?{$statusSql}
            GROUP BY o.id, o.table_room_id, tr.number_label, o.assigned_waiter_id, w.name, o.status, o.subtotal,
                o.tax_amount, o.service_charge_amount, o.total_amount, o.is_paid, o.paid_at,
                o.customer_instructions, o.created_at, o.updated_at
            ORDER BY o.created_at DESC
            LIMIT ? OFFSET ?
        ");

        $position = 1;
        foreach ($params as $param) {
            $stmt->bindValue($position, $param, PDO::PARAM_STR);
            $position++;
        }
        $stmt->bindValue($position, $limit, PDO::PARAM_INT);
        $stmt->bindValue($position + 1, $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function getApiOrdersCount(?string $status = null): int {
        $params = [self::getBusinessId()];
        $statusSql = '';

        if ($status !== null && $status !== '') {
            $statusSql = ' AND status = ?';
            $params[] = $status;
        }

        $stmt = self::getDb()->prepare("SELECT COUNT(*) FROM orders WHERE business_id = ?{$statusSql}");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public static function getHomeOrderStats(): array {
        $stmt = self::getDb()->prepare("
            SELECT
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN status IN ('accepted', 'preparing', 'ready') THEN 1 ELSE 0 END) AS preparing,
                SUM(CASE WHEN status IN ('served', 'completed') THEN 1 ELSE 0 END) AS served
            FROM orders
            WHERE business_id = ?
        ");
        $stmt->execute([self::getBusinessId()]);
        $stats = $stmt->fetch() ?: [];

        return [
            'pending' => (int)($stats['pending'] ?? 0),
            'preparing' => (int)($stats['preparing'] ?? 0),
            'served' => (int)($stats['served'] ?? 0),
        ];
    }

    public static function getHomeRecentOrders(int $limit = 3): array {
        $stmt = self::getDb()->prepare("
            SELECT
                o.id,
                CONCAT(UPPER(LEFT(REPLACE(o.id, '-', ''), 4))) AS display_id,
                o.status,
                o.total_amount,
                o.created_at,
                tr.number_label AS service_unit_name,
                COALESCE(
                    GROUP_CONCAT(
                        CONCAT(oi.quantity, 'x ', mi.name)
                        ORDER BY oi.created_at ASC
                        SEPARATOR ', '
                    ),
                    ''
                ) AS items
            FROM orders o
            LEFT JOIN table_room tr ON o.table_room_id = tr.id
            LEFT JOIN order_item oi ON oi.order_id = o.id
            LEFT JOIN menu_item mi ON oi.menu_item_id = mi.id
            WHERE o.business_id = ?
            GROUP BY o.id, o.status, o.total_amount, o.created_at, tr.number_label
            ORDER BY o.created_at DESC
            LIMIT ?
        ");

        $stmt->bindValue(1, self::getBusinessId(), PDO::PARAM_STR);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function getTotalCompletedRevenue(): float {
        $stmt = self::getDb()->prepare("
            SELECT SUM(total_amount)
            FROM orders
            WHERE business_id = ? AND status = 'completed'
        ");
        $stmt->execute([self::getBusinessId()]);
        return (float)$stmt->fetchColumn();
    }

    private static function assertTableBelongsToBusiness(string $tableRoomId, string $businessId): void {
        $stmt = self::getDb()->prepare("
            SELECT id
            FROM table_room
            WHERE id = ? AND business_id = ? AND type = 'table' AND status != 'disabled'
            LIMIT 1
        ");
        $stmt->execute([$tableRoomId, $businessId]);

        if (!$stmt->fetch()) {
            throw new \InvalidArgumentException('Invalid table_room_id.');
        }
    }

    private static function calculateOrderTotals(array $items, string $businessId): array {
        if (empty($items)) {
            throw new \InvalidArgumentException('At least one order item is required.');
        }

        $settingsStmt = self::getDb()->prepare("
            SELECT tax_percentage, service_charge_percentage
            FROM business_settings
            WHERE business_id = ?
            LIMIT 1
        ");
        $settingsStmt->execute([$businessId]);
        $settings = $settingsStmt->fetch() ?: ['tax_percentage' => 0, 'service_charge_percentage' => 0];

        $menuStmt = self::getDb()->prepare("
            SELECT id, price, discount_price
            FROM menu_item
            WHERE id = ? AND business_id = ? AND is_active = 1 AND is_available = 1
            LIMIT 1
        ");

        $subtotal = 0.0;
        $normalizedItems = [];

        foreach ($items as $item) {
            $menuItemId = (string)($item['menu_item_id'] ?? '');
            $quantity = max((int)($item['quantity'] ?? 0), 0);

            if ($menuItemId === '' || $quantity < 1) {
                throw new \InvalidArgumentException('Each item requires menu_item_id and quantity.');
            }

            $menuStmt->execute([$menuItemId, $businessId]);
            $menuItem = $menuStmt->fetch();

            if (!$menuItem) {
                throw new \InvalidArgumentException('Invalid or unavailable menu_item_id: ' . $menuItemId);
            }

            $unitPrice = $menuItem['discount_price'] !== null ? (float)$menuItem['discount_price'] : (float)$menuItem['price'];
            $subtotal += $unitPrice * $quantity;
            $normalizedItems[] = [
                'menu_item_id' => $menuItemId,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'special_instructions' => $item['special_instructions'] ?? null,
            ];
        }

        $taxAmount = round($subtotal * ((float)$settings['tax_percentage'] / 100), 2);
        $serviceCharge = round($subtotal * ((float)$settings['service_charge_percentage'] / 100), 2);

        return [
            'subtotal' => round($subtotal, 2),
            'tax_amount' => $taxAmount,
            'service_charge_amount' => $serviceCharge,
            'total_amount' => round($subtotal + $taxAmount + $serviceCharge, 2),
            'items' => $normalizedItems,
        ];
    }

    private static function insertOrderItems(string $orderId, array $items): void {
        $stmt = self::getDb()->prepare("
            INSERT INTO order_item (
                id, order_id, menu_item_id, quantity, unit_price, special_instructions,
                item_status, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW(), NOW())
        ");

        foreach ($items as $item) {
            $stmt->execute([
                self::uuid(),
                $orderId,
                $item['menu_item_id'],
                $item['quantity'],
                $item['unit_price'],
                $item['special_instructions'],
            ]);
        }
    }

    private static function markTableForOrder(string $tableRoomId, ?string $orderId, bool $occupied): void {
        $stmt = self::getDb()->prepare("
            UPDATE table_room
            SET active_order_id = ?, status = ?, updated_at = NOW()
            WHERE id = ? AND business_id = ?
        ");
        $stmt->execute([$orderId, $occupied ? 'occupied' : 'available', $tableRoomId, self::getBusinessId()]);
    }

    private static function uuid(): string {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

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
