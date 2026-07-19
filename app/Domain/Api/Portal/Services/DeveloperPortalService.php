<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\Api\Portal\Services;

use JobVisa\App\Domain\Api\Portal\Support\DeveloperPortalVersion;
use JobVisa\App\Domain\Api\Support\ApiVersion;
use JobVisa\App\Domain\Api\Webhooks\WebhookDispatcher;

/**
 * Aggregates portal navigation, OpenAPI metadata, and readiness.
 */
final class DeveloperPortalService
{
    /**
     * @return list<array{slug: string, label: string, path: string}>
     */
    public function nav(): array
    {
        $base = rtrim((string) config('developer_portal.base_path', '/developers'), '/');

        return [
            ['slug' => 'home', 'label' => 'Overview', 'path' => $base],
            ['slug' => 'getting-started', 'label' => 'Getting started', 'path' => $base . '/getting-started'],
            ['slug' => 'authentication', 'label' => 'Authentication', 'path' => $base . '/authentication'],
            ['slug' => 'endpoints', 'label' => 'Endpoints', 'path' => $base . '/endpoints'],
            ['slug' => 'errors', 'label' => 'Errors', 'path' => $base . '/errors'],
            ['slug' => 'webhooks', 'label' => 'Webhooks', 'path' => $base . '/webhooks'],
            ['slug' => 'sdk', 'label' => 'PHP SDK', 'path' => $base . '/sdk'],
            ['slug' => 'openapi', 'label' => 'OpenAPI', 'path' => $base . '/openapi'],
            ['slug' => 'tokens', 'label' => 'API tokens', 'path' => $base . '/tokens'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        return [
            'portal_version' => DeveloperPortalVersion::CURRENT,
            'api_version' => ApiVersion::V1,
            'platform_version' => ApiVersion::CURRENT,
            'enabled' => (bool) config('developer_portal.enabled', true),
            'sdk_enabled' => (bool) config('developer_portal.sdk_enabled', true),
            'api_base' => $this->apiBaseUrl(),
            'openapi_path' => base_path('docs/05-api/openapi.json'),
            'openapi_exists' => is_file(base_path('docs/05-api/openapi.json')),
            'webhook_events' => WebhookDispatcher::EVENTS,
            'endpoints' => $this->endpointCatalog(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function status(): array
    {
        $overview = $this->overview();

        return [
            'status' => ($overview['enabled'] && $overview['openapi_exists']) ? 'ok' : 'degraded',
            'portal_version' => DeveloperPortalVersion::CURRENT,
            'api_version' => ApiVersion::V1,
            'sdk_enabled' => $overview['sdk_enabled'],
            'openapi' => $overview['openapi_exists'],
            'api_base' => $overview['api_base'],
        ];
    }

    public function apiBaseUrl(): string
    {
        $configured = trim((string) config('developer_portal.default_api_base', ''));
        if ($configured !== '') {
            return rtrim($configured, '/');
        }

        return rtrim((string) config('app.url', ''), '/') . '/api/v1';
    }

    /**
     * @return array<string, mixed>|null
     */
    public function openapiDocument(): ?array
    {
        $path = base_path('docs/05-api/openapi.json');
        if (!is_file($path)) {
            return null;
        }
        $raw = file_get_contents($path);
        $data = is_string($raw) ? json_decode($raw, true) : null;

        return is_array($data) ? $data : null;
    }

    /**
     * @return list<array{group: string, method: string, path: string, auth: bool, summary: string}>
     */
    public function endpointCatalog(): array
    {
        return [
            ['group' => 'Public', 'method' => 'GET', 'path' => '/health', 'auth' => false, 'summary' => 'API health'],
            ['group' => 'Public', 'method' => 'GET', 'path' => '/jobs', 'auth' => false, 'summary' => 'List published jobs'],
            ['group' => 'Public', 'method' => 'GET', 'path' => '/jobs/{job}', 'auth' => false, 'summary' => 'Get published job'],
            ['group' => 'Auth', 'method' => 'GET', 'path' => '/me', 'auth' => true, 'summary' => 'Current user'],
            ['group' => 'Auth', 'method' => 'GET', 'path' => '/resumes', 'auth' => true, 'summary' => 'List own resumes'],
            ['group' => 'Auth', 'method' => 'GET', 'path' => '/resumes/{resume}', 'auth' => true, 'summary' => 'Get own resume'],
            ['group' => 'Auth', 'method' => 'GET', 'path' => '/resumes/{resume}/intelligence', 'auth' => true, 'summary' => 'Resume intelligence'],
            ['group' => 'Auth', 'method' => 'GET', 'path' => '/jobs/{job}/match', 'auth' => true, 'summary' => 'Resume↔job match'],
            ['group' => 'Auth', 'method' => 'GET', 'path' => '/tokens', 'auth' => true, 'summary' => 'List tokens'],
            ['group' => 'Auth', 'method' => 'POST', 'path' => '/tokens', 'auth' => true, 'summary' => 'Create token'],
            ['group' => 'Auth', 'method' => 'POST', 'path' => '/tokens/{token}/revoke', 'auth' => true, 'summary' => 'Revoke token'],
            ['group' => 'Auth', 'method' => 'POST', 'path' => '/tokens/revoke-all', 'auth' => true, 'summary' => 'Revoke all PATs'],
            ['group' => 'Lifecycle', 'method' => 'GET', 'path' => '/auth/status', 'auth' => false, 'summary' => 'Auth lifecycle readiness'],
            ['group' => 'Lifecycle', 'method' => 'POST', 'path' => '/auth/login', 'auth' => false, 'summary' => 'Password login → access+refresh'],
            ['group' => 'Lifecycle', 'method' => 'POST', 'path' => '/auth/refresh', 'auth' => false, 'summary' => 'Rotate refresh token'],
            ['group' => 'Lifecycle', 'method' => 'POST', 'path' => '/auth/logout', 'auth' => true, 'summary' => 'Logout current session'],
            ['group' => 'Lifecycle', 'method' => 'POST', 'path' => '/auth/logout-everywhere', 'auth' => true, 'summary' => 'Revoke all devices/sessions'],
            ['group' => 'Lifecycle', 'method' => 'GET', 'path' => '/auth/devices', 'auth' => true, 'summary' => 'List devices'],
            ['group' => 'Lifecycle', 'method' => 'POST', 'path' => '/auth/devices/{device}/revoke', 'auth' => true, 'summary' => 'Revoke device'],
            ['group' => 'Lifecycle', 'method' => 'POST', 'path' => '/auth/password/forgot', 'auth' => false, 'summary' => 'Password reset request'],
            ['group' => 'Lifecycle', 'method' => 'POST', 'path' => '/auth/password/reset', 'auth' => false, 'summary' => 'Password reset confirm'],
            ['group' => 'Lifecycle', 'method' => 'POST', 'path' => '/auth/email/verify', 'auth' => false, 'summary' => 'Verify email token'],
            ['group' => 'Lifecycle', 'method' => 'POST', 'path' => '/auth/email/resend', 'auth' => false, 'summary' => 'Resend verification'],
            ['group' => 'Lifecycle', 'method' => 'GET', 'path' => '/auth/mfa', 'auth' => true, 'summary' => 'MFA-ready status'],
            ['group' => 'Lifecycle', 'method' => 'POST', 'path' => '/auth/mfa/register', 'auth' => true, 'summary' => 'Register MFA placeholder'],
            ['group' => 'Public', 'method' => 'GET', 'path' => '/docs/openapi', 'auth' => false, 'summary' => 'OpenAPI 3 document (raw JSON; ?envelope=1 wraps)'],
            ['group' => 'JobSeeker', 'method' => 'GET', 'path' => '/applications', 'auth' => true, 'summary' => 'My applications'],
            ['group' => 'JobSeeker', 'method' => 'GET', 'path' => '/applications/{application}', 'auth' => true, 'summary' => 'Application detail'],
            ['group' => 'JobSeeker', 'method' => 'POST', 'path' => '/jobs/{job}/applications', 'auth' => true, 'summary' => 'Apply to job'],
            ['group' => 'JobSeeker', 'method' => 'POST', 'path' => '/applications/{application}/withdraw', 'auth' => true, 'summary' => 'Withdraw application'],
            ['group' => 'Employer', 'method' => 'GET', 'path' => '/employer/jobs', 'auth' => true, 'summary' => 'Owned jobs'],
            ['group' => 'Employer', 'method' => 'POST', 'path' => '/employer/jobs', 'auth' => true, 'summary' => 'Create job'],
            ['group' => 'Employer', 'method' => 'GET', 'path' => '/employer/jobs/{job}', 'auth' => true, 'summary' => 'Get owned job'],
            ['group' => 'Employer', 'method' => 'POST', 'path' => '/employer/jobs/{job}', 'auth' => true, 'summary' => 'Update owned job'],
            ['group' => 'Employer', 'method' => 'POST', 'path' => '/employer/jobs/{job}/publish', 'auth' => true, 'summary' => 'Publish job'],
            ['group' => 'Employer', 'method' => 'POST', 'path' => '/employer/jobs/{job}/unpublish', 'auth' => true, 'summary' => 'Unpublish job'],
            ['group' => 'Employer', 'method' => 'POST', 'path' => '/employer/jobs/{job}/archive', 'auth' => true, 'summary' => 'Archive job (closed)'],
            ['group' => 'Employer', 'method' => 'GET', 'path' => '/employer/jobs/{job}/applicants', 'auth' => true, 'summary' => 'Applicants'],
            ['group' => 'Employer', 'method' => 'GET', 'path' => '/employer/applications/{application}', 'auth' => true, 'summary' => 'Applicant detail'],
            ['group' => 'Employer', 'method' => 'POST', 'path' => '/employer/applications/{application}/status', 'auth' => true, 'summary' => 'Update application status'],
            ['group' => 'Employer', 'method' => 'POST', 'path' => '/employer/applications/{application}/interviews', 'auth' => true, 'summary' => 'Schedule interview'],
            ['group' => 'Employer', 'method' => 'GET', 'path' => '/employer/interviews', 'auth' => true, 'summary' => 'List interviews'],
            ['group' => 'Employer', 'method' => 'GET', 'path' => '/employer/interviews/{interview}', 'auth' => true, 'summary' => 'Interview detail'],
            ['group' => 'Employer', 'method' => 'POST', 'path' => '/employer/interviews/{interview}/reschedule', 'auth' => true, 'summary' => 'Reschedule interview'],
            ['group' => 'Employer', 'method' => 'POST', 'path' => '/employer/interviews/{interview}/cancel', 'auth' => true, 'summary' => 'Cancel interview'],
            ['group' => 'Employer', 'method' => 'POST', 'path' => '/employer/interviews/{interview}/complete', 'auth' => true, 'summary' => 'Complete interview'],
            ['group' => 'JobSeeker', 'method' => 'GET', 'path' => '/interviews', 'auth' => true, 'summary' => 'My interviews'],
            ['group' => 'JobSeeker', 'method' => 'GET', 'path' => '/interviews/{interview}', 'auth' => true, 'summary' => 'Interview detail'],
            ['group' => 'JobSeeker', 'method' => 'POST', 'path' => '/interviews/{interview}/confirm', 'auth' => true, 'summary' => 'Confirm interview'],
            ['group' => 'JobSeeker', 'method' => 'POST', 'path' => '/interviews/{interview}/decline', 'auth' => true, 'summary' => 'Decline interview'],
            ['group' => 'Employer', 'method' => 'POST', 'path' => '/employer/applications/{application}/offers', 'auth' => true, 'summary' => 'Draft job offer'],
            ['group' => 'Employer', 'method' => 'GET', 'path' => '/employer/offers', 'auth' => true, 'summary' => 'List offers'],
            ['group' => 'Employer', 'method' => 'GET', 'path' => '/employer/offers/{offer}', 'auth' => true, 'summary' => 'Offer detail'],
            ['group' => 'Employer', 'method' => 'POST', 'path' => '/employer/offers/{offer}/send', 'auth' => true, 'summary' => 'Send offer'],
            ['group' => 'Employer', 'method' => 'POST', 'path' => '/employer/offers/{offer}/withdraw', 'auth' => true, 'summary' => 'Withdraw offer'],
            ['group' => 'Employer', 'method' => 'POST', 'path' => '/employer/offers/{offer}/expire', 'auth' => true, 'summary' => 'Expire offer'],
            ['group' => 'JobSeeker', 'method' => 'GET', 'path' => '/offers', 'auth' => true, 'summary' => 'My offers'],
            ['group' => 'JobSeeker', 'method' => 'GET', 'path' => '/offers/{offer}', 'auth' => true, 'summary' => 'Offer detail'],
            ['group' => 'JobSeeker', 'method' => 'POST', 'path' => '/offers/{offer}/accept', 'auth' => true, 'summary' => 'Accept offer'],
            ['group' => 'JobSeeker', 'method' => 'POST', 'path' => '/offers/{offer}/decline', 'auth' => true, 'summary' => 'Decline offer'],
            ['group' => 'Employer', 'method' => 'GET', 'path' => '/employer/jobs/{job}/ranking', 'auth' => true, 'summary' => 'Applicant ranking'],
            ['group' => 'Portal', 'method' => 'GET', 'path' => '/portal', 'auth' => false, 'summary' => 'Developer portal status'],
        ];
    }
}
