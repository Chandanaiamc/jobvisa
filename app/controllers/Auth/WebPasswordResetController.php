<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Controller;
use JobVisa\App\Auth\AuthManager;
use JobVisa\App\Auth\PasswordResetService;
use JobVisa\App\Auth\RememberMeCookie;
use JobVisa\App\Security\Csrf;
use JobVisa\App\Security\SessionManager;
use JobVisa\App\Security\Validator;

/**
 * HTML forgot / reset password pages.
 */
final class WebPasswordResetController extends Controller
{
    private PasswordResetService $resets;
    private AuthManager $auth;
    private RememberMeCookie $rememberCookie;

    public function __construct()
    {
        parent::__construct();
        $this->resets = container(PasswordResetService::class);
        $this->auth = container(AuthManager::class);
        $this->rememberCookie = container(RememberMeCookie::class);
    }

    /**
     * GET /forgot-password
     */
    public function showForgot(): void
    {
        $this->render('auth/forgot-password', [
            'title' => 'Forgot Password',
            'errors' => [],
            'old' => ['email' => ''],
        ]);
    }

    /**
     * POST /forgot-password
     */
    public function sendResetLink(): void
    {
        $validator = Validator::make($_POST)->required('email')->email('email');

        if ($validator->fails()) {
            $this->render('auth/forgot-password', [
                'title' => 'Forgot Password',
                'errors' => $validator->errors(),
                'old' => ['email' => (string) ($_POST['email'] ?? '')],
            ]);

            return;
        }

        $result = $this->resets->request((string) $_POST['email']);
        Csrf::rotate();

        if (!empty($result['throttled'])) {
            SessionManager::flash('error', $result['message']);
        } else {
            SessionManager::flash('success', $result['message']);
        }

        redirect(app_url('/forgot-password'));
    }

    /**
     * GET /reset-password/{token}
     */
    public function showReset(string $token): void
    {
        $row = $this->resets->findActiveToken($token);

        if ($row === null) {
            SessionManager::flash('error', 'This password reset link is invalid or has expired.');
            redirect(app_url('/forgot-password'));
        }

        $this->render('auth/reset-password', [
            'title' => 'Reset Password',
            'errors' => [],
            'token' => $token,
            'email' => (string) $row['email'],
        ]);
    }

    /**
     * POST /reset-password
     */
    public function reset(): void
    {
        $token = (string) ($_POST['token'] ?? '');
        $email = (string) ($_POST['email'] ?? '');

        $result = $this->resets->reset([
            'token' => $token,
            'email' => $email,
            'password' => $_POST['password'] ?? '',
            'password_confirmation' => $_POST['password_confirmation'] ?? '',
        ]);

        if (!($result['success'] ?? false)) {
            $this->render('auth/reset-password', [
                'title' => 'Reset Password',
                'errors' => $result['errors'] ?? ['form' => [$result['message'] ?? 'Unable to reset password.']],
                'token' => $token,
                'email' => $email,
            ]);

            return;
        }

        if ($this->auth->check()) {
            $this->auth->logout();
            $this->rememberCookie->forget();
        } else {
            $this->rememberCookie->forget();
            SessionManager::regenerate(true);
        }

        Csrf::rotate();
        SessionManager::flash('success', 'Your password has been reset. Please sign in.');
        redirect(app_url('/login'));
    }
}
