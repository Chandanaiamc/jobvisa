<?php

declare(strict_types=1);

namespace JobVisa\Tests\Api;

use JobVisa\App\Domain\Api\Auth\PersonalAccessTokenService;
use JobVisa\App\Domain\Api\RateLimit\ApiRateLimiter;
use JobVisa\App\Providers\RouteServiceProvider;
use JobVisa\App\Routing\RouteRegistrar;
use JobVisa\Tests\Support\ApplicationTestCase;

final class ApiHardeningTest extends ApplicationTestCase
{
    public function testOpenApiIncludesRevokeAndDocsPaths(): void
    {
        $doc = json_decode((string) file_get_contents(base_path('docs/05-api/openapi.json')), true);
        $this->assertIsArray($doc);
        $this->assertArrayHasKey('/tokens/{token}/revoke', $doc['paths']);
        $this->assertArrayHasKey('/docs/openapi', $doc['paths']);
    }

    public function testRegisteredApiRoutesHaveOpenApiEntries(): void
    {
        $provider = new RouteServiceProvider($this->container);
        $provider->loadRoutes();
        /** @var RouteRegistrar $registrar */
        $registrar = $this->container->get(RouteRegistrar::class);
        $map = $registrar->routeMiddlewareMap();
        $doc = json_decode((string) file_get_contents(base_path('docs/05-api/openapi.json')), true);
        $this->assertIsArray($doc);

        foreach (['GET', 'POST'] as $method) {
            foreach (array_keys($map[$method] ?? []) as $uri) {
                if (!str_starts_with((string) $uri, '/api/v1')) {
                    continue;
                }
                $path = substr((string) $uri, strlen('/api/v1'));
                $this->assertArrayHasKey($path, $doc['paths'], $method . ' ' . $uri);
            }
        }
    }

    public function testJobseekerMiddlewareOnResumeRoutes(): void
    {
        $provider = new RouteServiceProvider($this->container);
        $provider->loadRoutes();
        /** @var RouteRegistrar $registrar */
        $registrar = $this->container->get(RouteRegistrar::class);
        $map = $registrar->routeMiddlewareMap();
        $mw = $map['GET']['/api/v1/resumes'] ?? [];
        $this->assertContains('api.auth', $mw);
        $this->assertContains('api.jobseeker', $mw);
    }

    public function testTokenExpiryUtcSemantics(): void
    {
        /** @var PersonalAccessTokenService $tokens */
        $tokens = $this->container->get(PersonalAccessTokenService::class);
        $this->assertTrue($tokens->isExpired(gmdate('Y-m-d H:i:s', time() - 60)));
        $this->assertFalse($tokens->isExpired(gmdate('Y-m-d H:i:s', time() + 3600)));
        $this->assertFalse($tokens->isExpired(null));
    }

    public function testRateLimitBeginRequestClearsMemo(): void
    {
        ApiRateLimiter::beginRequest();
        $ref = new \ReflectionClass(ApiRateLimiter::class);
        $prop = $ref->getProperty('hitThisRequest');
        $prop->setAccessible(true);
        $prop->setValue(null, ['ip:x' => ['attempts' => 9, 'reset_at' => time() + 30]]);
        ApiRateLimiter::beginRequest();
        $this->assertSame([], $prop->getValue());
    }

    public function testTokenHashUsesPepperDeterministicallyInLocal(): void
    {
        /** @var PersonalAccessTokenService $tokens */
        $tokens = $this->container->get(PersonalAccessTokenService::class);
        $a = $tokens->hash('jv1_deadbeef');
        $b = $tokens->hash('jv1_deadbeef');
        $this->assertSame($a, $b);
        $this->assertSame(64, strlen($a));
    }
}
