<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Controller;
use JobVisa\App\Domain\Api\Http\ApiException;
use JobVisa\App\Domain\Frontend\Auth\FrontendApiAuthService;
use JobVisa\App\Security\Csrf;
use JobVisa\App\Security\SecurityHelper;
use JobVisa\App\Security\Validator;
use Throwable;

/**
 * Same-origin JSON bridge to Auth Token Lifecycle (httpOnly cookies; no tokens in body).
 */
final class FrontendApiAuthController extends Controller
{
    private FrontendApiAuthService $apiAuth;

    public function __construct()
    {
        parent::__construct();
        $this->apiAuth = container(FrontendApiAuthService::class);
    }

    /**
     * POST /auth/api/login
     */
    public function login(): void
    {
        if (!(bool) config('frontend.api_auth.enabled', true)) {
            $this->fail('auth_lifecycle_disabled', 'Frontend API auth is disabled.', 503);

            return;
        }

        $input = $this->input();
        $validator = Validator::make($input)
            ->required('email')
            ->email('email')
            ->required('password');

        if ($validator->fails()) {
            $this->json([
                'success' => false,
                'error' => [
                    'code' => 'validation_error',
                    'message' => 'Validation failed.',
                    'details' => $validator->errors(),
                ],
                'csrf_token' => Csrf::token(),
            ], 422);

            return;
        }

        $remember = filter_var($input['remember'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $device = [
            'name' => (string) ($input['device_name'] ?? 'Browser'),
            'fingerprint' => (string) ($input['device_fingerprint'] ?? $this->browserFingerprint()),
            'platform' => (string) ($input['platform'] ?? 'web'),
        ];

        try {
            $result = $this->apiAuth->login(
                (string) $input['email'],
                (string) $input['password'],
                $device,
                $remember
            );
        } catch (ApiException $e) {
            Csrf::rotate();
            $this->json([
                'success' => false,
                'error' => [
                    'code' => $e->errorCode(),
                    'message' => $e->getMessage(),
                    'details' => $e->details(),
                ],
                'csrf_token' => Csrf::token(),
            ], $e->status());

            return;
        } catch (Throwable $e) {
            Csrf::rotate();
            $this->json([
                'success' => false,
                'error' => [
                    'code' => 'server_error',
                    'message' => 'Login failed.',
                    'details' => [],
                ],
                'csrf_token' => Csrf::token(),
            ], 500);

            return;
        }

        Csrf::rotate();
        $this->json([
            'success' => true,
            'data' => $result,
            'csrf_token' => Csrf::token(),
        ]);
    }

    /**
     * POST /auth/api/refresh
     */
    public function refresh(): void
    {
        try {
            $data = $this->apiAuth->refresh();
        } catch (ApiException $e) {
            Csrf::rotate();
            $this->json([
                'success' => false,
                'error' => [
                    'code' => $e->errorCode(),
                    'message' => $e->getMessage(),
                    'details' => $e->details(),
                ],
                'csrf_token' => Csrf::token(),
            ], $e->status());

            return;
        }

        Csrf::rotate();
        $this->json([
            'success' => true,
            'data' => $data,
            'csrf_token' => Csrf::token(),
        ]);
    }

    /**
     * GET /auth/api/me
     */
    public function me(): void
    {
        try {
            $data = $this->apiAuth->me();
        } catch (ApiException $e) {
            $this->json([
                'success' => false,
                'error' => [
                    'code' => $e->errorCode(),
                    'message' => $e->getMessage(),
                    'details' => $e->details(),
                ],
            ], $e->status());

            return;
        }

        $this->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * POST /auth/api/logout
     */
    public function logout(): void
    {
        $result = $this->apiAuth->logout(true);
        Csrf::rotate();
        $this->json([
            'success' => true,
            'data' => $result,
            'csrf_token' => Csrf::token(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function input(): array
    {
        $raw = file_get_contents('php://input');
        if (is_string($raw) && trim($raw) !== '') {
            $json = json_decode($raw, true);
            if (is_array($json)) {
                return $json;
            }
        }

        return is_array($_POST) ? $_POST : [];
    }

    private function browserFingerprint(): string
    {
        $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
        $ip = SecurityHelper::clientIp();

        return 'web-' . substr(hash('sha256', $ua . '|' . $ip), 0, 24);
    }

    private function fail(string $code, string $message, int $status): void
    {
        $this->json([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => [],
            ],
            'csrf_token' => Csrf::token(),
        ], $status);
    }
}
