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
        // Returning to a list page restores the filters it had when the user
        // left it, instead of resetting them on every save.
        header('Location: ' . Router::listUrl($route));
        exit;
    }

    protected function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    /** A Y-m-d date from the request (date filters), or null when absent or malformed. */
    protected function dateInput(string $key): ?string
    {
        $value = (string) $this->input($key, '');

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 ? $value : null;
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
