<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use JobVisa\App\Domain\Api\Http\ApiException;
use JobVisa\App\Domain\JobOffer\Exceptions\JobOfferException;
use JobVisa\App\Domain\JobOffer\Services\JobOfferService;

final class OffersController extends ApiController
{
    public function index(): void
    {
        try {
            $rows = container(JobOfferService::class)->listForSeeker($this->actor(), 100);
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

    public function accept(string $offer): void
    {
        $result = container(JobOfferService::class)->accept($this->actor(), $this->positiveId($offer));
        $this->respondMutation($result);
    }

    public function decline(string $offer): void
    {
        $result = container(JobOfferService::class)->decline($this->actor(), $this->positiveId($offer));
        $this->respondMutation($result);
    }

    /**
     * @param  array{success: bool, message: string, offer?: array<string, mixed>}  $result
     */
    private function respondMutation(array $result): void
    {
        if (!($result['success'] ?? false)) {
            $message = (string) ($result['message'] ?? 'Request failed.');
            if (stripos($message, 'not found') !== false) {
                throw ApiException::notFound($message);
            }
            if (stripos($message, 'expired') !== false) {
                throw ApiException::validation($message, ['status' => [$message]]);
            }
            if (stripos($message, 'cannot change') !== false) {
                throw ApiException::validation($message, ['status' => [$message]]);
            }
            throw ApiException::validation($message);
        }

        $this->ok([
            'message' => $result['message'] ?? 'OK',
            'offer' => $result['offer'] ?? null,
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
}
