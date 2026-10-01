<?php
declare(strict_types=1);

namespace Somen\InventoryManagementSystem\Core;

final class Response
{
    public function __construct(
        private string $body = '',
        private int $status = 200,
        private array $headers = []
    ) {
    }

    public static function json(array $payload, int $status = 200): self
    {
        return new self(
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE),
            $status,
            ['Content-Type' => 'application/json; charset=utf-8', 'Cache-Control' => 'no-store']
        );
    }

    /** Standard success envelope: {success, message, data, errors, meta} */
    public static function success(string $message = 'OK', mixed $data = null, int $status = 200, array $meta = []): self
    {
        return self::json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
            'errors'  => new \stdClass(),
            'meta'    => (object)$meta,
        ], $status);
    }

    /** Standard error envelope. $code is a machine-readable string (e.g. MAIL_FAILED). */
    public static function error(string $message, int $status = 400, array $errors = [], ?string $code = null, mixed $data = null): self
    {
        return self::json([
            'success' => false,
            'message' => $message,
            'code'    => $code,
            'data'    => $data,
            'errors'  => (object)$errors,
            'meta'    => new \stdClass(),
        ], $status);
    }

    public static function html(string $html, int $status = 200): self
    {
        return new self($html, $status, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public static function redirect(string $url, int $status = 302): self
    {
        return new self('', $status, ['Location' => $url]);
    }

    public static function raw(string $body, string $contentType, int $status = 200, array $headers = []): self
    {
        return new self($body, $status, array_merge(['Content-Type' => $contentType], $headers));
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
            header('Referrer-Policy: same-origin');
            foreach ($this->headers as $k => $v) {
                header("{$k}: {$v}");
            }
        }
        echo $this->body;
    }
}
