<?php

declare(strict_types=1);

namespace App\Controllers\Employer;

use App\Core\Controller;
use JobVisa\App\Auth\AuthManager;
use JobVisa\App\Security\Csrf;
use JobVisa\App\Security\SessionManager;

/**
 * Shared helpers for authenticated employer dashboard controllers.
 */
abstract class EmployerController extends Controller
{
    protected AuthManager $auth;

    public function __construct()
    {
        parent::__construct();
        $this->auth = container(AuthManager::class);
    }

    /**
     * @return array<string, mixed>
     */
    protected function actor(): array
    {
        $user = $this->auth->user();

        if ($user === null) {
            SessionManager::flash('error', 'Please sign in to continue.');
            redirect(app_url('/login'));
        }

        return $user;
    }

    protected function userId(): int
    {
        return (int) ($this->actor()['id'] ?? 0);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function dashboard(string $view, array $data = []): void
    {
        $this->render('employer/layout', array_merge([
            'title' => $data['title'] ?? 'Employer',
            'activeNav' => $data['activeNav'] ?? 'overview',
            'contentView' => $view,
            'actor' => $this->actor(),
        ], $data));
    }

    protected function flashRedirect(string $path, string $type, string $message): never
    {
        Csrf::rotate();
        SessionManager::flash($type, $message);
        redirect(app_url($path));
    }
}
