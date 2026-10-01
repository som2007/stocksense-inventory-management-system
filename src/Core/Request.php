<?php
declare(strict_types=1);

namespace Somen\InventoryManagementSystem\Core;

final class Request
{
    private array $json = [];
    public array $params = [];

    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $post,
        public readonly array $server
    ) {
        $ctype = strtolower($this->header('Content-Type', ''));
        if (str_contains($ctype, 'application/json')) {
            $raw = file_get_contents('php://input') ?: '';
            $decoded = json_decode($raw, true);
            $this->json = is_array($decoded) ? $decoded : [];
        }
    }

    public static function capture(): self
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        // REQUEST_URI keeps the ORIGINAL url. With the root .htaccess (project folder as web root)
        // that is ".../project/login" while SCRIPT_NAME is ".../project/public/index.php", so try both.
        $base = self::basePath();
        $candidates = [$base];
        if (str_ends_with($base, '/public')) {
            $candidates[] = substr($base, 0, -7);
        }
        foreach ($candidates as $b) {
            if ($b !== '' && ($uri === $b || str_starts_with($uri, $b . '/'))) {
                $uri = substr($uri, strlen($b));
                break;
            }
        }
        $uri = '/' . trim($uri, '/');
        // Allow running through /index.php/... as well
        if (str_starts_with($uri, '/index.php')) {
            $uri = '/' . trim(substr($uri, strlen('/index.php')), '/');
        }
        return new self(strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'), $uri, $_GET, $_POST, $_SERVER);
    }

    /** URL path where public/ is served (no trailing slash), e.g. "/inventory-management-system/public" */
    public static function basePath(): string
    {
        $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
        return $dir === '/' || $dir === '.' ? '' : rtrim($dir, '/');
    }

    public function header(string $name, string $default = ''): string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        if ($name === 'Content-Type') {
            $key = 'CONTENT_TYPE';
        }
        return (string)($this->server[$key] ?? $default);
    }

    /** Merged input: JSON body > POST > GET */
    public function all(): array
    {
        return array_merge($this->query, $this->post, $this->json);
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function string(string $key, string $default = ''): string
    {
        $v = $this->input($key, $default);
        return is_scalar($v) ? trim((string)$v) : $default;
    }

    public function ip(): string
    {
        return (string)($this->server['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    public function isApi(): bool
    {
        return str_starts_with($this->path, '/api/');
    }
}
