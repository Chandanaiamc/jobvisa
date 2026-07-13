<?php

declare(strict_types=1);

namespace JobVisa\App\Repositories;

/**
 * Lightweight market salary samples from published jobs.
 */
final class SalaryMarketSampleRepository extends BaseRepository
{
    protected string $table = 'jobs';

    public function countryNameById(int $countryId): ?string
    {
        if ($countryId < 1) {
            return null;
        }
        $row = $this->fetchOne(
            'SELECT `name` FROM `countries` WHERE `id` = :id LIMIT 1',
            ['id' => $countryId]
        );

        return $row !== null ? (string) ($row['name'] ?? '') : null;
    }

    /**
     * @return array{average: float, count: int, currency: string}
     */
    public function samplePublished(?string $titleHint, ?string $currency, int $limit = 25): array
    {
        $limit = max(1, min(50, $limit));
        $params = [];
        $sql = 'SELECT j.`salary_min`, j.`salary_max`, j.`salary_currency`, j.`salary_period`
                FROM `jobs` j
                WHERE j.`status` = \'published\'
                  AND j.`salary_min` IS NOT NULL
                  AND j.`salary_min` > 0';

        if ($currency !== null && $currency !== '') {
            $sql .= ' AND UPPER(j.`salary_currency`) = :currency';
            $params['currency'] = strtoupper(substr($currency, 0, 3));
        }
        if ($titleHint !== null && trim($titleHint) !== '') {
            $sql .= ' AND j.`title` LIKE :title';
            $params['title'] = '%' . mb_substr(trim($titleHint), 0, 40) . '%';
        }

        $sql .= ' ORDER BY j.`updated_at` DESC LIMIT ' . $limit;
        $rows = $this->fetchAll($sql, $params);

        if ($rows === [] && $titleHint !== null) {
            return $this->samplePublished(null, $currency, $limit);
        }

        $mids = [];
        $curr = $currency !== null && $currency !== '' ? strtoupper(substr($currency, 0, 3)) : 'USD';
        foreach ($rows as $row) {
            $min = (float) ($row['salary_min'] ?? 0);
            $max = (float) ($row['salary_max'] ?? $min);
            if ($min <= 0) {
                continue;
            }
            $mid = $max > $min ? (($min + $max) / 2) : $min;
            $period = (string) ($row['salary_period'] ?? 'month');
            if ($period === 'month') {
                $mid *= 12;
            } elseif ($period === 'hour') {
                $mid *= 2080;
            }
            $mids[] = $mid;
            $c = strtoupper((string) ($row['salary_currency'] ?? ''));
            if ($c !== '') {
                $curr = $c;
            }
        }

        if ($mids === []) {
            return ['average' => 0.0, 'count' => 0, 'currency' => $curr];
        }

        return [
            'average' => array_sum($mids) / count($mids),
            'count' => count($mids),
            'currency' => $curr,
        ];
    }
}
