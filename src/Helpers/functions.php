<?php
declare(strict_types=1);

use Somen\InventoryManagementSystem\Core\Csrf;
use Somen\InventoryManagementSystem\Core\Env;
use Somen\InventoryManagementSystem\Core\Request;
use Somen\InventoryManagementSystem\Core\Session;

if (!function_exists('e')) {
    function e(mixed $v): string
    {
        return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('base_url')) {
    /** Path prefix where public/ lives. */
    function base_url(string $path = ''): string
    {
        return Request::basePath() . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        $file = dirname(__DIR__, 2) . '/public/assets/' . ltrim($path, '/');
        $v = is_file($file) ? filemtime($file) : 1;
        return base_url('assets/' . ltrim($path, '/')) . '?v=' . $v;
    }
}

if (!function_exists('app_url')) {
    /** Absolute URL used inside e-mails. Prefers APP_URL from .env (prevents Host-header spoofing). */
    function app_url(string $path = ''): string
    {
        $configured = rtrim((string)Env::get('APP_URL', ''), '/');
        if ($configured !== '') {
            return $configured . '/' . ltrim($path, '/');
        }
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return ($https ? 'https://' : 'http://') . $host . base_url($path);
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Csrf::token();
    }
}

if (!function_exists('auth_user')) {
    function auth_user(): ?array
    {
        $u = Session::get('auth_user');
        return is_array($u) ? $u : null;
    }
}

if (!function_exists('role_label')) {
    function role_label(string $role): string
    {
        return $role === 'inventory_manager' ? 'Inventory Manager' : 'Warehouse Staff';
    }
}

if (!function_exists('initials')) {
    function initials(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name)) ?: [];
        $out = '';
        foreach (array_slice($parts, 0, 2) as $p) {
            $out .= mb_strtoupper(mb_substr($p, 0, 1));
        }
        return $out ?: '?';
    }
}
