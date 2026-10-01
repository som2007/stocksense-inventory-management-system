<?php
declare(strict_types=1);

namespace Somen\InventoryManagementSystem\Services;

use Somen\InventoryManagementSystem\Core\Database;

/**
 * Forgot-password links: 32-byte random token, only its SHA-256 is stored.
 * Valid 8 hours and strictly one-time (used_at). Issuing a new link voids older ones.
 */
final class PasswordResetService
{
    public const TTL_HOURS = 8;

    private static function db(): Database
    {
        return Database::getInstance();
    }

    public static function issue(int $userId): array
    {
        $token = bin2hex(random_bytes(32));
        $id = self::db()->transaction(function (Database $db) use ($userId, $token): int {
            $db->execute('UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL', [$userId]);
            return $db->insert(
                'INSERT INTO password_resets (user_id, token_hash, expires_at)
                 VALUES (?, ?, NOW() + INTERVAL ' . self::TTL_HOURS . ' HOUR)',
                [$userId, hash('sha256', $token)]
            );
        });
        return ['id' => $id, 'token' => $token];
    }

    public static function discard(int $id): void
    {
        self::db()->execute('DELETE FROM password_resets WHERE id = ?', [$id]);
    }

    /** Returns the reset row (with user_id) if token is valid, unused and unexpired. */
    public static function find(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        return self::db()->fetch(
            'SELECT id, user_id FROM password_resets
             WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1',
            [hash('sha256', $token)]
        );
    }

    /** Atomically burn the token and set the new password. Returns false if token no longer valid. */
    public static function consume(string $token, string $passwordHash): bool
    {
        return (bool)self::db()->transaction(function (Database $db) use ($token, $passwordHash): bool {
            $row = $db->fetch(
                'SELECT id, user_id FROM password_resets
                 WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1 FOR UPDATE',
                [hash('sha256', $token)]
            );
            if ($row === null) {
                return false;
            }
            $db->execute('UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL', [$row['user_id']]);
            $db->execute('UPDATE users SET password_hash = ? WHERE id = ?', [$passwordHash, $row['user_id']]);
            return true;
        });
    }
}
