<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use JobVisa\App\Domain\Api\Auth\ApiAuth;
use JobVisa\App\Domain\Api\Http\ApiException;
use JobVisa\App\Domain\Api\Resources\ApiResource;

final class MeController extends ApiController
{
    public function show(): void
    {
        $user = ApiAuth::user();
        if ($user === null) {
            throw ApiException::unauthorized();
        }
        $this->ok([
            'user' => ApiResource::user($user),
            'token' => ApiResource::tokenMeta(ApiAuth::token() ?? []),
        ], $this->platformMeta());
    }
}
