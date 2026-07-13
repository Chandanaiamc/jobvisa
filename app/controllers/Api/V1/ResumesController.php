<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use JobVisa\App\Domain\Api\Http\ApiException;
use JobVisa\App\Domain\Api\Resources\ApiResource;
use JobVisa\App\Domain\Resume\Intelligence\Services\ResumeIntelligenceService;
use JobVisa\App\Repositories\Contracts\ResumeRepositoryInterface;

final class ResumesController extends ApiController
{
    public function index(): void
    {
        $actor = $this->actor();
        $userId = (int) ($actor['id'] ?? 0);
        $rows = container(ResumeRepositoryInterface::class)->listActiveRecordsForUser($userId);
        $data = array_map(static fn (array $r): array => ApiResource::resume($r), $rows);
        $this->ok(['resumes' => $data], array_merge($this->platformMeta(), ['count' => count($data)]));
    }

    public function show(string $resume): void
    {
        $resumeId = (int) $resume;
        $actor = $this->actor();
        $userId = (int) ($actor['id'] ?? 0);
        if ($resumeId < 1) {
            throw ApiException::validation('Invalid resume id.', ['resume' => ['Must be a positive integer.']]);
        }
        $row = container(ResumeRepositoryInterface::class)->findByIdForUser($resumeId, $userId);
        if ($row === null) {
            throw ApiException::notFound('Resume not found.');
        }
        $this->ok(['resume' => ApiResource::resume($row)], $this->platformMeta());
    }

    public function intelligence(string $resume): void
    {
        $resumeId = (int) $resume;
        $actor = $this->actor();
        try {
            $page = container(ResumeIntelligenceService::class)->page($actor, $resumeId, false);
        } catch (\Throwable) {
            throw ApiException::notFound('Resume intelligence unavailable.');
        }

        $intel = $page['intelligence'] ?? null;
        $safe = [
            'resume' => $page['resume'] ?? ['id' => $resumeId],
            'disclaimer' => $page['disclaimer'] ?? 'Heuristic guidance only.',
        ];

        if (is_object($intel) && method_exists($intel, 'toArray')) {
            $arr = $intel->toArray();
            unset($arr['raw_json'], $arr['analysis_json'], $arr['internal']);
            $safe['intelligence'] = $arr;
        } elseif (is_array($intel)) {
            unset($intel['raw_json'], $intel['analysis_json']);
            $safe['intelligence'] = $intel;
        }

        $this->ok($safe, $this->platformMeta());
    }
}
