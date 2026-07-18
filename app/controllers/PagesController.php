<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use JobVisa\App\Domain\Job\Services\PublicJobsService;

/**
 * Static public pages + public jobs SSR shells.
 */
final class PagesController extends Controller
{
    /**
     * Display the about page.
     */
    public function about(): void
    {
        $this->render('pages/about', [
            'title' => 'About',
        ]);
    }

    /**
     * Display the contact page.
     */
    public function contact(): void
    {
        $this->render('pages/contact', [
            'title' => 'Contact',
        ]);
    }

    /**
     * Public jobs listing (SSR + progressive enhancement).
     */
    public function jobs(): void
    {
        /** @var PublicJobsService $svc */
        $svc = container(PublicJobsService::class);
        $query = [
            'q' => (string) ($_GET['q'] ?? ''),
            'country_id' => (int) ($_GET['country_id'] ?? 0),
            'job_type_id' => (int) ($_GET['job_type_id'] ?? 0),
            'page' => (int) ($_GET['page'] ?? 1),
            'per_page' => (int) ($_GET['per_page'] ?? 12),
        ];
        $result = $svc->search($query);
        $options = $svc->filterOptions();

        $this->render('pages/jobs/index', [
            'title' => 'Jobs',
            'jobs' => $result['jobs'],
            'pagination' => $result['pagination'],
            'filters' => $result['filters_applied'],
            'countries' => $options['countries'],
            'jobTypes' => $options['job_types'],
            'apiListUrl' => rtrim((string) app_url(''), '/') . '/api/v1/jobs',
        ]);
    }

    /**
     * Public job detail (SSR).
     */
    public function jobShow(string $job): void
    {
        $jobId = (int) $job;
        /** @var PublicJobsService $svc */
        $svc = container(PublicJobsService::class);
        $row = $jobId > 0 ? $svc->find($jobId) : null;
        if ($row === null) {
            http_response_code(404);
            $this->render('pages/jobs/not-found', [
                'title' => 'Job not found',
            ]);

            return;
        }

        $this->render('pages/jobs/show', [
            'title' => (string) ($row['title'] ?? 'Job'),
            'job' => $row,
            'apiJobUrl' => rtrim((string) app_url(''), '/') . '/api/v1/jobs/' . $jobId,
        ]);
    }

    /**
     * Display the companies page.
     */
    public function companies(): void
    {
        $this->render('pages/companies', [
            'title' => 'Companies',
        ]);
    }
}
