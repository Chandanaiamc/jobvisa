<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use JobVisa\App\Domain\Api\Http\ApiException;
use JobVisa\App\Domain\InterviewScheduling\Exceptions\InterviewSchedulingException;
use JobVisa\App\Domain\InterviewScheduling\Services\InterviewSchedulingService;

final class EmployerInterviewsController extends ApiController
{
    public function index(): void
    {
        try {
            $rows = container(InterviewSchedulingService::class)->listForEmployer($this->actor(), 100);
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

    public function store(string $application): void
    {
        $body = $this->jsonBody();
        $result = container(InterviewSchedulingService::class)->schedule(
            $this->actor(),
            $this->positiveId($application),
            $body
        );
        $this->respondMutation($result, 201);
    }

    public function reschedule(string $interview): void
    {
        $result = container(InterviewSchedulingService::class)->reschedule(
            $this->actor(),
            $this->positiveId($interview),
            $this->jsonBody()
        );
        $this->respondMutation($result, 200);
    }

    public function cancel(string $interview): void
    {
        $body = $this->jsonBody();
        $note = isset($body['note']) ? (string) $body['note'] : null;
        $result = container(InterviewSchedulingService::class)->cancel(
            $this->actor(),
            $this->positiveId($interview),
            $note
        );
        $this->respondMutation($result, 200);
    }

    public function complete(string $interview): void
    {
        $body = $this->jsonBody();
        $note = isset($body['note']) ? (string) $body['note'] : null;
        $result = container(InterviewSchedulingService::class)->complete(
            $this->actor(),
            $this->positiveId($interview),
            $note
        );
        $this->respondMutation($result, 200);
    }

    /**
     * @param  array{success: bool, message: string, errors?: array<string, list<string>>, interview?: array<string, mixed>, conflict?: bool}  $result
     */
    private function respondMutation(array $result, int $successStatus): void
    {
        if (!($result['success'] ?? false)) {
            $message = (string) ($result['message'] ?? 'Request failed.');
            if (!empty($result['conflict'])) {
                throw ApiException::conflict($message, ['interview' => $result['interview'] ?? null]);
            }
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

        $this->ok([
            'message' => $result['message'] ?? 'OK',
            'interview' => $result['interview'] ?? null,
        ], $this->platformMeta(), $successStatus);
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
