<?php

namespace App\Models;

use App\Core\Model;

class MenuItem extends Model {
    public static function getApiMenuItemDetail(string $id): ?array {
        $stmt = self::getDb()->prepare("
            SELECT
                m.*,
                c.name AS category_name
            FROM menu_item m
            JOIN category c ON m.category_id = c.id
            WHERE m.id = ? AND m.business_id = ? AND m.is_active = 1
            LIMIT 1
        ");
        $stmt->execute([$id, self::getBusinessId()]);
        $item = $stmt->fetch();
        return $item ?: null;
    }

    public static function getApiMenuItems(?string $categoryId = null, ?bool $available = null): array {
        $params = [self::getBusinessId()];
        $filters = '';

        if ($categoryId !== null && $categoryId !== '') {
            $filters .= ' AND m.category_id = ?';
            $params[] = $categoryId;
        }

        if ($available !== null) {
            $filters .= ' AND m.is_available = ?';
            $params[] = $available ? 1 : 0;
        }

        $stmt = self::getDb()->prepare("
            SELECT
                m.*,
                c.name AS category_name
            FROM menu_item m
            JOIN category c ON m.category_id = c.id
            WHERE m.business_id = ?
              AND m.is_active = 1
              {$filters}
            ORDER BY c.display_order ASC, c.name ASC, m.display_order ASC, m.name ASC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function createApiMenuItem(array $data): array {
        $id = self::uuid();
        $categoryId = self::resolveCategoryId($data);
        $price = self::resolvePrice($data);
        $dietaryType = self::normalizeDietaryType((string)($data['dietary_type'] ?? $data['type'] ?? 'veg'));

        $stmt = self::getDb()->prepare("
            INSERT INTO menu_item (
                id, business_id, category_id, name, description, price, discount_price,
                dietary_type, spicy_level, prep_time_minutes, image_url, gallery_urls,
                is_recommended, is_best_seller, is_todays_special, is_available,
                display_order, sku, barcode, is_active, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())
        ");
        $stmt->execute([
            $id,
            self::getBusinessId(),
            $categoryId,
            trim((string)$data['name']),
            $data['description'] ?? null,
            $price,
            self::nullableFloat($data['discount_price'] ?? null),
            $dietaryType,
            self::normalizeSpicyLevel((string)($data['spicy_level'] ?? 'none')),
            self::normalizePrepTime($data['prep_time_minutes'] ?? $data['cooking_time'] ?? null),
            $data['image_url'] ?? null,
            json_encode(self::normalizeGallery($data)),
            !empty($data['is_recommended']) ? 1 : 0,
            !empty($data['is_best_seller']) ? 1 : 0,
            !empty($data['is_todays_special']) ? 1 : 0,
            array_key_exists('is_available', $data) ? (!empty($data['is_available']) ? 1 : 0) : 1,
            (int)($data['display_order'] ?? 0),
            $data['sku'] ?? null,
            $data['barcode'] ?? null,
        ]);

        return self::getApiMenuItemDetail($id) ?: ['id' => $id];
    }

    public static function updateApiMenuItem(string $id, array $data): ?array {
        $existing = self::getApiMenuItemDetail($id);
        if (!$existing) {
            return null;
        }

        $categoryId = self::resolveCategoryId($data);
        $price = self::resolvePrice($data);
        $dietaryType = self::normalizeDietaryType((string)($data['dietary_type'] ?? $data['type'] ?? 'veg'));
        $imageUrl = array_key_exists('image_url', $data) ? $data['image_url'] : $existing['image_url'];

        $stmt = self::getDb()->prepare("
            UPDATE menu_item
            SET category_id = ?, name = ?, description = ?, price = ?, discount_price = ?,
                dietary_type = ?, spicy_level = ?, prep_time_minutes = ?, image_url = ?,
                gallery_urls = ?, is_recommended = ?, is_best_seller = ?, is_todays_special = ?,
                is_available = ?, display_order = ?, sku = ?, barcode = ?, updated_at = NOW()
            WHERE id = ? AND business_id = ? AND is_active = 1
        ");
        $stmt->execute([
            $categoryId,
            trim((string)$data['name']),
            $data['description'] ?? null,
            $price,
            self::nullableFloat($data['discount_price'] ?? null),
            $dietaryType,
            self::normalizeSpicyLevel((string)($data['spicy_level'] ?? 'none')),
            self::normalizePrepTime($data['prep_time_minutes'] ?? $data['cooking_time'] ?? null),
            $imageUrl,
            json_encode(self::normalizeGallery($data)),
            !empty($data['is_recommended']) ? 1 : 0,
            !empty($data['is_best_seller']) ? 1 : 0,
            !empty($data['is_todays_special']) ? 1 : 0,
            array_key_exists('is_available', $data) ? (!empty($data['is_available']) ? 1 : 0) : 1,
            (int)($data['display_order'] ?? 0),
            $data['sku'] ?? null,
            $data['barcode'] ?? null,
            $id,
            self::getBusinessId(),
        ]);

        return self::getApiMenuItemDetail($id);
    }

    public static function updateApiAvailability(string $id, bool $available, ?string $imageUrl = null): ?array {
        $imageSql = $imageUrl !== null ? ', image_url = ?' : '';
        $params = [$available ? 1 : 0];

        if ($imageUrl !== null) {
            $params[] = $imageUrl;
        }

        $params[] = $id;
        $params[] = self::getBusinessId();

        $stmt = self::getDb()->prepare("
            UPDATE menu_item
            SET is_available = ?{$imageSql}, updated_at = NOW()
            WHERE id = ? AND business_id = ? AND is_active = 1
        ");
        $stmt->execute($params);

        if ($stmt->rowCount() < 1) {
            return self::getApiMenuItemDetail($id);
        }

        return self::getApiMenuItemDetail($id);
    }

    public static function deleteApiMenuItem(string $id): bool {
        $stmt = self::getDb()->prepare("
            UPDATE menu_item
            SET is_active = 0, updated_at = NOW()
            WHERE id = ? AND business_id = ? AND is_active = 1
        ");
        $stmt->execute([$id, self::getBusinessId()]);
        return $stmt->rowCount() > 0;
    }

    public static function all(): array {
        $stmt = self::getDb()->prepare("
            SELECT m.*, c.name as category_name 
            FROM menu_item m 
            JOIN category c ON m.category_id = c.id 
            WHERE m.business_id = ? 
              AND m.is_active = 1
            ORDER BY c.display_order ASC, c.name ASC, m.display_order ASC, m.name ASC
        ");
        $stmt->execute([self::getBusinessId()]);
        return $stmt->fetchAll();
    }

    private static function resolveCategoryId(array $data): string {
        $categoryId = trim((string)($data['category_id'] ?? ''));
        if ($categoryId !== '') {
            if (!Category::belongsToBusiness($categoryId)) {
                throw new \InvalidArgumentException('Invalid category_id.');
            }
            return $categoryId;
        }

        $categoryName = trim((string)($data['category_name'] ?? $data['category'] ?? ''));
        return Category::findOrCreateByName($categoryName)['id'];
    }

    private static function resolvePrice(array $data): float {
        if (isset($data['variants']) && is_array($data['variants']) && !empty($data['variants'])) {
            $firstVariant = $data['variants'][0];
            return self::positiveFloat($firstVariant['price'] ?? null, 'Variant price is required.');
        }

        return self::positiveFloat($data['price'] ?? null, 'Price is required.');
    }

    private static function positiveFloat(mixed $value, string $message): float {
        if (!is_numeric($value) || (float)$value < 0) {
            throw new \InvalidArgumentException($message);
        }

        return round((float)$value, 2);
    }

    private static function nullableFloat(mixed $value): ?float {
        return is_numeric($value) ? round((float)$value, 2) : null;
    }

    private static function normalizeDietaryType(string $type): string {
        $type = strtolower(trim($type));
        $type = str_replace('-', '', $type);
        return in_array($type, ['veg', 'nonveg', 'egg', 'jain'], true) ? $type : 'veg';
    }

    private static function normalizeSpicyLevel(string $level): string {
        $level = strtolower(trim($level));
        return in_array($level, ['none', 'mild', 'medium', 'hot', 'extra_hot'], true) ? $level : 'none';
    }

    private static function normalizePrepTime(mixed $value): ?int {
        if (is_numeric($value)) {
            return (int)$value;
        }

        if (is_string($value) && preg_match('/\d+/', $value, $matches)) {
            return (int)$matches[0];
        }

        return null;
    }

    private static function normalizeGallery(array $data): array {
        $gallery = $data['gallery_urls'] ?? [];
        return is_array($gallery) ? array_values($gallery) : [];
    }

    private static function uuid(): string {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
