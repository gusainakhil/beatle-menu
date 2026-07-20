<?php

namespace App\Models;

use App\Core\Model;

class ServiceUnit extends Model {
    public static function getApiTables(?string $status = null): array {
        $params = [self::getBusinessId()];
        $statusSql = '';

        if ($status !== null && $status !== '') {
            $statusSql = ' AND tr.status = ?';
            $params[] = $status;
        }

        $stmt = self::getDb()->prepare("
            SELECT
                tr.*,
                CONCAT('ORD-', UPPER(LEFT(REPLACE(o.id, '-', ''), 8))) AS active_order_number,
                o.status AS active_order_status,
                o.total_amount AS active_order_total
            FROM table_room tr
            LEFT JOIN orders o ON tr.active_order_id = o.id
            WHERE tr.business_id = ?
              AND tr.type = 'table'
              {$statusSql}
            ORDER BY tr.number_label ASC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function all(): array {
        $stmt = self::getDb()->prepare("
            SELECT *, number_label AS name
            FROM table_room
            WHERE business_id = ?
            ORDER BY type ASC, number_label ASC
        ");
        $stmt->execute([self::getBusinessId()]);
        return $stmt->fetchAll();
    }

    public static function getTableStats(): array {
        $stmt = self::getDb()->prepare("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'occupied' OR active_order_id IS NOT NULL THEN 1 ELSE 0 END) AS active
            FROM table_room
            WHERE business_id = ? AND type = 'table'
        ");
        $stmt->execute([self::getBusinessId()]);
        $stats = $stmt->fetch() ?: [];

        return [
            'active' => (int)($stats['active'] ?? 0),
            'total' => (int)($stats['total'] ?? 0),
        ];
    }
}
