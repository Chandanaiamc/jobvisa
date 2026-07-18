<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use JobVisa\App\Domain\Api\Http\ApiException;
use JobVisa\App\Domain\Api\Resources\ApiResource;
use JobVisa\App\Domain\ApplicantRanking\Services\ApplicantRankingService;
use JobVisa\App\Domain\Job\Exceptions\JobException;
use JobVisa\App\Domain\Job\Services\EmployerJobsService;
use JobVisa\App\Repositories\Contracts\ApplicationRepositoryInterface;
use JobVisa\App\Repositories\Contracts\JobRepositoryInterface;

final class EmployerJobsController extends ApiController
{
    public function index(): void
    {
        $actor = $this->actor();
        /** @var EmployerJobsService $svc */
        $svc = container(EmployerJobsService::class);
        $jobs = $svc->listForActor($actor, 50);
        $this->ok(['jobs' => $jobs], array_merge($this->platformMeta(), ['count' => count($jobs)]));
    }

    public function show(string $job): void
    {
        $jobId = $this->positiveId($job);
        try {
            $row = container(EmployerJobsService::class)->getForActor($this->actor(), $jobId);
        } catch (JobException $e) {
            throw $this->mapJobException($e);
        }
        $this->ok(['job' => $row], $this->platformMeta());
    }

    public function store(): void
    {
        $body = $this->jsonBody();
        $result = container(EmployerJobsService::class)->create($this->actor(), $body);
        $this->respondMutation($result, 201);
    }

    public function update(string $job): void
    {
        $jobId = $this->positiveId($job);
        $body = $this->jsonBody();
        $result = container(EmployerJobsService::class)->update($this->actor(), $jobId, $body);
        $this->respondMutation($result, 200);
    }

    public function publish(string $job): void
    {
        $result = container(EmployerJobsService::class)->publish($this->actor(), $this->positiveId($job));
        $this->respondMutation($result, 200);
    }

    public function unpublish(string $job): void
    {
        $result = container(EmployerJobsService::class)->unpublish($this->actor(), $this->positiveId($job));
        $this->respondMutation($result, 200);
    }

    public function archive(string $job): void
    {
        $result = container(EmployerJobsService::class)->archive($this->actor(), $this->positiveId($job));
        $this->respondMutation($result, 200);
    }

    public function applicants(string $job): void
    {
        $jobId = $this->positiveId($job);
        $actor = $this->actor();
        $userId = (int) ($actor['id'] ?? 0);
        $owned = container(JobRepositoryInterface::class)->findOwnedByEmployerUser($jobId, $userId);
        if ($owned === null) {
            throw ApiException::notFound('Job not found.');
        }
        $apps = container(ApplicationRepositoryInterface::class)->findDetailedByJobId($jobId, 200);
        $data = array_map(static fn (array $a): array => ApiResource::applicant($a), $apps);
        $this->ok([
            'job' => ApiResource::jobEmployer($owned, true),
            'applicants' => $data,
        ], array_merge($this->platformMeta(), ['count' => count($data)]));
    }

    public function ranking(string $job): void
    {
        $jobId = $this->positiveId($job);
        $actor = $this->actor();
        try {
            $page = container(ApplicantRankingService::class)->rankingPage($actor, $jobId, [], false);
        } catch (\Throwable) {
            throw ApiException::notFound('Ranking unavailable for this job.');
        }

        $candidates = [];
        foreach ($page['candidates'] ?? [] as $c) {
            if (is_object($c) && method_exists($c, 'toArray')) {
                $row = $c->toArray();
            } elseif (is_array($c)) {
                $row = $c;
            } else {
                continue;
            }
            unset($row['email'], $row['phone'], $row['raw']);
            $candidates[] = $row;
        }

        $this->ok([
            'job' => $page['job'] ?? null,
            'candidates' => $candidates,
            'total_ranked' => $page['total_ranked'] ?? count($candidates),
            'disclaimer' => $page['disclaimer'] ?? 'Heuristic ranking only.',
        ], $this->platformMeta());
    }

    /**
     * @param  array{success: bool, message: string, errors?: array<string, list<string>>, job?: array<string, mixed>}  $result
     */
    private function respondMutation(array $result, int $successStatus): void
    {
        if (!($result['success'] ?? false)) {
            $message = (string) ($result['message'] ?? 'Request failed.');
            if (isset($result['errors']) && is_array($result['errors'])) {
                throw ApiException::validation($message, $result['errors']);
            }
            if (stripos($message, 'not found') !== false) {
                throw ApiException::notFound($message);
            }
            if (stripos($message, 'not allowed') !== false || stripos($message, 'forbidden') !== false) {
                throw ApiException::forbidden($message);
            }
            throw ApiException::validation($message);
        }

        $this->ok([
            'message' => $result['message'] ?? 'OK',
            'job' => $result['job'] ?? null,
        ], $this->platformMeta(), $successStatus);
    }

    private function positiveId(string $job): int
    {
        $jobId = (int) $job;
        if ($jobId < 1) {
            throw ApiException::validation('Invalid job id.', ['job' => ['Must be a positive integer.']]);
        }

        return $jobId;
    }

    private function mapJobException(JobException $e): ApiException
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
