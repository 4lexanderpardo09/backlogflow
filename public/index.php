<?php

session_start();

require_once __DIR__ . '/../config/config.php';

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = APP_PATH . '/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($path)) {
        require $path;
    }
});

use App\Core\Router;

$route = $_GET['r'] ?? '';

if ($route === '' && isset($_SERVER['PATH_INFO'])) {
    $route = $_SERVER['PATH_INFO'];
}

(new Router())->dispatch($route, $_GET);
