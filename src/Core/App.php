<?php
declare(strict_types=1);

namespace Somen\InventoryManagementSystem\Core;

final class App
{
    public static function run(string $root): void
    {
        Env::load($root . '/.env');
        date_default_timezone_set((string)Env::get('APP_TIMEZONE', 'Asia/Kolkata'));

        $debug = Env::bool('APP_DEBUG', false);
        ini_set('display_errors', $debug ? '1' : '0');
        error_reporting(E_ALL);

        $request = Request::capture();

        set_exception_handler(function (\Throwable $e) use ($request, $debug): void {
            Logger::error($e::class . ': ' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
            $msg = $debug ? $e->getMessage() : 'Something went wrong on our side. Please try again.';
            if ($request->isApi()) {
                Response::error($msg, 500, [], 'SERVER_ERROR')->send();
            } else {
                http_response_code(500);
                echo $debug ? '<pre>' . e((string)$e) . '</pre>' : e($msg);
            }
        });

        Session::start();

        $router = new Router();
        require $root . '/src/routes.php';
        $router->dispatch($request)->send();
    }
}
