<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use JobVisa\App\Domain\Api\Http\ApiException;
use JobVisa\App\Domain\JobOffer\Exceptions\JobOfferException;
use JobVisa\App\Domain\JobOffer\Services\JobOfferService;

final class EmployerOffersController extends ApiController
{
    public function index(): void
    {
        try {
            $rows = container(JobOfferService::class)->listForEmployer($this->actor(), 100);
        } catch (JobOfferException $e) {
            throw $this->mapException($e);
        }
        $this->ok(['offers' => $rows], array_merge($this->platformMeta(), ['count' => count($rows)]));
    }

    public function show(string $offer): void
    {
        try {
            $row = container(JobOfferService::class)->getForActor($this->actor(), $this->positiveId($offer));
        } catch (JobOfferException $e) {
            throw $this->mapException($e);
        }
        $this->ok(['offer' => $row], $this->platformMeta());
    }

    public function store(string $application): void
    {
        $result = container(JobOfferService::class)->create(
            $this->actor(),
            $this->positiveId($application),
            $this->jsonBody()
        );
        $this->respondMutation($result, 201);
    }

    public function send(string $offer): void
    {
        $body = $this->jsonBody();
        $note = isset($body['note']) ? (string) $body['note'] : null;
        $result = container(JobOfferService::class)->send(
            $this->actor(),
            $this->positiveId($offer),
            $note
        );
        $this->respondMutation($result, 200);
    }

    public function withdraw(string $offer): void
    {
        $body = $this->jsonBody();
        $note = isset($body['note']) ? (string) $body['note'] : null;
        $result = container(JobOfferService::class)->withdraw(
            $this->actor(),
            $this->positiveId($offer),
            $note
        );
        $this->respondMutation($result, 200);
    }

    public function expire(string $offer): void
    {
        $body = $this->jsonBody();
        $note = isset($body['note']) ? (string) $body['note'] : null;
        $result = container(JobOfferService::class)->expire(
            $this->actor(),
            $this->positiveId($offer),
            $note
        );
        $this->respondMutation($result, 200);
    }

    /**
     * @param  array{success: bool, message: string, errors?: array<string, list<string>>, offer?: array<string, mixed>, conflict?: bool}  $result
     */
    private function respondMutation(array $result, int $successStatus): void
    {
        if (!($result['success'] ?? false)) {
            $message = (string) ($result['message'] ?? 'Request failed.');
            if (!empty($result['conflict'])) {
                throw ApiException::conflict($message, ['offer' => $result['offer'] ?? null]);
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
            'offer' => $result['offer'] ?? null,
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

    private function mapException(JobOfferException $e): ApiException
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
