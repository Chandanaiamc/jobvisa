<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Controller;
use JobVisa\App\Auth\EmailVerificationService;
use JobVisa\App\Auth\PasswordResetService;
use JobVisa\App\Security\Csrf;
use JobVisa\App\Security\Validator;

/**
 * Password reset and email verification endpoints (JSON).
 */
final class PasswordController extends Controller
{
    private PasswordResetService $passwordReset;
    private EmailVerificationService $emailVerification;

    public function __construct()
    {
        parent::__construct();
        $this->passwordReset = container(PasswordResetService::class);
        $this->emailVerification = container(EmailVerificationService::class);
    }

    /**
     * POST /auth/password/forgot
     */
    public function forgot(): void
    {
        $input = $this->input();
        $result = $this->passwordReset->request((string) ($input['email'] ?? ''));

        if (!($result['success'] ?? false)) {
            $this->json($result, 422);

            return;
        }

        Csrf::rotate();
        $this->json($result);
    }

    /**
     * POST /auth/password/reset
     */
    public function reset(): void
    {
        $result = $this->passwordReset->reset($this->input());

        if (!($result['success'] ?? false)) {
            $status = isset($result['errors']) ? 422 : 400;
            $this->json($result, $status);

            return;
        }

        Csrf::rotate();
        $this->json($result);
    }

    /**
     * POST /auth/email/verify
     */
    public function verifyEmail(): void
    {
        $input = $this->input();
        $validator = Validator::make($input)->required('token');

        if ($validator->fails()) {
            $this->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);

            return;
        }

        $result = $this->emailVerification->verify((string) $input['token']);
        $status = ($result['success'] ?? false) ? 200 : 400;

        if ($result['success'] ?? false) {
            Csrf::rotate();
        }

        $this->json($result, $status);
    }

    /**
     * POST /auth/email/resend
     */
    public function resendVerification(): void
    {
        $input = $this->input();
        $validator = Validator::make($input)->required('email')->email('email');

        if ($validator->fails()) {
            $this->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);

            return;
        }

        $result = $this->emailVerification->resend((string) $input['email']);
        Csrf::rotate();
        $this->json($result);
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
