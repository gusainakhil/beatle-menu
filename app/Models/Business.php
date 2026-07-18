<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class Business extends Model {
    private const BUSINESS_TYPES = ['restaurant', 'hotel', 'villa', 'cafe', 'bar', 'resort', 'cloud_kitchen'];

    public static function find(string $id): ?array {
        $stmt = self::getDb()->prepare("
            SELECT *, business_name AS name, phone_number AS phone
            FROM business
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public static function findByEmail(string $email): ?array {
        $stmt = self::getDb()->prepare("
            SELECT *, business_name AS name, phone_number AS phone
            FROM business
            WHERE email = ?
        ");
        $stmt->execute([$email]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public static function findLoginByEmail(string $email): ?array {
        $stmt = self::getDb()->prepare("
            SELECT
                b.id,
                b.business_name AS name,
                b.email,
                u.id AS user_id,
                u.password_hash
            FROM app_user u
            JOIN business b ON u.business_id = b.id
            WHERE u.email = ?
              AND u.role = 'admin'
              AND u.status = 'active'
              AND b.status IN ('active', 'pending_verification')
            LIMIT 1
        ");
        $stmt->execute([$email]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public static function register(array $data): array {
        $db = self::getDb();

        $businessId = self::uuid();
        $adminId = self::uuid();
        $businessType = in_array($data['business_type'] ?? '', self::BUSINESS_TYPES, true)
            ? $data['business_type']
            : 'restaurant';

        $businessName = trim((string)$data['business_name']);
        $ownerName = trim((string)$data['owner_name']);
        $email = strtolower(trim((string)$data['email']));
        $phone = trim((string)$data['phone_number']);
        $passwordHash = password_hash((string)$data['password'], PASSWORD_DEFAULT);
        $latitude = is_numeric($data['latitude'] ?? null) ? (float)$data['latitude'] : 0.0000000;
        $longitude = is_numeric($data['longitude'] ?? null) ? (float)$data['longitude'] : 0.0000000;

        $db->beginTransaction();

        try {
            $stmt = $db->prepare("
                INSERT INTO business (
                    id, business_type, business_name, owner_name, phone_number, whatsapp_number,
                    email, website, tagline, description, opening_time, closing_time, weekly_off,
                    address, city, state, country, pin_code, latitude, longitude, currency,
                    language, timezone, gps_radius_meters, status, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW(), NOW()
                )
            ");
            $stmt->execute([
                $businessId,
                $businessType,
                $businessName,
                $ownerName,
                $phone,
                $data['whatsapp_number'] ?? $phone,
                $email,
                $data['website'] ?? null,
                $data['tagline'] ?? null,
                $data['description'] ?? null,
                $data['opening_time'] ?? null,
                $data['closing_time'] ?? null,
                $data['weekly_off'] ?? 'none',
                $data['address'] ?? null,
                $data['city'] ?? null,
                $data['state'] ?? null,
                $data['country'] ?? 'India',
                $data['pin_code'] ?? null,
                $latitude,
                $longitude,
                $data['currency'] ?? 'INR',
                $data['language'] ?? 'en',
                $data['timezone'] ?? 'Asia/Kolkata',
                is_numeric($data['gps_radius_meters'] ?? null) ? (int)$data['gps_radius_meters'] : 100,
            ]);

            $stmt = $db->prepare("
                INSERT INTO app_user (
                    id, business_id, role, name, username, password_hash, phone, email,
                    joining_date, status, created_at, updated_at
                ) VALUES (?, ?, 'admin', ?, ?, ?, ?, ?, CURDATE(), 'active', NOW(), NOW())
            ");
            $stmt->execute([
                $adminId,
                $businessId,
                $ownerName,
                $data['username'] ?? $email,
                $passwordHash,
                $phone,
                $email,
            ]);

            $stmt = $db->prepare("
                INSERT INTO business_settings (
                    id, business_id, tax_percentage, service_charge_percentage,
                    number_of_tables, number_of_rooms, notification_prefs, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([
                self::uuid(),
                $businessId,
                is_numeric($data['tax_percentage'] ?? null) ? (float)$data['tax_percentage'] : 0.00,
                is_numeric($data['service_charge_percentage'] ?? null) ? (float)$data['service_charge_percentage'] : 0.00,
                is_numeric($data['number_of_tables'] ?? null) ? (int)$data['number_of_tables'] : 0,
                is_numeric($data['number_of_rooms'] ?? null) ? (int)$data['number_of_rooms'] : 0,
                json_encode(['new_order' => true, 'feedback_received' => true]),
            ]);

            $db->commit();

            return [
                'business_id' => $businessId,
                'admin_user_id' => $adminId,
                'business_name' => $businessName,
                'email' => $email,
            ];
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function updateInfo(string $id, string $name, string $email, ?string $phone, ?string $address): bool {
        $db = self::getDb();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare("UPDATE business SET business_name = ?, email = ?, phone_number = ?, address = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$name, $email, $phone, $address, $id]);

            $stmt = $db->prepare("UPDATE app_user SET email = ?, phone = ?, updated_at = NOW() WHERE business_id = ? AND role = 'admin'");
            $stmt->execute([$email, $phone, $id]);

            $db->commit();
            return true;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function getSettings(string $businessId): array {
        $stmt = self::getDb()->prepare("SELECT * FROM business_settings WHERE business_id = ?");
        $stmt->execute([$businessId]);
        return $stmt->fetch() ?: [];
    }

    public static function updateSetting(string $businessId, string $key, string $value): bool {
        $stmt = self::getDb()->prepare("
            INSERT INTO platform_setting (setting_key, setting_value, updated_at) 
            VALUES (?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
        ");
        return $stmt->execute([$businessId . ':' . $key, json_encode($value)]);
    }

    private static function uuid(): string {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
