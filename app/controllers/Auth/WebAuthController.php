<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Controller;
use JobVisa\App\Auth\AuthManager;
use JobVisa\App\Auth\DashboardRedirector;
use JobVisa\App\Auth\RegistrationService;
use JobVisa\App\Auth\RememberMeCookie;
use JobVisa\App\Domain\Frontend\Auth\FrontendApiAuthService;
use JobVisa\App\Security\Csrf;
use JobVisa\App\Security\SessionManager;
use JobVisa\App\Security\Validator;

/**
 * HTML registration, login, and logout (Sprint 2A).
 *
 * Reuses existing Auth services — does not replace them.
 */
final class WebAuthController extends Controller
{
    private AuthManager $auth;
    private RegistrationService $registration;
    private DashboardRedirector $redirector;
    private RememberMeCookie $rememberCookie;
    private FrontendApiAuthService $frontendApiAuth;

    public function __construct()
    {
        parent::__construct();
        $this->auth = container(AuthManager::class);
        $this->registration = container(RegistrationService::class);
        $this->redirector = container(DashboardRedirector::class);
        $this->rememberCookie = container(RememberMeCookie::class);
        $this->frontendApiAuth = container(FrontendApiAuthService::class);
    }

    /**
     * GET /register
     */
    public function showRegister(): void
    {
        $this->render('auth/register', [
            'title' => 'Create Account',
            'errors' => [],
            'old' => [
                'first_name' => '',
                'last_name' => '',
                'email' => '',
                'phone' => '',
                'role' => 'seeker',
                'terms' => '',
            ],
        ]);
    }

    /**
     * POST /register
     */
    public function register(): void
    {
        $input = $_POST;
        unset($input['password'], $input['password_confirmation'], $input['_token']);

        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));
        $fullName = trim($firstName . ' ' . $lastName);

        $validator = Validator::make($_POST)
            ->required('first_name', 'First name is required.')
            ->max('first_name', 80)
            ->required('last_name', 'Last name is required.')
            ->max('last_name', 80)
            ->required('email')
            ->email('email')
            ->max('email', 191)
            ->max('phone', 32)
            ->required('role')
            ->in('role', ['seeker', 'employer'], 'Please select a valid account type.')
            ->required('password')
            ->min('password', 8)
            ->confirmed('password')
            ->required('terms', 'You must accept the Terms and Conditions.');

        if ($validator->fails()) {
            $this->render('auth/register', [
                'title' => 'Create Account',
                'errors' => $validator->errors(),
                'old' => $this->safeOld($input + ['role' => $_POST['role'] ?? 'seeker']),
            ]);

            return;
        }

        $result = $this->registration->register([
            'full_name' => $fullName,
            'email' => $_POST['email'] ?? '',
            'password' => $_POST['password'] ?? '',
            'password_confirmation' => $_POST['password_confirmation'] ?? '',
            'role' => $_POST['role'] ?? 'seeker',
            'phone' => $_POST['phone'] ?? null,
        ], true);

        if (!($result['success'] ?? false)) {
            $this->render('auth/register', [
                'title' => 'Create Account',
                'errors' => $result['errors'] ?? ['form' => [$result['message'] ?? 'Registration failed.']],
                'old' => $this->safeOld([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => (string) ($_POST['email'] ?? ''),
                    'phone' => (string) ($_POST['phone'] ?? ''),
                    'role' => (string) ($_POST['role'] ?? 'seeker'),
                    'terms' => isset($_POST['terms']) ? '1' : '',
                ]),
            ]);

            return;
        }

        Csrf::rotate();
        SessionManager::flash('success', 'Welcome to JobVisa.lk! Please verify your email to continue.');
        redirect(app_url('/email/verify'));
    }

    /**
     * GET /login
     */
    public function showLogin(): void
    {
        $this->render('auth/login', [
            'title' => 'Sign In',
            'errors' => [],
            'old' => [
                'email' => '',
                'remember' => '',
            ],
        ]);
    }

    /**
     * POST /login
     */
    public function login(): void
    {
        $validator = Validator::make($_POST)
            ->required('email')
            ->email('email')
            ->required('password');

        if ($validator->fails()) {
            $this->render('auth/login', [
                'title' => 'Sign In',
                'errors' => $validator->errors(),
                'old' => [
                    'email' => (string) ($_POST['email'] ?? ''),
                    'remember' => isset($_POST['remember']) ? '1' : '',
                ],
            ]);

            return;
        }

        $remember = isset($_POST['remember']);
        $result = $this->auth->attempt(
            (string) ($_POST['email'] ?? ''),
            (string) ($_POST['password'] ?? ''),
            $remember
        );

        if (!($result['success'] ?? false)) {
            $message = !empty($result['throttled'])
                ? (string) ($result['message'] ?? 'Too many login attempts. Please try again later.')
                : 'Invalid email or password.';

            $this->render('auth/login', [
                'title' => 'Sign In',
                'errors' => ['form' => [$message]],
                'old' => [
                    'email' => (string) ($_POST['email'] ?? ''),
                    'remember' => $remember ? '1' : '',
                ],
            ]);

            return;
        }

        if (is_array($result['remember'] ?? null) && isset($result['remember']['plain'])) {
            $days = (int) config('auth.remember.cookie_days', 30);
            $this->rememberCookie->queue((int) $result['user_id'], (string) $result['remember']['plain'], $days);
        }

        Csrf::rotate();
        SessionManager::flash('success', 'Signed in successfully.');

        $user = $this->auth->user();
        if (is_array($user) && (int) ($user['id'] ?? 0) > 0) {
            $this->frontendApiAuth->bridgeSessionToApi((int) $user['id'], [
                'name' => 'Web Session',
                'fingerprint' => 'web-form-' . substr(hash('sha256', (string) ($_SERVER['HTTP_USER_AGENT'] ?? 'ua')), 0, 16),
                'platform' => 'web',
            ]);
        }

        if (is_array($user) && empty($user['email_verified_at'])) {
            redirect(app_url('/email/verify'));
        }

        $redirect = $this->redirector->forUser($user);
        redirect(app_url($redirect['path']));
    }

    /**
     * POST /logout
     */
    public function logout(): void
    {
        $this->frontendApiAuth->clearApiSessionOnWebLogout();
        $this->auth->logout();
        $this->rememberCookie->forget();
        Csrf::rotate();
        SessionManager::flash('success', 'You have been signed out.');
        redirect(app_url('/login'));
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{first_name: string, last_name: string, email: string, phone: string, role: string, terms: string}
     */
    private function safeOld(array $input): array
    {
        return [
            'first_name' => (string) ($input['first_name'] ?? ''),
            'last_name' => (string) ($input['last_name'] ?? ''),
            'email' => (string) ($input['email'] ?? ''),
            'phone' => (string) ($input['phone'] ?? ''),
            'role' => (string) ($input['role'] ?? 'seeker'),
            'terms' => (string) ($input['terms'] ?? ''),
        ];
    }
}
