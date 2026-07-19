<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use JobVisa\App\Domain\Api\Http\ApiException;
use JobVisa\App\Domain\HiringCompletion\Exceptions\HiringCompletionException;
use JobVisa\App\Domain\HiringCompletion\Services\HiringCompletionService;

final class HireCompletionsController extends ApiController
{
    public function index(): void
    {
        try {
            $rows = container(HiringCompletionService::class)->listForSeeker($this->actor(), 100);
        } catch (HiringCompletionException $e) {
            throw $this->mapException($e);
        }
        $this->ok(['hire_completions' => $rows], array_merge($this->platformMeta(), ['count' => count($rows)]));
    }

    public function show(string $hire): void
    {
        try {
            $row = container(HiringCompletionService::class)->getForActor($this->actor(), $this->positiveId($hire));
        } catch (HiringCompletionException $e) {
            throw $this->mapException($e);
        }
        $this->ok(['hire_completion' => $row], $this->platformMeta());
    }

    private function positiveId(string $value): int
    {
        $id = (int) $value;
        if ($id < 1) {
            throw ApiException::validation('Invalid id.', ['id' => ['Must be a positive integer.']]);
        }

        return $id;
    }

    private function mapException(HiringCompletionException $e): ApiException
    {
        $msg = $e->getMessage();
        if (stripos($msg, 'not found') !== false) {
            return ApiException::notFound($msg);
        }
        if (stripos($msg, 'not allowed') !== false) {
            return ApiException::forbidden($msg);
        }

        return ApiException::validation($msg);
    }
}
