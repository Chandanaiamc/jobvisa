<?php

declare(strict_types=1);

namespace App\Controllers\Developers;

use App\Core\Controller;
use JobVisa\App\Auth\AuthManager;
use JobVisa\App\Domain\Api\Auth\PersonalAccessTokenService;
use JobVisa\App\Domain\Api\Portal\Services\DeveloperPortalService;
use JobVisa\App\Domain\Api\Resources\ApiResource;
use JobVisa\App\Security\Csrf;
use JobVisa\App\Security\SessionManager;

/**
 * HTML developer portal (Sprint 4.6).
 */
final class DeveloperPortalController extends Controller
{
    private DeveloperPortalService $portal;

    public function __construct()
    {
        parent::__construct();
        $this->portal = container(DeveloperPortalService::class);
    }

    public function index(): void
    {
        $this->page('home', 'Developer Portal', [
            'overview' => $this->portal->overview(),
        ]);
    }

    public function gettingStarted(): void
    {
        $this->page('getting-started', 'Getting started', [
            'api_base' => $this->portal->apiBaseUrl(),
        ]);
    }

    public function authentication(): void
    {
        $this->page('authentication', 'Authentication', [
            'api_base' => $this->portal->apiBaseUrl(),
        ]);
    }

    public function endpoints(): void
    {
        $this->page('endpoints', 'Endpoints', [
            'endpoints' => $this->portal->endpointCatalog(),
            'api_base' => $this->portal->apiBaseUrl(),
        ]);
    }

    public function errors(): void
    {
        $this->page('errors', 'Errors & rate limits', []);
    }

    public function webhooks(): void
    {
        $this->page('webhooks', 'Webhooks', [
            'events' => $this->portal->overview()['webhook_events'],
        ]);
    }

    public function sdk(): void
    {
        $this->page('sdk', 'PHP SDK', [
            'api_base' => $this->portal->apiBaseUrl(),
            'sdk_enabled' => (bool) config('developer_portal.sdk_enabled', true),
        ]);
    }

    public function openapi(): void
    {
        $this->page('openapi', 'OpenAPI', [
            'document' => $this->portal->openapiDocument(),
            'api_json_url' => url('/api/v1/docs/openapi'),
        ]);
    }

    public function tokens(): void
    {
        /** @var AuthManager $auth */
        $auth = container(AuthManager::class);
        $user = $auth->user();
        $userId = (int) ($user['id'] ?? 0);
        $tokens = [];
        if ($userId > 0) {
            $tokens = container(PersonalAccessTokenService::class)->listForUser($userId);
            $tokens = array_map(
                static fn (array $t): array => ApiResource::tokenMeta($t),
                $tokens
            );
        }

        $this->page('tokens', 'API tokens', [
            'tokens' => $tokens,
            'csrf' => Csrf::token(),
            'plain_token' => SessionManager::getFlash('api_token_plain'),
            'flash_error' => SessionManager::getFlash('error'),
            'flash_success' => SessionManager::getFlash('success'),
        ]);
    }

    public function createToken(): void
    {
        /** @var AuthManager $auth */
        $auth = container(AuthManager::class);
        $userId = (int) ($auth->id() ?? 0);
        $name = trim((string) ($_POST['name'] ?? 'Developer portal'));
        try {
            $created = container(PersonalAccessTokenService::class)->create($userId, $name !== '' ? $name : 'Developer portal');
            SessionManager::flash('api_token_plain', $created['token']);
            SessionManager::flash('success', 'Token created. Copy it now — it will not be shown again.');
        } catch (\Throwable $e) {
            SessionManager::flash('error', 'Unable to create token.');
        }
        $this->redirect(url('/developers/tokens'));
    }

    public function revokeToken(string $token): void
    {
        /** @var AuthManager $auth */
        $auth = container(AuthManager::class);
        $userId = (int) ($auth->id() ?? 0);
        $tokenId = (int) $token;
        $ok = container(PersonalAccessTokenService::class)->revoke($userId, $tokenId);
        SessionManager::flash($ok ? 'success' : 'error', $ok ? 'Token revoked.' : 'Token not found.');
        $this->redirect(url('/developers/tokens'));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function page(string $active, string $title, array $data): void
    {
        if (!(bool) config('developer_portal.enabled', true)) {
            http_response_code(404);
            $this->render('errors/404', ['title' => 'Not Found', 'path' => '/developers']);

            return;
        }

        $this->render('developers/layout', array_merge($data, [
            'title' => $title,
            'activeNav' => $active,
            'nav' => $this->portal->nav(),
            'contentView' => 'developers/pages/' . $active,
            'portal_version' => $this->portal->overview()['portal_version'],
            'api_base' => $this->portal->apiBaseUrl(),
        ]));
    }
}
