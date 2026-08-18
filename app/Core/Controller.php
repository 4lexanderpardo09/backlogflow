<?php

namespace App\Core;

abstract class Controller
{
    protected function render(string $view, array $data = []): void
    {
        View::render($view, $data);
    }

    protected function json(array $data): void
    {
        View::json($data);
    }

    protected function redirect(string $route): void
    {
        header('Location: /index.php?r=' . ltrim($route, '/'));
        exit;
    }

    protected function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    /**
     * Queues a one-time banner shown on the next page (after a redirect),
     * since without this a create/edit/delete silently returns to the list
     * with no confirmation that anything happened.
     */
    protected function flash(string $type, string $message): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }
}
