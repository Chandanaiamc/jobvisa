<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Auth\Services;

use JobVisa\App\Domain\Api\Http\ApiException;
use JobVisa\App\Domain\Auth\Repositories\MfaFactorRepository;
use JobVisa\App\Domain\Auth\Support\AuthTokenLifecycleVersion;
use JobVisa\App\Domain\Security\Services\SecurityAuditLogger;

/**
 * MFA-ready architecture: factor registry without mandatory challenge yet.
 */
final class MfaFactorService
{
    public function __construct(
        private readonly MfaFactorRepository $factors,
        private readonly SecurityAuditLogger $audit,
    ) {
    }

    public function ensureSchemaReady(): bool
    {
        return $this->factors->ensureSchemaReady();
    }

    /**
     * @return array<string, mixed>
     */
    public function statusForUser(int $userId): array
    {
        $list = $this->factors->listForUser($userId);

        return [
            'mfa_ready' => true,
            'mfa_enforced' => (bool) config('auth_lifecycle.mfa_enforced', false),
            'challenge_required' => false,
            'has_enabled_factor' => $this->factors->hasEnabledFactor($userId),
            'factors' => $list,
            'lifecycle_version' => AuthTokenLifecycleVersion::CURRENT,
            'note' => 'MFA challenge verification is scaffolded; enable auth_lifecycle.mfa_enforced when ready.',
        ];
    }

    /**
     * Register an MFA factor placeholder (TOTP/WebAuthn secret handling deferred).
     *
     * @return array{id: int, type: string, label: string}
     */
    public function registerPlaceholder(int $userId, string $type = 'totp', string $label = 'Authenticator'): array
    {
        $type = strtolower(trim($type));
        if (!in_array($type, ['totp', 'webauthn', 'email_otp'], true)) {
            throw ApiException::validation('Unsupported MFA type.', ['type' => ['Allowed: totp, webauthn, email_otp']]);
        }
        $id = $this->factors->registerPlaceholder($userId, $type, $label);
        $this->audit->log('auth.mfa_factor_registered', $userId, 'auth_mfa_factor', $id, [], [
            'type' => $type,
        ]);

        return ['id' => $id, 'type' => $type, 'label' => $label];
    }

    public function revoke(int $userId, int $factorId): bool
    {
        $ok = $this->factors->revoke($factorId, $userId);
        if ($ok) {
            $this->audit->log('auth.mfa_factor_revoked', $userId, 'auth_mfa_factor', $factorId);
        }

        return $ok;
    }
}
