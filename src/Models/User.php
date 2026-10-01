<?php
declare(strict_types=1);

namespace Somen\InventoryManagementSystem\Models;

use Somen\InventoryManagementSystem\Core\Database;

final class User
{
    private static function db(): Database
    {
        return Database::getInstance();
    }

    public static function findById(int $id): ?array
    {
        return self::db()->fetch('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public static function findByEmail(string $email): ?array
    {
        return self::db()->fetch('SELECT * FROM users WHERE email = ?', [mb_strtolower($email)]);
    }

    /** "Taken" only counts VERIFIED accounts; an unverified row can still be resumed by its owner. */
    public static function emailTaken(string $email, ?int $exceptId = null): bool
    {
        return (bool)self::db()->scalar(
            'SELECT 1 FROM users WHERE email = ? AND is_verified = 1 AND id <> ? LIMIT 1',
            [mb_strtolower($email), $exceptId ?? 0]
        );
    }

    public static function phoneTaken(string $phone, ?int $exceptId = null): bool
    {
        return (bool)self::db()->scalar(
            'SELECT 1 FROM users WHERE phone = ? AND is_verified = 1 AND id <> ? LIMIT 1',
            [$phone, $exceptId ?? 0]
        );
    }

    /** Remove unverified leftovers (no data yet) that hold a phone/e-mail we need. */
    public static function purgeUnverifiedConflicts(string $email, string $phone, ?int $exceptId = null): void
    {
        self::db()->execute(
            'DELETE FROM users WHERE is_verified = 0 AND id <> ? AND (phone = ? OR email = ?)',
            [$exceptId ?? 0, $phone, mb_strtolower($email)]
        );
    }

    public static function purgeStaleUnverified(): void
    {
        self::db()->execute('DELETE FROM users WHERE is_verified = 0 AND created_at < (NOW() - INTERVAL 1 DAY)');
    }

    public static function create(array $d): int
    {
        return self::db()->insert(
            'INSERT INTO users (full_name, email, phone, password_hash, role, is_verified, status)
             VALUES (?, ?, ?, ?, ?, 0, \'active\')',
            [$d['full_name'], mb_strtolower($d['email']), $d['phone'], $d['password_hash'], $d['role']]
        );
    }

    /** Re-registration of an unverified e-mail: refresh its details, keep the row. */
    public static function refreshUnverified(int $id, array $d): void
    {
        self::db()->execute(
            'UPDATE users SET full_name = ?, phone = ?, password_hash = ?, role = ?, created_at = NOW()
             WHERE id = ? AND is_verified = 0',
            [$d['full_name'], $d['phone'], $d['password_hash'], $d['role'], $id]
        );
    }

    public static function markVerified(int $id): void
    {
        self::db()->execute('UPDATE users SET is_verified = 1, email_verified_at = NOW() WHERE id = ?', [$id]);
    }

    public static function touchLogin(int $id): void
    {
        self::db()->execute('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$id]);
    }

    public static function updateProfile(int $id, string $name, string $phone): void
    {
        self::db()->execute('UPDATE users SET full_name = ?, phone = ? WHERE id = ?', [$name, $phone, $id]);
    }

    public static function updatePassword(int $id, string $hash): void
    {
        self::db()->execute('UPDATE users SET password_hash = ? WHERE id = ?', [$hash, $id]);
    }

    /** Safe subset stored in the session / sent to the browser. */
    public static function publicData(array $u): array
    {
        return [
            'id'         => (int)$u['id'],
            'full_name'  => $u['full_name'],
            'email'      => $u['email'],
            'phone'      => $u['phone'],
            'role'       => $u['role'],
            'last_login' => $u['last_login_at'] ?? null,
            'created_at' => $u['created_at'] ?? null,
        ];
    }
}
