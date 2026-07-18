<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use JobVisa\App\Domain\Api\Http\ApiException;
use JobVisa\App\Domain\Application\Exceptions\ApplicationException;
use JobVisa\App\Domain\Application\Services\ApplicationService;

/**
 * Employer application management (Phase 1).
 */
final class EmployerApplicationsController extends ApiController
{
    public function show(string $application): void
    {
        try {
            $row = container(ApplicationService::class)->getForEmployer(
                $this->actor(),
                $this->positiveId($application)
            );
        } catch (ApplicationException $e) {
            throw $this->mapException($e);
        }
        $this->ok(['application' => $row], $this->platformMeta());
    }

    public function updateStatus(string $application): void
    {
        $body = $this->jsonBody();
        $result = container(ApplicationService::class)->updateStatus(
            $this->actor(),
            $this->positiveId($application),
            $body
        );
        $this->respondMutation($result);
    }

    /**
     * @param  array{success: bool, message: string, errors?: array<string, list<string>>, application?: array<string, mixed>}  $result
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

        $this->ok([
            'message' => $result['message'] ?? 'OK',
            'application' => $result['application'] ?? null,
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
