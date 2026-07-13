<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Base controller for all HTTP controllers.
 *
 * Provides shared helpers for rendering views and issuing redirects.
 * Controllers should stay thin and defer business rules to services later.
 */
abstract class Controller
{
    protected View $view;

    public function __construct(?View $view = null)
    {
        $this->view = $view ?? new View();
    }

    /**
     * Render a view template.
     *
     * @param  array<string, mixed>  $data
     */
    protected function render(string $view, array $data = []): void
    {
        $this->view->display($view, $data);
    }

    /**
     * Render a view and return HTML without echoing.
     *
     * @param  array<string, mixed>  $data
     */
    protected function view(string $view, array $data = []): string
    {
        return $this->view->render($view, $data);
    }

    /**
     * Redirect to another URL and stop execution.
     */
    protected function redirect(string $url, int $status = 302): never
    {
        redirect($url, $status);
    }

    /**
     * Send a JSON response.
     *
     * @param  array<string, mixed>  $data
     */
    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
