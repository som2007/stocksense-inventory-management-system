<?php
declare(strict_types=1);

namespace Somen\InventoryManagementSystem\Middleware;

use Somen\InventoryManagementSystem\Core\Request;
use Somen\InventoryManagementSystem\Core\Response;

final class AuthMiddleware
{
    public function handle(Request $request): ?Response
    {
        if (auth_user() !== null) {
            return null;
        }
        if ($request->isApi()) {
            return Response::error('Your session has expired. Please login again.', 401, [], 'UNAUTHENTICATED');
        }
        return Response::redirect(base_url('login'));
    }
}
