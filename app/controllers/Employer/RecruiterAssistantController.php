<?php

declare(strict_types=1);

namespace App\Controllers\Employer;

use JobVisa\App\Domain\RecruiterAssistant\Exceptions\RecruiterAssistantException;
use JobVisa\App\Domain\RecruiterAssistant\Services\RecruiterAssistantService;
use JobVisa\App\Security\SessionManager;

/**
 * AI Recruiter Assistant (Sprint 2F.6).
 */
final class RecruiterAssistantController extends EmployerController
{
    private RecruiterAssistantService $assistant;

    public function __construct()
    {
        parent::__construct();
        $this->assistant = container(RecruiterAssistantService::class);
    }

    public function show(): void
    {
        $prefill = isset($_GET['q']) ? trim((string) $_GET['q']) : null;
        if ($prefill === '') {
            $prefill = null;
        }

        try {
            $data = $this->assistant->page($this->actor(), $prefill);
        } catch (RecruiterAssistantException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/employer'));
        }

        $this->dashboard('employer/pages/recruiter-assistant', [
            'title' => 'AI Recruiter Assistant',
            'activeNav' => 'recruiter',
            'jobs' => $data['jobs'],
            'criteria' => $data['criteria'],
            'results' => $data['results'],
            'suggestions' => $data['suggestions'],
            'history' => $data['history'],
            'version' => $data['version'],
            'disclaimer' => $data['disclaimer'],
            'query' => $prefill ?? '',
        ]);
    }

    public function search(): void
    {
        $query = trim((string) ($_POST['q'] ?? ''));

        try {
            $result = $this->assistant->search($this->actor(), $query);
        } catch (RecruiterAssistantException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/employer/recruiter-assistant'));
        }

        $this->flashRedirect(
            '/employer/recruiter-assistant?q=' . rawurlencode($query),
            'success',
            $result['message'] ?? 'Search complete.'
        );
    }

    public function deleteHistory(string $historyId): void
    {
        try {
            $result = $this->assistant->softDeleteHistory($this->actor(), (int) $historyId);
        } catch (RecruiterAssistantException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/employer/recruiter-assistant'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/employer/recruiter-assistant', $type, $result['message']);
    }

    public function clearHistory(): void
    {
        try {
            $result = $this->assistant->clearHistory($this->actor());
        } catch (RecruiterAssistantException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/employer/recruiter-assistant'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/employer/recruiter-assistant', $type, $result['message']);
    }
}
