<?php

namespace App\Core;

/**
 * Renders a view file inside the shared layout. View files themselves
 * contain the Spanish-language markup shown to end users.
 */
class View
{
    public static function render(string $view, array $data = [], string $module = 'projects'): void
    {
        extract($data, EXTR_SKIP);

        $viewFile = VIEWS_PATH . '/' . $view . '.php';

        if (!is_file($viewFile)) {
            throw new \RuntimeException("View not found: {$viewFile}");
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        require VIEWS_PATH . '/layout/main.php';
    }

    public static function renderPartial(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require VIEWS_PATH . '/' . $view . '.php';
    }

    public static function json(array $data): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
