<?php

declare(strict_types=1);

namespace JobVisa\Tests\Performance;

use JobVisa\App\Cache\CacheInterface;
use JobVisa\App\Domain\Performance\Services\PerformanceHealthService;
use JobVisa\App\Domain\Performance\Support\PerformanceVersion;
use JobVisa\Tests\Support\ApplicationTestCase;

final class CachePerformanceRegressionTest extends ApplicationTestCase
{
    public function testPerformanceVersionIntact(): void
    {
        $this->assertSame('4.2.0', PerformanceVersion::CURRENT);
    }

    public function testCacheRoundTrip(): void
    {
        /** @var CacheInterface $cache */
        $cache = $this->container->get(CacheInterface::class);
        $key = 'rc.perf.' . bin2hex(random_bytes(4));
        $cache->put($key, ['v' => 1], 60);
        $this->assertTrue($cache->has($key));
        $this->assertSame(1, ($cache->get($key)['v'] ?? null));
        $cache->forget($key);
        $this->assertFalse($cache->has($key));
    }

    public function testPerformanceHealthStatusOk(): void
    {
        /** @var PerformanceHealthService $svc */
        $svc = $this->container->get(PerformanceHealthService::class);
        $status = $svc->status();
        $this->assertSame('ok', $status['status'] ?? null);
        $this->assertSame('4.2.0', $status['version'] ?? null);
    }
}
