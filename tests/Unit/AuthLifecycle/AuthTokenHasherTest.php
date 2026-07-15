<?php

declare(strict_types=1);

namespace JobVisa\Tests\Unit\AuthLifecycle;

use JobVisa\App\Domain\Auth\Support\AuthTokenHasher;
use JobVisa\App\Domain\Auth\Support\AuthTokenLifecycleVersion;
use PHPUnit\Framework\TestCase;

final class AuthTokenHasherTest extends TestCase
{
    public function testVersionConstant(): void
    {
        $this->assertSame('2.1.0', AuthTokenLifecycleVersion::CURRENT);
    }

    public function testHashIsDeterministicAndHex(): void
    {
        $h = new AuthTokenHasher();
        $a = $h->hash('jvr1_abc');
        $b = $h->hash('jvr1_abc');
        $this->assertSame($a, $b);
        $this->assertSame(64, strlen($a));
        $this->assertNotSame($a, $h->hash('jvr1_other'));
    }

    public function testUtcExpirySemantics(): void
    {
        $h = new AuthTokenHasher();
        $this->assertTrue($h->isExpiredUtc(gmdate('Y-m-d H:i:s', time() - 30)));
        $this->assertFalse($h->isExpiredUtc(gmdate('Y-m-d H:i:s', time() + 600)));
        $this->assertFalse($h->isExpiredUtc(null));
    }

    public function testFamilyIdFormat(): void
    {
        $h = new AuthTokenHasher();
        $id = $h->familyId();
        $this->assertMatchesRegularExpression('/^[a-f0-9\-]{32,36}$/i', $id);
    }

    public function testUtcPlusHelpers(): void
    {
        $h = new AuthTokenHasher();
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $h->utcPlusSeconds(120));
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $h->utcPlusDays(2));
    }
}
