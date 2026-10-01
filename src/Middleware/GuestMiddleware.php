<?php
declare(strict_types=1);

namespace Somen\InventoryManagementSystem\Middleware;

use Somen\InventoryManagementSystem\Core\Request;
use Somen\InventoryManagementSystem\Core\Response;

/** Logged-in users are bounced to their dashboard (login/register pages are guests-only). */
final class GuestMiddleware
{
    public function handle(Request $request): ?Response
    {
        return auth_user() !== null ? Response::redirect(base_url('dashboard')) : null;
    }
}
