<?php

declare(strict_types=1);

namespace App\Controllers\Employer;

use JobVisa\App\Domain\InterviewAssistant\Exceptions\InterviewAssistantException;
use JobVisa\App\Domain\InterviewAssistant\Services\InterviewAssistantService;
use JobVisa\App\Security\SessionManager;

/**
 * AI Interview Assistant (Sprint 2F.7).
 */
final class InterviewAssistantController extends EmployerController
{
    private InterviewAssistantService $assistant;

    public function __construct()
    {
        parent::__construct();
        $this->assistant = container(InterviewAssistantService::class);
    }

    public function show(): void
    {
        $jobId = isset($_GET['job']) ? (int) $_GET['job'] : null;
        if ($jobId !== null && $jobId < 1) {
            $jobId = null;
        }

        try {
            $data = $this->assistant->page($this->actor(), $jobId);
        } catch (InterviewAssistantException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/employer'));
        }

        $this->dashboard('employer/pages/interview-assistant', [
            'title' => 'AI Interview Assistant',
            'activeNav' => 'interview',
            'jobs' => $data['jobs'],
            'selectedJob' => $data['selected_job'],
            'candidates' => $data['candidates'],
            'history' => $data['history'],
            'version' => $data['version'],
            'disclaimer' => $data['disclaimer'],
        ]);
    }

    public function generate(): void
    {
        $applicationId = (int) ($_POST['application_id'] ?? 0);

        try {
            $result = $this->assistant->generate($this->actor(), $applicationId);
        } catch (InterviewAssistantException $e) {
            SessionManager::flash('error', $e->getMessage());
            $jobId = (int) ($_POST['job_id'] ?? 0);
            redirect(app_url('/employer/interview-assistant' . ($jobId > 0 ? '?job=' . $jobId : '')));
        }

        $this->flashRedirect(
            '/employer/interview-assistant/sessions/' . (int) $result['session_id'],
            'success',
            $result['message'] ?? 'Interview pack ready.'
        );
    }

    public function session(string $sessionId): void
    {
        try {
            $data = $this->assistant->showSession($this->actor(), (int) $sessionId);
        } catch (InterviewAssistantException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/employer/interview-assistant'));
        }

        $this->dashboard('employer/pages/interview-session', [
            'title' => 'Interview session',
            'activeNav' => 'interview',
            'session' => $data['session'],
            'version' => $data['version'],
            'disclaimer' => $data['disclaimer'],
        ]);
    }

    public function saveScorecard(string $sessionId): void
    {
        try {
            $result = $this->assistant->saveScorecard($this->actor(), (int) $sessionId, $_POST);
        } catch (InterviewAssistantException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/employer/interview-assistant/sessions/' . (int) $sessionId));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect(
            '/employer/interview-assistant/sessions/' . (int) $sessionId,
            $type,
            $result['message']
        );
    }

    public function deleteHistory(string $sessionId): void
    {
        try {
            $result = $this->assistant->softDeleteHistory($this->actor(), (int) $sessionId);
        } catch (InterviewAssistantException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/employer/interview-assistant'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/employer/interview-assistant', $type, $result['message']);
    }

    public function clearHistory(): void
    {
        try {
            $result = $this->assistant->clearHistory($this->actor());
        } catch (InterviewAssistantException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/employer/interview-assistant'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/employer/interview-assistant', $type, $result['message']);
    }
}
