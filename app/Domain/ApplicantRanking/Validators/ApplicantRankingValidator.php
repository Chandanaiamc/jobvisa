<?php

declare(strict_types=1);

namespace JobVisa\App\Domain\ApplicantRanking\Validators;

use JobVisa\App\Domain\ApplicantRanking\DTO\RankingFilterDTO;
use JobVisa\App\Domain\ApplicantRanking\Exceptions\ApplicantRankingException;

final class ApplicantRankingValidator
{
    public function assertJobId(int $jobId): void
    {
        if ($jobId < 1) {
            throw ApplicantRankingException::invalidFilters('A valid job is required.');
        }
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function filters(array $input): RankingFilterDTO
    {
        return RankingFilterDTO::fromInput($input);
    }
}
