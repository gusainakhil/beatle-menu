<?php

namespace App\Models;

use App\Core\Model;

class QrCode extends Model {
    public static function all(): array {
        $stmt = self::getDb()->prepare("
            SELECT
                q.*,
                tr.number_label as service_unit_name,
                tr.type as service_unit_type,
                tr.status as service_unit_status
            FROM qr_code q
            JOIN table_room tr ON q.table_room_id = tr.id
            WHERE q.business_id = ?
            ORDER BY tr.type ASC, tr.number_label ASC
        ");
        $stmt->execute([self::getBusinessId()]);
        return $stmt->fetchAll();
    }

    public static function dashboardRows(): array {
        $stmt = self::getDb()->prepare("
            SELECT
                tr.id AS table_room_id,
                tr.number_label AS service_unit_name,
                tr.type AS service_unit_type,
                tr.status AS service_unit_status,
                q.id AS qr_id,
                q.encrypted_token,
                q.is_active,
                q.revoked_at,
                q.created_at
            FROM table_room tr
            LEFT JOIN qr_code q
                ON q.table_room_id = tr.id
               AND q.business_id = tr.business_id
               AND q.is_active = 1
            WHERE tr.business_id = ?
            ORDER BY tr.type ASC, tr.number_label ASC
        ");
        $stmt->execute([self::getBusinessId()]);
        return $stmt->fetchAll();
    }

    public static function generateForTableRoom(string $tableRoomId): string {
        $db = self::getDb();
        $businessId = self::getBusinessId();

        $stmt = $db->prepare("SELECT id FROM table_room WHERE id = ? AND business_id = ?");
        $stmt->execute([$tableRoomId, $businessId]);
        if (!$stmt->fetch()) {
            throw new \InvalidArgumentException('Selected table or room was not found.');
        }

        $token = self::makeToken();

        $db->beginTransaction();
        try {
            $stmt = $db->prepare("
                UPDATE qr_code
                SET is_active = 0, revoked_at = NOW()
                WHERE business_id = ? AND table_room_id = ? AND is_active = 1
            ");
            $stmt->execute([$businessId, $tableRoomId]);

            $stmt = $db->prepare("
                INSERT INTO qr_code (business_id, table_room_id, encrypted_token, is_active, created_at)
                VALUES (?, ?, ?, 1, NOW())
            ");
            $stmt->execute([$businessId, $tableRoomId, $token]);

            $db->commit();
            return $token;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function generateForAllMissing(): int {
        $rows = self::dashboardRows();
        $created = 0;

        foreach ($rows as $row) {
            if (empty($row['qr_id'])) {
                self::generateForTableRoom($row['table_room_id']);
                $created++;
            }
        }

        return $created;
    }

    public static function revoke(string $qrId): bool {
        $stmt = self::getDb()->prepare("
            UPDATE qr_code
            SET is_active = 0, revoked_at = NOW()
            WHERE id = ? AND business_id = ? AND is_active = 1
        ");
        $stmt->execute([$qrId, self::getBusinessId()]);
        return $stmt->rowCount() > 0;
    }

    private static function makeToken(): string {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }
}
