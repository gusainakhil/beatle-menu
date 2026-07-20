<?php

namespace App\Models;

use App\Core\Model;

class AppUser extends Model {
    public static function getApiUsers(?string $role = null, ?string $status = null): array {
        $params = [self::getBusinessId()];
        $filters = '';

        if ($role !== null && $role !== '') {
            $filters .= ' AND role = ?';
            $params[] = self::normalizeRole($role);
        }

        if ($status !== null && $status !== '') {
            $filters .= ' AND status = ?';
            $params[] = self::normalizeStatus($status);
        }

        $stmt = self::getDb()->prepare("
            SELECT *, UPPER(CONCAT(LEFT(name, 1), IF(LOCATE(' ', name) > 0, SUBSTRING(name, LOCATE(' ', name) + 1, 1), ''))) AS avatar
            FROM app_user
            WHERE business_id = ?
              AND role IN ('admin', 'waiter')
              {$filters}
            ORDER BY role ASC, name ASC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function getApiDetail(string $id): ?array {
        $stmt = self::getDb()->prepare("
            SELECT *, UPPER(CONCAT(LEFT(name, 1), IF(LOCATE(' ', name) > 0, SUBSTRING(name, LOCATE(' ', name) + 1, 1), ''))) AS avatar
            FROM app_user
            WHERE id = ? AND business_id = ? AND role IN ('admin', 'waiter')
            LIMIT 1
        ");
        $stmt->execute([$id, self::getBusinessId()]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function createApi(array $data): array {
        $id = self::uuid();
        $role = self::normalizeRole((string)($data['role'] ?? 'waiter'));
        $status = self::normalizeStatus((string)($data['status'] ?? 'active'));
        $name = trim((string)($data['name'] ?? ''));

        if ($name === '') {
            throw new \InvalidArgumentException('name is required.');
        }

        $passwordHash = !empty($data['password']) ? password_hash((string)$data['password'], PASSWORD_DEFAULT) : null;

        $stmt = self::getDb()->prepare("
            INSERT INTO app_user (
                id, business_id, role, name, employee_id, username, password_hash,
                phone, email, address, photo_url, joining_date, status, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $id,
            self::getBusinessId(),
            $role,
            $name,
            $data['employee_id'] ?? null,
            $data['username'] ?? null,
            $passwordHash,
            $data['phone'] ?? null,
            $data['email'] ?? null,
            $data['address'] ?? null,
            $data['photo_url'] ?? null,
            $data['joining_date'] ?? date('Y-m-d'),
            $status,
        ]);

        return self::getApiDetail($id) ?: ['id' => $id];
    }

    public static function updateApi(string $id, array $data): ?array {
        $existing = self::getApiDetail($id);
        if (!$existing) {
            return null;
        }

        $role = self::normalizeRole((string)($data['role'] ?? $existing['role']));
        $status = self::normalizeStatus((string)($data['status'] ?? $existing['status']));
        $name = trim((string)($data['name'] ?? $existing['name']));
        $photoUrl = array_key_exists('photo_url', $data) ? $data['photo_url'] : $existing['photo_url'];
        $passwordHash = !empty($data['password'])
            ? password_hash((string)$data['password'], PASSWORD_DEFAULT)
            : $existing['password_hash'];

        if ($name === '') {
            throw new \InvalidArgumentException('name is required.');
        }

        $stmt = self::getDb()->prepare("
            UPDATE app_user
            SET role = ?, name = ?, employee_id = ?, username = ?, password_hash = ?,
                phone = ?, email = ?, address = ?, photo_url = ?, joining_date = ?,
                status = ?, updated_at = NOW()
            WHERE id = ? AND business_id = ? AND role IN ('admin', 'waiter')
        ");
        $stmt->execute([
            $role,
            $name,
            $data['employee_id'] ?? $existing['employee_id'],
            $data['username'] ?? $existing['username'],
            $passwordHash,
            $data['phone'] ?? $existing['phone'],
            $data['email'] ?? $existing['email'],
            $data['address'] ?? $existing['address'],
            $photoUrl,
            $data['joining_date'] ?? $existing['joining_date'],
            $status,
            $id,
            self::getBusinessId(),
        ]);

        return self::getApiDetail($id);
    }

    public static function updateApiStatus(string $id, string $status): ?array {
        if (!self::getApiDetail($id)) {
            return null;
        }

        $stmt = self::getDb()->prepare("
            UPDATE app_user
            SET status = ?, updated_at = NOW()
            WHERE id = ? AND business_id = ? AND role IN ('admin', 'waiter')
        ");
        $stmt->execute([self::normalizeStatus($status), $id, self::getBusinessId()]);
        return self::getApiDetail($id);
    }

    public static function deleteApi(string $id): bool {
        $stmt = self::getDb()->prepare("
            UPDATE app_user
            SET status = 'deactivated', updated_at = NOW()
            WHERE id = ? AND business_id = ? AND role IN ('admin', 'waiter')
        ");
        $stmt->execute([$id, self::getBusinessId()]);
        return $stmt->rowCount() > 0;
    }

    private static function normalizeRole(string $role): string {
        $role = strtolower(trim($role));
        if (!in_array($role, ['admin', 'waiter'], true)) {
            throw new \InvalidArgumentException('Invalid role.');
        }
        return $role;
    }

    private static function normalizeStatus(string $status): string {
        $status = strtolower(trim($status));
        if (!in_array($status, ['active', 'inactive', 'deactivated'], true)) {
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
