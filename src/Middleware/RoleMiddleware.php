<?php
declare(strict_types=1);

namespace Somen\InventoryManagementSystem\Middleware;

use Somen\InventoryManagementSystem\Core\Request;
use Somen\InventoryManagementSystem\Core\Response;

final class RoleMiddleware
{
    /** @param string[] $roles */
    public function __construct(private array $roles)
    {
    }

    public function handle(Request $request): ?Response
    {
        $user = auth_user();
        if ($user === null) {
            return (new AuthMiddleware())->handle($request);
        }
        if (in_array($user['role'], $this->roles, true)) {
            return null;
        }
        if ($request->isApi()) {
            return Response::error('You are not authorised to perform this action.', 403, [], 'FORBIDDEN');
        }
        return Response::html(\Somen\InventoryManagementSystem\Core\View::render('errors/403', [], 'layouts/app'), 403);
    }
}
