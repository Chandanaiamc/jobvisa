<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use JobVisa\App\Domain\Api\Http\ApiException;
use JobVisa\App\Domain\Application\Exceptions\ApplicationException;
use JobVisa\App\Domain\Application\Services\ApplicationService;

/**
 * Seeker application endpoints (Phase 1).
 */
final class ApplicationsController extends ApiController
{
    public function index(): void
    {
        try {
            $apps = container(ApplicationService::class)->listForSeeker($this->actor(), 100);
        } catch (ApplicationException $e) {
            throw $this->mapException($e);
        }
        $this->ok(['applications' => $apps], array_merge($this->platformMeta(), ['count' => count($apps)]));
    }

    public function show(string $application): void
    {
        try {
            $row = container(ApplicationService::class)->getForSeeker($this->actor(), $this->positiveId($application));
        } catch (ApplicationException $e) {
            throw $this->mapException($e);
        }
        $this->ok(['application' => $row], $this->platformMeta());
    }

    public function store(string $job): void
    {
        $body = $this->jsonBody();
        $result = container(ApplicationService::class)->apply($this->actor(), $this->positiveId($job), $body);
        $this->respondMutation($result, 201);
    }

    public function withdraw(string $application): void
    {
        $result = container(ApplicationService::class)->withdraw($this->actor(), $this->positiveId($application));
        $this->respondMutation($result, 200);
    }

    /**
     * @param  array{success: bool, message: string, errors?: array<string, list<string>>, application?: array<string, mixed>, conflict?: bool}  $result
     */
    private function respondMutation(array $result, int $successStatus): void
    {
        if (!($result['success'] ?? false)) {
            $message = (string) ($result['message'] ?? 'Request failed.');
            if (!empty($result['conflict'])) {
                throw ApiException::conflict($message, [
                    'application' => $result['application'] ?? null,
                ]);
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
            if (stripos($message, 'published') !== false) {
                throw ApiException::validation($message, ['job' => [$message]]);
            }
            if (stripos($message, 'cannot change') !== false) {
                throw ApiException::validation($message, ['status' => [$message]]);
            }
            throw ApiException::validation($message);
        }

        $this->ok([
            'message' => $result['message'] ?? 'OK',
            'application' => $result['application'] ?? null,
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

    private function mapException(ApplicationException $e): ApiException
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
