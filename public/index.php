<?php
declare(strict_types=1);

$root = dirname(__DIR__);

require $root . '/vendor/autoload.php';
require $root . '/src/Helpers/functions.php';

Somen\InventoryManagementSystem\Core\App::run($root);
