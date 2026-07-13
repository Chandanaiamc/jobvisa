<?php

declare(strict_types=1);

namespace App\Controllers\Employer;

use JobVisa\App\Domain\EmployerDashboard\Exceptions\EmployerDashboardException;
use JobVisa\App\Domain\EmployerDashboard\Services\EmployerAiDashboardService;
use JobVisa\App\Security\SessionManager;

/**
 * Employer AI hiring dashboard (Sprint 2F.5).
 */
final class AiDashboardController extends EmployerController
{
    private EmployerAiDashboardService $aiDashboard;

    public function __construct()
    {
        parent::__construct();
        $this->aiDashboard = container(EmployerAiDashboardService::class);
    }

    public function show(): void
    {
        try {
            $data = $this->aiDashboard->page($this->actor(), false);
        } catch (EmployerDashboardException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/login'));
        }

        $this->dashboard('employer/pages/ai-dashboard', [
            'title' => 'AI Hiring Dashboard',
            'activeNav' => 'overview',
            'dash' => $data['dashboard'],
            'canRefresh' => $data['can_refresh'],
            'disclaimer' => $data['disclaimer'],
        ]);
    }

    public function refresh(): void
    {
        try {
            $result = $this->aiDashboard->refresh($this->actor());
        } catch (EmployerDashboardException $e) {
            SessionManager::flash('error', $e->getMessage());
            redirect(app_url('/login'));
        }

        $type = ($result['success'] ?? false) ? 'success' : 'error';
        $this->flashRedirect('/employer', $type, $result['message']);
    }
}
