<?php

declare(strict_types=1);

/** @deprecated Replaced by pages/jobs/index — kept as redirect shell if referenced. */
$contentView = 'pages/jobs/list';
$jobs = $jobs ?? [];
$pagination = $pagination ?? ['page' => 1, 'per_page' => 12, 'total' => 0, 'total_pages' => 1];
$filters = $filters ?? ['q' => '', 'country_id' => null, 'job_type_id' => null];
$countries = $countries ?? [];
$jobTypes = $jobTypes ?? [];
$apiListUrl = $apiListUrl ?? (rtrim((string) app_url(''), '/') . '/api/v1/jobs');
require base_path('app/views/layouts/public.php');
