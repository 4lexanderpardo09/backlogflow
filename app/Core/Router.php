<?php

namespace App\Core;

/**
 * Minimal front-controller router.
 *
 * URLs look like: /index.php?r=projects/dashboard/index or, with mod_rewrite,
 * /projects/dashboard/index. Route format: "module/controller/action".
 * Missing segments default to "index".
 */
class Router
{
    private const DEFAULT_MODULE = 'projects';
    private const DEFAULT_CONTROLLER = 'dashboard';
    private const DEFAULT_ACTION = 'index';

    /** @var array<string,string> module key => Controllers namespace segment */
    private array $moduleNamespaces = [
        'projects' => 'Projects',
        'sla' => 'Sla',
    ];

    public function dispatch(string $route, array $params = []): void
    {
        $segments = array_values(array_filter(explode('/', trim($route, '/'))));

        $module = $segments[0] ?? self::DEFAULT_MODULE;
        $controllerName = $segments[1] ?? self::DEFAULT_CONTROLLER;
        $action = $segments[2] ?? self::DEFAULT_ACTION;
        $id = $segments[3] ?? ($params['id'] ?? null);

        if (!isset($this->moduleNamespaces[$module])) {
            $this->notFound("Unknown module: {$module}");
            return;
        }

        $controllerClass = sprintf(
            'App\\Controllers\\%s\\%sController',
            $this->moduleNamespaces[$module],
            $this->studly($controllerName)
        );

        if (!class_exists($controllerClass)) {
            $this->notFound("Unknown controller: {$controllerClass}");
            return;
        }

        $controller = new $controllerClass();
        $actionMethod = $action . 'Action';

        if (!method_exists($controller, $actionMethod)) {
            $this->notFound("Unknown action: {$controllerClass}::{$actionMethod}");
            return;
        }

        $controller->$actionMethod($id, $params);
    }

    private function studly(string $value): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $value)));
    }

    private function notFound(string $message): void
    {
        http_response_code(404);
        echo '<h1>404 - Página no encontrada</h1>';
        if (ini_get('display_errors')) {
            echo '<p>' . htmlspecialchars($message) . '</p>';
        }
    }
}
