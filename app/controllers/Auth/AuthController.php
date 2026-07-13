<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Controller;
use JobVisa\App\Auth\AuthManager;
use JobVisa\App\Auth\DashboardRedirector;
use JobVisa\App\Auth\RegistrationService;
use JobVisa\App\Auth\RememberMeCookie;
use JobVisa\App\Security\Csrf;
use JobVisa\App\Security\Validator;

/**
 * Authentication HTTP endpoints (JSON — no frontend views).
 */
final class AuthController extends Controller
{
    private AuthManager $auth;
    private RegistrationService $registration;
    private DashboardRedirector $redirector;
    private RememberMeCookie $rememberCookie;

    public function __construct()
    {
        parent::__construct();
        $this->auth = container(AuthManager::class);
        $this->registration = container(RegistrationService::class);
        $this->redirector = container(DashboardRedirector::class);
        $this->rememberCookie = container(RememberMeCookie::class);
    }

    /**
     * POST /auth/register
     */
    public function register(): void
    {
        $result = $this->registration->register($this->input());

        if (!($result['success'] ?? false)) {
            $this->json($result, 422);

            return;
        }

        Csrf::rotate();
        $this->json($result, 201);
    }

    /**
     * POST /auth/login
     */
    public function login(): void
    {
        $input = $this->input();
        $validator = Validator::make($input)
            ->required('email')
            ->email('email')
            ->required('password');

        if ($validator->fails()) {
            $this->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);

            return;
        }

        $remember = filter_var($input['remember'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $result = $this->auth->attempt((string) $input['email'], (string) $input['password'], $remember);

        if (!($result['success'] ?? false)) {
            $status = !empty($result['throttled']) ? 429 : 401;
            $this->json([
                'success' => false,
                'message' => $result['message'] ?? 'Invalid credentials.',
                'throttled' => (bool) ($result['throttled'] ?? false),
            ], $status);

            return;
        }

        if (is_array($result['remember'] ?? null) && isset($result['remember']['plain'])) {
            $days = (int) config('auth.remember.cookie_days', 30);
            $this->rememberCookie->queue((int) $result['user_id'], (string) $result['remember']['plain'], $days);
        }

        $user = $this->auth->user();
        $redirect = $this->redirector->forUser($user);

        Csrf::rotate();

        $this->json([
            'success' => true,
            'message' => 'Login successful.',
            'user_id' => $result['user_id'],
            'redirect' => $redirect,
            'csrf_token' => Csrf::token(),
        ]);
    }

    /**
     * POST /auth/logout
     */
    public function logout(): void
    {
        $this->auth->logout();
        $this->rememberCookie->forget();
        Csrf::rotate();

        $this->json([
            'success' => true,
            'message' => 'Logged out.',
            'csrf_token' => Csrf::token(),
        ]);
    }

    /**
     * GET /auth/me
     */
    public function me(): void
    {
        $user = $this->auth->user();

        if ($user === null) {
            $this->json(['success' => false, 'message' => 'Unauthenticated.'], 401);

            return;
        }

        $safe = [
            'id' => (int) $user['id'],
            'email' => $user['email'] ?? null,
            'full_name' => $user['full_name'] ?? null,
            'role' => $user['role'] ?? null,
            'role_id' => isset($user['role_id']) ? (int) $user['role_id'] : null,
            'status' => $user['status'] ?? null,
            'email_verified_at' => $user['email_verified_at'] ?? null,
        ];

        $this->json([
            'success' => true,
            'user' => $safe,
            'redirect' => $this->redirector->forUser($user),
            'csrf_token' => Csrf::token(),
        ]);
    }

    /**
     * GET /auth/redirect — role-based dashboard target for the current session.
     */
    public function redirectTarget(): void
    {
        $user = $this->auth->user();

        if ($user === null) {
            $this->json(['success' => false, 'message' => 'Unauthenticated.'], 401);

            return;
        }

        $this->json([
            'success' => true,
            'redirect' => $this->redirector->forUser($user),
        ]);
    }

    /**
     * GET /auth/csrf — issue a CSRF token for API clients (no UI).
     */
    public function csrf(): void
    {
        $this->json([
            'success' => true,
            'csrf_token' => Csrf::token(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function input(): array
    {
        $contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '');

        if (str_contains(strtolower($contentType), 'application/json')) {
            $raw = file_get_contents('php://input');
            $decoded = is_string($raw) ? json_decode($raw, true) : null;

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return $_POST;
    }
}
