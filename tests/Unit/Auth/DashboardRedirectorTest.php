<?php

declare(strict_types=1);

namespace JobVisa\Tests\Unit\Auth;

use JobVisa\App\Auth\DashboardRedirector;
use PHPUnit\Framework\TestCase;

final class DashboardRedirectorTest extends TestCase
{
    public function testSeekerDashboard(): void
    {
        $redirector = new DashboardRedirector();
        $result = $redirector->forUser(['role' => 'seeker']);

        $this->assertSame('/jobseeker', $result['path']);
        $this->assertSame('seeker', $result['role']);
        $this->assertStringContainsString('/jobseeker', $result['url']);
    }

    public function testEmployerDashboard(): void
    {
        $redirector = new DashboardRedirector();
        $result = $redirector->forUser(['role' => 'employer']);

        $this->assertSame('/employer', $result['path']);
    }

    public function testAdminDashboard(): void
    {
        $redirector = new DashboardRedirector();
        $result = $redirector->forUser(['role' => 'admin']);

        $this->assertSame('/admin', $result['path']);
    }

    public function testDefaultWhenUnknown(): void
    {
        $redirector = new DashboardRedirector();
        $result = $redirector->forUser(['role' => 'unknown']);

        $this->assertSame('/', $result['path']);
    }
}
