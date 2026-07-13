<?php

declare(strict_types=1);

namespace App\Core;

/**
 * View renderer.
 *
 * Loads PHP templates from app/views and extracts data into local variables.
 */
final class View
{
    private string $viewsPath;

    public function __construct(?string $viewsPath = null)
    {
        $this->viewsPath = $viewsPath ?? base_path('app/views');
    }

    /**
     * Render a view template and return the HTML string.
     *
     * @param  array<string, mixed>  $data
     */
    public function render(string $view, array $data = []): string
    {
        $path = $this->resolvePath($view);

        if (!is_file($path)) {
            throw new \RuntimeException("View [{$view}] not found at [{$path}].");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $path;

        return (string) ob_get_clean();
    }

    /**
     * Render a view and send it to the browser.
     *
     * @param  array<string, mixed>  $data
     */
    public function display(string $view, array $data = []): void
    {
        echo $this->render($view, $data);
    }

    private function resolvePath(string $view): string
    {
        $view = str_replace(['.', '\\'], '/', $view);
        $view = trim($view, '/');

        return $this->viewsPath . '/' . $view . '.php';
    }
}
