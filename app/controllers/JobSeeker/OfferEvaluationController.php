<?php

declare(strict_types=1);

namespace App\Controllers\JobSeeker;

use JobVisa\App\Domain\OfferEvaluation\Exceptions\OfferEvaluationException;
use JobVisa\App\Domain\OfferEvaluation\Services\OfferEvaluationService;
use JobVisa\App\Security\SessionManager;

/**
 * AI Offer Evaluation Assistant (Sprint 3.9).
 */
final class OfferEvaluationController extends JobSeekerController
{
    private OfferEvaluationService $offers;

    public function __construct()
    {
        parent::__construct();
        $this->offers = container(OfferEvaluationService::class);
    }

    public function show(string $id): void
    {
        try {
            $data = $this->offers->page($this->actor(), (int) $id);
        } catch (OfferEvaluationException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $this->dashboard('jobseeker/pages/resumes/offer-evaluation', [
            'title' => 'Offer Evaluation',
            'activeNav' => 'resumes',
            'resume' => $data['resume'],
            'completion' => $data['completion'],
            'offerAnalysis' => $data['analysis'],
            'matchedJobs' => $data['matched_jobs'],
            'defaults' => $data['defaults'],
            'versions' => $data['versions'],
            'history' => $data['history'],
            'deletedHistory' => $data['deleted_history'],
            'canEdit' => $data['can_edit'],
            'version' => $data['version'],
            'disclaimer' => $data['disclaimer'],
            'resumeSection' => 'offer-evaluation',
        ]);
    }

    public function evaluate(string $id): void
    {
        try {
            $result = $this->offers->evaluate($this->actor(), (int) $id, $_POST);
        } catch (OfferEvaluationException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/offer-evaluation'));
        }

        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/offer-evaluation',
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message']
        );
    }

    public function recalculate(string $id): void
    {
        try {
            $result = $this->offers->recalculate($this->actor(), (int) $id, $_POST);
        } catch (OfferEvaluationException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/offer-evaluation'));
        }

        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/offer-evaluation',
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message']
        );
    }

    public function history(string $id): void
    {
        try {
            $data = $this->offers->historyPage($this->actor(), (int) $id);
        } catch (OfferEvaluationException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes'));
        }

        $this->dashboard('jobseeker/pages/resumes/offer-evaluation-history', [
            'title' => 'Offer Evaluation History',
            'activeNav' => 'resumes',
            'resume' => $data['resume'],
            'history' => $data['history'],
            'deletedHistory' => $data['deleted_history'],
            'versions' => $data['versions'],
            'version' => $data['version'],
            'resumeSection' => 'offer-evaluation',
        ]);
    }

    public function deleteHistory(string $id, string $historyId): void
    {
        try {
            $result = $this->offers->softDeleteHistory($this->actor(), (int) $id, (int) $historyId);
        } catch (OfferEvaluationException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/offer-evaluation/history'));
        }

        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/offer-evaluation/history',
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message']
        );
    }

    public function restoreHistory(string $id, string $historyId): void
    {
        try {
            $result = $this->offers->restoreHistory($this->actor(), (int) $id, (int) $historyId);
        } catch (OfferEvaluationException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/offer-evaluation/history'));
        }

        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/offer-evaluation/history',
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message']
        );
    }

    public function purgeHistory(string $id, string $historyId): void
    {
        try {
            $result = $this->offers->purgeHistory($this->actor(), (int) $id, (int) $historyId);
        } catch (OfferEvaluationException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/offer-evaluation/history'));
        }

        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/offer-evaluation/history',
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message']
        );
    }

    public function clearHistory(string $id): void
    {
        try {
            $result = $this->offers->clearHistory($this->actor(), (int) $id);
        } catch (OfferEvaluationException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/offer-evaluation/history'));
        }

        $this->flashRedirect(
            '/jobseeker/resumes/' . (int) $id . '/offer-evaluation/history',
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message']
        );
    }

    public function exportPdf(string $id, string $analysisId): void
    {
        try {
            $file = $this->offers->exportPdf($this->actor(), (int) $id, (int) $analysisId);
        } catch (OfferEvaluationException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/jobseeker/resumes/' . (int) $id . '/offer-evaluation'));
        }

        header('Content-Type: ' . $file['mime']);
        header('Content-Disposition: attachment; filename="' . $file['filename'] . '"');
        header('Content-Length: ' . (string) strlen($file['content']));
        header('Cache-Control: no-store');
        echo $file['content'];
        exit;
    }
}
