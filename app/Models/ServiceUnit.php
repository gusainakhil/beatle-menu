<?php

namespace App\Models;

use App\Core\Model;

class ServiceUnit extends Model {
    public static function getApiTables(?string $status = null, ?string $type = 'table'): array {
        $params = [self::getBusinessId()];
        $statusSql = '';
        $typeSql = '';

        if ($status !== null && $status !== '') {
            $statusSql = ' AND tr.status = ?';
            $params[] = $status;
        }

        if ($type !== null && $type !== '') {
            $typeSql = ' AND tr.type = ?';
            $params[] = $type;
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
              {$statusSql}
              {$typeSql}
            ORDER BY tr.type ASC, tr.number_label ASC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function getApiDetail(string $id): ?array {
        $stmt = self::getDb()->prepare("
            SELECT
                tr.*,
                CONCAT('ORD-', UPPER(LEFT(REPLACE(o.id, '-', ''), 8))) AS active_order_number,
                o.status AS active_order_status,
                o.total_amount AS active_order_total
            FROM table_room tr
            LEFT JOIN orders o ON tr.active_order_id = o.id
            WHERE tr.id = ? AND tr.business_id = ?
            LIMIT 1
        ");
        $stmt->execute([$id, self::getBusinessId()]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function createApi(array $data): array {
        $id = self::uuid();
        $type = self::normalizeType((string)($data['type'] ?? 'table'));
        $status = self::normalizeStatus((string)($data['status'] ?? 'available'));
        $numberLabel = trim((string)($data['number_label'] ?? $data['name'] ?? ''));

        if ($numberLabel === '') {
            throw new \InvalidArgumentException('number_label is required.');
        }

        $stmt = self::getDb()->prepare("
            INSERT INTO table_room (id, business_id, type, number_label, status, active_order_id, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, NULL, NOW(), NOW())
        ");
        $stmt->execute([$id, self::getBusinessId(), $type, $numberLabel, $status]);

        return self::getApiDetail($id) ?: ['id' => $id];
    }

    public static function updateApi(string $id, array $data): ?array {
        $existing = self::getApiDetail($id);
        if (!$existing) {
            return null;
        }

        $type = self::normalizeType((string)($data['type'] ?? 'table'));
        $status = self::normalizeStatus((string)($data['status'] ?? 'available'));
        $numberLabel = trim((string)($data['number_label'] ?? $data['name'] ?? ''));

        if ($numberLabel === '') {
            throw new \InvalidArgumentException('number_label is required.');
        }

        $stmt = self::getDb()->prepare("
            UPDATE table_room
            SET type = ?, number_label = ?, status = ?, updated_at = NOW()
            WHERE id = ? AND business_id = ?
        ");
        $stmt->execute([$type, $numberLabel, $status, $id, self::getBusinessId()]);

        return self::getApiDetail($id);
    }

    public static function updateApiStatus(string $id, string $status): ?array {
        if (!self::getApiDetail($id)) {
            return null;
        }

        $status = self::normalizeStatus($status);
        $clearActiveOrder = $status !== 'occupied';

        $stmt = self::getDb()->prepare("
            UPDATE table_room
            SET status = ?, active_order_id = IF(? = 1, NULL, active_order_id), updated_at = NOW()
            WHERE id = ? AND business_id = ?
        ");
        $stmt->execute([$status, $clearActiveOrder ? 1 : 0, $id, self::getBusinessId()]);

        return self::getApiDetail($id);
    }

    public static function deleteApi(string $id): bool {
        $row = self::getApiDetail($id);
        if (!$row) {
            return false;
        }

        if (!empty($row['active_order_id']) || $row['status'] === 'occupied') {
            throw new \InvalidArgumentException('Occupied table/room cannot be deleted.');
        }

        $stmt = self::getDb()->prepare("DELETE FROM table_room WHERE id = ? AND business_id = ?");
        $stmt->execute([$id, self::getBusinessId()]);
        return $stmt->rowCount() > 0;
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

    private static function normalizeType(string $type): string {
        $type = strtolower(trim($type));
        return in_array($type, ['table', 'room'], true) ? $type : 'table';
    }

    private static function normalizeStatus(string $status): string {
        $status = strtolower(trim($status));
        if (!in_array($status, ['available', 'occupied', 'disabled'], true)) {
            throw new \InvalidArgumentException('Invalid status.');
        }
        return $status;
    }

    private static function uuid(): string {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
