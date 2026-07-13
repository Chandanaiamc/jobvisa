<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Controller;
use JobVisa\App\Auth\AuthManager;
use JobVisa\App\Auth\DashboardRedirector;
use JobVisa\App\Auth\EmailVerificationService;
use JobVisa\App\Security\Csrf;
use JobVisa\App\Security\SessionManager;
use JobVisa\App\Security\Validator;

/**
 * HTML email verification notice, link confirmation, and resend.
 */
final class WebEmailController extends Controller
{
    private AuthManager $auth;
    private EmailVerificationService $verification;
    private DashboardRedirector $redirector;

    public function __construct()
    {
        parent::__construct();
        $this->auth = container(AuthManager::class);
        $this->verification = container(EmailVerificationService::class);
        $this->redirector = container(DashboardRedirector::class);
    }

    /**
     * GET /email/verify — notice / resend page.
     */
    public function notice(): void
    {
        $user = $this->auth->user();

        if ($user !== null && !empty($user['email_verified_at'])) {
            $redirect = $this->redirector->forUser($user);
            redirect(app_url($redirect['path']));
        }

        $this->render('auth/verify-email', [
            'title' => 'Verify Email',
            'errors' => [],
            'user' => $user,
            'email' => is_array($user) ? (string) ($user['email'] ?? '') : '',
        ]);
    }

    /**
     * GET /email/verify/{token}
     */
    public function verify(string $token): void
    {
        $result = $this->verification->verify($token);

        if (!($result['success'] ?? false)) {
            SessionManager::flash('error', $result['message'] ?? 'Unable to verify email.');
            redirect(app_url('/email/verify'));
        }

        SessionManager::flash('success', 'Your email has been verified.');

        if ($this->auth->check()) {
            $redirect = $this->redirector->forUser($this->auth->user());
            redirect(app_url($redirect['path']));
        }

        redirect(app_url('/login'));
    }

    /**
     * POST /email/verification-notification
     */
    public function resend(): void
    {
        $user = $this->auth->user();

        if ($user !== null) {
            $result = $this->verification->resendForUser($user);
        } else {
            $validator = Validator::make($_POST)->required('email')->email('email');

            if ($validator->fails()) {
                $this->render('auth/verify-email', [
                    'title' => 'Verify Email',
                    'errors' => $validator->errors(),
                    'user' => null,
                    'email' => (string) ($_POST['email'] ?? ''),
                ]);

                return;
            }

            $result = $this->verification->resend((string) $_POST['email']);
        }

        Csrf::rotate();

        if (!empty($result['throttled'])) {
            SessionManager::flash('error', $result['message']);
        } else {
            SessionManager::flash('success', $result['message']);
        }

        redirect(app_url('/email/verify'));
    }
}
