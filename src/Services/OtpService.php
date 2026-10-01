<?php
declare(strict_types=1);

namespace Somen\InventoryManagementSystem\Services;

use Somen\InventoryManagementSystem\Core\Database;
use Somen\InventoryManagementSystem\Core\Env;

/**
 * 6-digit e-mail OTP. Valid for TTL seconds (DB clock), max MAX_ATTEMPTS wrong tries,
 * stored only as an HMAC hash. A new OTP can be requested only after the old one expired.
 */
final class OtpService
{
    public const TTL = 120;
    public const MAX_ATTEMPTS = 5;

    private static function db(): Database
    {
        return Database::getInstance();
    }

    private static function hash(string $otp): string
    {
        return hash_hmac('sha256', $otp, (string)Env::get('APP_KEY', 'ims-default-key'));
    }

    /** The currently usable OTP row (not consumed, not expired) plus seconds left. */
    public static function active(int $userId, string $purpose = 'register'): ?array
    {
        return self::db()->fetch(
            'SELECT id, attempts, TIMESTAMPDIFF(SECOND, NOW(), expires_at) AS seconds_left
             FROM otp_verifications
             WHERE user_id = ? AND purpose = ? AND consumed_at IS NULL AND expires_at > NOW()
             ORDER BY id DESC LIMIT 1',
            [$userId, $purpose]
        );
    }

    /** Create a fresh OTP (older ones are invalidated). Returns the plain OTP to e-mail. */
    public static function issue(int $userId, string $purpose = 'register'): array
    {
        $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $id = self::db()->transaction(function (Database $db) use ($userId, $purpose, $otp): int {
            $db->execute(
                'UPDATE otp_verifications SET consumed_at = NOW() WHERE user_id = ? AND purpose = ? AND consumed_at IS NULL',
                [$userId, $purpose]
            );
            return $db->insert(
                'INSERT INTO otp_verifications (user_id, purpose, otp_hash, expires_at)
                 VALUES (?, ?, ?, NOW() + INTERVAL ' . self::TTL . ' SECOND)',
                [$userId, $purpose, self::hash($otp)]
            );
        });
        return ['id' => $id, 'otp' => $otp, 'expires_in' => self::TTL];
    }

    /** Called when the e-mail could not be delivered: the timer must not start. */
    public static function discard(int $otpId): void
    {
        self::db()->execute('DELETE FROM otp_verifications WHERE id = ?', [$otpId]);
    }

    /**
     * @return array{status:string, attempts_left?:int}
     * status: OK | EXPIRED | LOCKED | INVALID
     */
    public static function verify(int $userId, string $otp, string $purpose = 'register'): array
    {
        return self::db()->transaction(fn(Database $db) => self::verifyLocked($db, $userId, $otp, $purpose));
    }

    private static function verifyLocked(Database $db, int $userId, string $otp, string $purpose): array
    {
        $row = $db->fetch(
            'SELECT id, otp_hash, attempts FROM otp_verifications
             WHERE user_id = ? AND purpose = ? AND consumed_at IS NULL AND expires_at > NOW()
             ORDER BY id DESC LIMIT 1 FOR UPDATE',
            [$userId, $purpose]
        );
        if ($row === null) {
            return ['status' => 'EXPIRED'];
        }
        if ((int)$row['attempts'] >= self::MAX_ATTEMPTS) {
            return ['status' => 'LOCKED'];
        }
        if (hash_equals((string)$row['otp_hash'], self::hash($otp))) {
            $db->execute('UPDATE otp_verifications SET consumed_at = NOW() WHERE id = ?', [$row['id']]);
            return ['status' => 'OK'];
        }
        $db->execute('UPDATE otp_verifications SET attempts = attempts + 1 WHERE id = ?', [$row['id']]);
        $left = self::MAX_ATTEMPTS - ((int)$row['attempts'] + 1);
        return $left <= 0 ? ['status' => 'LOCKED'] : ['status' => 'INVALID', 'attempts_left' => $left];
    }
}
