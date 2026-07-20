<?php

namespace App\Models;

use App\Core\Model;

class Category extends Model {
    public static function getApiCategories(): array {
        $stmt = self::getDb()->prepare("
            SELECT *, 'utensils' AS icon
            FROM category
            WHERE business_id = ? AND is_active = 1
            ORDER BY display_order ASC, name ASC
        ");
        $stmt->execute([self::getBusinessId()]);
        return $stmt->fetchAll();
    }

    public static function findOrCreateByName(string $name): array {
        $name = trim($name);
        if ($name === '') {
            throw new \InvalidArgumentException('Category name is required.');
        }

        $stmt = self::getDb()->prepare("
            SELECT *
            FROM category
            WHERE business_id = ? AND LOWER(name) = LOWER(?) AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute([self::getBusinessId(), $name]);
        $category = $stmt->fetch();

        if ($category) {
            return $category;
        }

        $id = self::uuid();
        $insert = self::getDb()->prepare("
            INSERT INTO category (id, business_id, name, display_order, is_active, is_hidden, created_at, updated_at)
            VALUES (?, ?, ?, 0, 1, 0, NOW(), NOW())
        ");
        $insert->execute([$id, self::getBusinessId(), $name]);

        return [
            'id' => $id,
            'business_id' => self::getBusinessId(),
            'name' => $name,
            'display_order' => 0,
            'is_active' => 1,
            'is_hidden' => 0,
        ];
    }

    public static function belongsToBusiness(string $categoryId): bool {
        $stmt = self::getDb()->prepare("
            SELECT id
            FROM category
            WHERE id = ? AND business_id = ? AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute([$categoryId, self::getBusinessId()]);
        return (bool)$stmt->fetch();
    }

    public static function all(): array {
        $stmt = self::getDb()->prepare("SELECT *, 'utensils' AS icon FROM category WHERE business_id = ? AND is_active = 1 ORDER BY display_order ASC, name ASC");
        $stmt->execute([self::getBusinessId()]);
        return $stmt->fetchAll();
    }

    private static function uuid(): string {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
