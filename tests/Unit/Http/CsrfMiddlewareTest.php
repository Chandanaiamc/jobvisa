<?php

declare(strict_types=1);

namespace JobVisa\Tests\Unit\Http;

use JobVisa\App\Http\Middleware\CsrfMiddleware;
use JobVisa\App\Http\Middleware\StartSessionMiddleware;
use JobVisa\App\Security\Csrf;
use JobVisa\App\Security\SessionManager;
use PHPUnit\Framework\TestCase;

final class CsrfMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        SessionManager::start();
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
        $_POST = [];
    }

    public function testRejectsMissingToken(): void
    {
        $middleware = new CsrfMiddleware();
        ob_start();
        $result = $middleware->handle(static fn () => 'ok');
        $body = ob_get_clean();

        $this->assertNull($result);
        $this->assertStringContainsString('CSRF token mismatch', (string) $body);
    }

    public function testAcceptsValidToken(): void
    {
        $token = Csrf::token();
        $_POST['_token'] = $token;

        $middleware = new CsrfMiddleware();
        $result = $middleware->handle(static fn () => 'ok');

        $this->assertSame('ok', $result);
    }

    public function testStartSessionMiddlewarePasses(): void
    {
        $middleware = new StartSessionMiddleware();
        $result = $middleware->handle(static fn () => 'started');

        $this->assertSame('started', $result);
    }
}
