<?php
declare(strict_types=1);

namespace Somen\InventoryManagementSystem\Middleware;

use Somen\InventoryManagementSystem\Core\Csrf;
use Somen\InventoryManagementSystem\Core\Request;
use Somen\InventoryManagementSystem\Core\Response;

final class CsrfMiddleware
{
    public function handle(Request $request): ?Response
    {
        if (in_array($request->method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return null;
        }
        $token = $request->header('X-CSRF-Token') ?: (string)$request->input('_csrf', '');
        if (Csrf::verify($token)) {
            return null;
        }
        return Response::error('Security token expired. Please refresh the page and try again.', 419, [], 'CSRF_MISMATCH');
    }
}
