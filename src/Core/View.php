<?php
declare(strict_types=1);

namespace Somen\InventoryManagementSystem\Core;

final class View
{
    public static function root(): string
    {
        return dirname(__DIR__, 2) . '/views';
    }

    /** Render a view file inside an optional layout. $layout receives $content + $data. */
    public static function render(string $view, array $data = [], ?string $layout = 'layouts/app'): string
    {
        $content = self::capture($view, $data);
        if ($layout === null) {
            return $content;
        }
        return self::capture($layout, $data + ['content' => $content]);
    }

    private static function capture(string $view, array $data): string
    {
        $file = self::root() . '/' . $view . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("View not found: {$view}");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        try {
            require $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string)ob_get_clean();
    }
}
