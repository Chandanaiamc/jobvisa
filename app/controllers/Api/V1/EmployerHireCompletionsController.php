<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use JobVisa\App\Domain\Api\Http\ApiException;
use JobVisa\App\Domain\HiringCompletion\Exceptions\HiringCompletionException;
use JobVisa\App\Domain\HiringCompletion\Services\HiringCompletionService;

final class EmployerHireCompletionsController extends ApiController
{
    public function index(): void
    {
        try {
            $rows = container(HiringCompletionService::class)->listForEmployer($this->actor(), 100);
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

    public function confirm(string $hire): void
    {
        $result = container(HiringCompletionService::class)->confirm(
            $this->actor(),
            $this->positiveId($hire),
            $this->jsonBody()
        );
        $this->respondMutation($result);
    }

    public function complete(string $hire): void
    {
        $result = container(HiringCompletionService::class)->complete(
            $this->actor(),
            $this->positiveId($hire),
            $this->jsonBody()
        );
        $this->respondMutation($result);
    }

    public function cancel(string $hire): void
    {
        $body = $this->jsonBody();
        $note = isset($body['note']) ? (string) $body['note'] : null;
        $result = container(HiringCompletionService::class)->cancel(
            $this->actor(),
            $this->positiveId($hire),
            $note
        );
        $this->respondMutation($result);
    }

    /**
     * @param  array{success: bool, message: string, errors?: array<string, list<string>>, hire_completion?: array<string, mixed>, job_closed?: bool}  $result
     */
    private function respondMutation(array $result): void
    {
        if (!($result['success'] ?? false)) {
            $message = (string) ($result['message'] ?? 'Request failed.');
            if (isset($result['errors']) && is_array($result['errors'])) {
                throw ApiException::validation($message, $result['errors']);
            }
            if (stripos($message, 'not found') !== false) {
                throw ApiException::notFound($message);
            }
            if (stripos($message, 'not allowed') !== false) {
                throw ApiException::forbidden($message);
            }
            throw ApiException::validation($message);
        }

        $payload = [
            'message' => $result['message'] ?? 'OK',
            'hire_completion' => $result['hire_completion'] ?? null,
        ];
        if (array_key_exists('job_closed', $result)) {
            $payload['job_closed'] = (bool) $result['job_closed'];
        }

        $this->ok($payload, $this->platformMeta());
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
