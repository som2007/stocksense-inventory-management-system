<?php
declare(strict_types=1);

namespace Somen\InventoryManagementSystem\Core;

/**
 * DB-backed sliding-window rate limiter (uses DB clock, so app/DB time can never disagree).
 */
final class RateLimiter
{
    public static function hit(string $action, string $identifier): void
    {
        Database::getInstance()->execute(
            'INSERT INTO rate_limits (action, identifier, created_at) VALUES (?, ?, NOW())',
            [$action, substr($identifier, 0, 190)]
        );
    }

    public static function attempts(string $action, string $identifier, int $windowSeconds): int
    {
        return (int)Database::getInstance()->scalar(
            'SELECT COUNT(*) FROM rate_limits WHERE action = ? AND identifier = ? AND created_at > (NOW() - INTERVAL ? SECOND)',
            [$action, substr($identifier, 0, 190), $windowSeconds]
        );
    }

    public static function tooMany(string $action, string $identifier, int $max, int $windowSeconds): bool
    {
        return self::attempts($action, $identifier, $windowSeconds) >= $max;
    }

    public static function clear(string $action, string $identifier): void
    {
        Database::getInstance()->execute(
            'DELETE FROM rate_limits WHERE action = ? AND identifier = ?',
            [$action, substr($identifier, 0, 190)]
        );
    }

    /** Housekeeping: drop entries older than a day. */
    public static function purge(): void
    {
        Database::getInstance()->execute('DELETE FROM rate_limits WHERE created_at < (NOW() - INTERVAL 1 DAY)');
    }
}
