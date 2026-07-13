<?php

declare(strict_types=1);

namespace JobVisa\Tests\Security;

use JobVisa\App\Http\Middleware\CsrfMiddleware;
use JobVisa\App\Security\Csrf;
use JobVisa\App\Security\SessionManager;
use PHPUnit\Framework\TestCase;

final class CsrfSecurityRegressionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        SessionManager::start();
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
        $_POST = [];
    }

    public function testPostWithoutTokenIsRejected(): void
    {
        $middleware = new CsrfMiddleware();
        ob_start();
        $result = $middleware->handle(static fn () => 'ok');
        $body = (string) ob_get_clean();

        $this->assertNull($result);
        $this->assertStringContainsString('CSRF', $body);
    }

    public function testPostWithValidTokenPasses(): void
    {
        $_POST['_token'] = Csrf::token();
        $middleware = new CsrfMiddleware();
        $this->assertSame('ok', $middleware->handle(static fn () => 'ok'));
    }
}
