<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use JobVisa\App\Domain\Api\Auth\PersonalAccessTokenService;
use JobVisa\App\Domain\Api\Http\ApiException;
use JobVisa\App\Domain\Api\Resources\ApiResource;

final class TokensController extends ApiController
{
    public function index(): void
    {
        $userId = (int) ($this->actor()['id'] ?? 0);
        $rows = container(PersonalAccessTokenService::class)->listForUser($userId);
        $data = array_map(static fn (array $t): array => ApiResource::tokenMeta($t), $rows);
        $this->ok(['tokens' => $data], $this->platformMeta());
    }

    public function store(): void
    {
        $body = $this->jsonBody();
        $input = $this->validator()->validate(array_merge(['name' => $body['name'] ?? ''], $body), [
            'name' => 'required|max:120',
            'ttl_days' => 'integer|min:0|max:3650',
        ]);
        // Re-validate name as string max via manual check (validator treats max as numeric when numeric)
        $name = trim((string) ($input['name'] ?? $body['name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 120) {
            throw ApiException::validation('Invalid token name.', ['name' => ['Name is required (max 120).']]);
        }

        $userId = (int) ($this->actor()['id'] ?? 0);
        $created = container(PersonalAccessTokenService::class)->create(
            $userId,
            $name,
            isset($input['ttl_days']) ? (int) $input['ttl_days'] : null
        );
        $this->ok([
            'token' => $created['token'],
            'meta' => ApiResource::tokenMeta($created),
            'warning' => 'Store this token securely. It will not be shown again.',
        ], $this->platformMeta(), 201);
    }

    public function destroy(string $token): void
    {
        $tokenId = (int) $token;
        $userId = (int) ($this->actor()['id'] ?? 0);
        $ok = container(PersonalAccessTokenService::class)->revoke($userId, $tokenId);
        if (!$ok) {
            throw ApiException::notFound('Token not found.');
        }
        $this->ok(['revoked' => true, 'id' => $tokenId], $this->platformMeta());
    }

    /**
     * @return array<string, mixed>
     */
    private function jsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if (!is_string($raw) || trim($raw) === '') {
            return is_array($_POST) ? $_POST : [];
        }
        $data = json_decode($raw, true);

        return is_array($data) ? $data : [];
    }
}
