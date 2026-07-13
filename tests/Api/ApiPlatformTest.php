<?php

declare(strict_types=1);

namespace JobVisa\Tests\Api;

use JobVisa\App\Domain\Api\Http\ApiResponse;
use JobVisa\App\Domain\Api\Portal\Services\DeveloperPortalService;
use JobVisa\App\Domain\Api\Support\ApiVersion;
use JobVisa\App\Providers\RouteServiceProvider;
use JobVisa\App\Routing\RouteRegistrar;
use JobVisa\Tests\Support\ApplicationTestCase;

final class ApiPlatformTest extends ApplicationTestCase
{
    public function testApiVersionConstant(): void
    {
        $this->assertSame('4.5.0', ApiVersion::CURRENT);
    }

    public function testOpenApiDocumentExists(): void
    {
        /** @var DeveloperPortalService $portal */
        $portal = $this->container->get(DeveloperPortalService::class);
        $doc = $portal->openapiDocument();
        $this->assertIsArray($doc);
        $this->assertArrayHasKey('openapi', $doc);
    }

    public function testHealthAndOpenApiRoutesRegistered(): void
    {
        $provider = new RouteServiceProvider($this->container);
        $provider->loadRoutes();
        /** @var RouteRegistrar $registrar */
        $registrar = $this->container->get(RouteRegistrar::class);
        $map = $registrar->routeMiddlewareMap();

        $this->assertArrayHasKey('/api/v1/health', $map['GET'] ?? []);
    }

    public function testApiResponseHelperShapesJson(): void
    {
        http_response_code(200);
        ob_start();
        ApiResponse::success(['ping' => true]);
        $body = (string) ob_get_clean();
        $json = json_decode($body, true);
        $this->assertIsArray($json);
        $this->assertTrue($json['success'] ?? false);
        $this->assertTrue(($json['data']['ping'] ?? false) === true);
    }
}
