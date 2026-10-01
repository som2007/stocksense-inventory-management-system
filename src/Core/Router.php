<?php
declare(strict_types=1);

namespace Somen\InventoryManagementSystem\Core;

use Somen\InventoryManagementSystem\Middleware\AuthMiddleware;
use Somen\InventoryManagementSystem\Middleware\CsrfMiddleware;
use Somen\InventoryManagementSystem\Middleware\GuestMiddleware;
use Somen\InventoryManagementSystem\Middleware\RoleMiddleware;

final class Router
{
    /** @var array<int, array{method:string, regex:string, handler:array, middleware:array}> */
    private array $routes = [];

    public function add(string $method, string $path, array $handler, array $middleware = []): void
    {
        $regex = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $path);
        $this->routes[] = [
            'method'     => strtoupper($method),
            'regex'      => '#^' . $regex . '$#',
            'handler'    => $handler,
            'middleware' => $middleware,
        ];
    }

    public function get(string $path, array $handler, array $mw = []): void
    {
        $this->add('GET', $path, $handler, $mw);
    }

    public function post(string $path, array $handler, array $mw = []): void
    {
        $this->add('POST', $path, $handler, $mw);
    }

    public function put(string $path, array $handler, array $mw = []): void
    {
        $this->add('PUT', $path, $handler, $mw);
    }

    public function delete(string $path, array $handler, array $mw = []): void
    {
        $this->add('DELETE', $path, $handler, $mw);
    }

    public function dispatch(Request $request): Response
    {
        $pathMatched = false;
        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $request->path, $m)) {
                continue;
            }
            $pathMatched = true;
            if ($route['method'] !== $request->method) {
                continue;
            }
            $request->params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);

            foreach ($route['middleware'] as $mwSpec) {
                $short = $this->resolveMiddleware($mwSpec)->handle($request);
                if ($short instanceof Response) {
                    return $short;
                }
            }
            [$class, $method] = $route['handler'];
            $result = (new $class())->$method($request);
            return $result instanceof Response ? $result : Response::html((string)$result);
        }

        if ($pathMatched) {
            return $request->isApi()
                ? Response::error('Method not allowed.', 405, [], 'METHOD_NOT_ALLOWED')
                : Response::html('Method not allowed', 405);
        }
        if ($request->isApi()) {
            return Response::error('Endpoint not found.', 404, [], 'NOT_FOUND');
        }
        return Response::html(View::render('errors/404', [], 'layouts/auth'), 404);
    }

    private function resolveMiddleware(string $spec): object
    {
        [$name, $arg] = array_pad(explode(':', $spec, 2), 2, null);
        return match ($name) {
            'auth'  => new AuthMiddleware(),
            'guest' => new GuestMiddleware(),
            'csrf'  => new CsrfMiddleware(),
            'role'  => new RoleMiddleware(explode(',', (string)$arg)),
            default => throw new \InvalidArgumentException("Unknown middleware: {$name}"),
        };
    }
}
