<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use JobVisa\App\Domain\Api\Http\ApiException;
use JobVisa\App\Domain\InterviewScheduling\Exceptions\InterviewSchedulingException;
use JobVisa\App\Domain\InterviewScheduling\Services\InterviewSchedulingService;

final class InterviewsController extends ApiController
{
    public function index(): void
    {
        try {
            $rows = container(InterviewSchedulingService::class)->listForSeeker($this->actor(), 100);
        } catch (InterviewSchedulingException $e) {
            throw $this->mapException($e);
        }
        $this->ok(['interviews' => $rows], array_merge($this->platformMeta(), ['count' => count($rows)]));
    }

    public function show(string $interview): void
    {
        try {
            $row = container(InterviewSchedulingService::class)->getForActor($this->actor(), $this->positiveId($interview));
        } catch (InterviewSchedulingException $e) {
            throw $this->mapException($e);
        }
        $this->ok(['interview' => $row], $this->platformMeta());
    }

    public function confirm(string $interview): void
    {
        $result = container(InterviewSchedulingService::class)->confirm($this->actor(), $this->positiveId($interview));
        $this->respondMutation($result);
    }

    public function decline(string $interview): void
    {
        $result = container(InterviewSchedulingService::class)->decline($this->actor(), $this->positiveId($interview));
        $this->respondMutation($result);
    }

    /**
     * @param  array{success: bool, message: string, interview?: array<string, mixed>}  $result
     */
    private function respondMutation(array $result): void
    {
        if (!($result['success'] ?? false)) {
            $message = (string) ($result['message'] ?? 'Request failed.');
            if (stripos($message, 'not found') !== false) {
                throw ApiException::notFound($message);
            }
            if (stripos($message, 'cannot change') !== false) {
                throw ApiException::validation($message, ['status' => [$message]]);
            }
            throw ApiException::validation($message);
        }

        $this->ok([
            'message' => $result['message'] ?? 'OK',
            'interview' => $result['interview'] ?? null,
        ], $this->platformMeta());
    }

    private function positiveId(string $value): int
    {
        $id = (int) $value;
        if ($id < 1) {
            throw ApiException::validation('Invalid id.', ['id' => ['Must be a positive integer.']]);
        }

        return $id;
    }

    private function mapException(InterviewSchedulingException $e): ApiException
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
