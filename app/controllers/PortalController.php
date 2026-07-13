<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use JobVisa\App\Auth\AuthManager;

/**
 * Simple protected portal placeholders (Sprint 2A/2B).
 */
final class PortalController extends Controller
{
    public function admin(): void
    {
        $this->portal('Admin', 'admin');
    }

    public function employer(): void
    {
        $this->portal('Employer', 'employer');
    }

    public function jobseeker(): void
    {
        $this->portal('Job Seeker', 'jobseeker');
    }

    private function portal(string $label, string $area): void
    {
        /** @var AuthManager $auth */
        $auth = container(AuthManager::class);
        $user = $auth->user();

        $verified = is_array($user) && !empty($user['email_verified_at']);
        $role = is_array($user) ? (string) ($user['role'] ?? '') : '';

        $this->render('portal/placeholder', [
            'title' => $label . ' Dashboard',
            'area' => $area,
            'label' => $label,
            'userName' => is_array($user) ? (string) ($user['full_name'] ?? $user['email'] ?? 'User') : 'User',
            'userEmail' => is_array($user) ? (string) ($user['email'] ?? '') : '',
            'userRole' => $role !== '' ? $role : 'unknown',
            'emailVerified' => $verified,
        ]);
    }
}
