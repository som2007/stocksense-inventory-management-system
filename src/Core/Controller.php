<?php
declare(strict_types=1);

namespace Somen\InventoryManagementSystem\Core;

abstract class Controller
{
    protected function view(string $view, array $data = [], ?string $layout = 'layouts/app', int $status = 200): Response
    {
        return Response::html(View::render($view, $data, $layout), $status);
    }

    protected function validationError(array $errors, string $message = 'Please correct the highlighted fields.'): Response
    {
        return Response::error($message, 422, $errors, 'VALIDATION_FAILED');
    }
}
