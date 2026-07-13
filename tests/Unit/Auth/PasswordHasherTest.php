<?php

declare(strict_types=1);

namespace JobVisa\Tests\Unit\Auth;

use JobVisa\App\Auth\PasswordHasher;
use PHPUnit\Framework\TestCase;

final class PasswordHasherTest extends TestCase
{
    public function testHashAndVerify(): void
    {
        $hasher = new PasswordHasher();
        $hash = $hasher->hash('SecretPass!123');

        $this->assertNotSame('SecretPass!123', $hash);
        $this->assertTrue($hasher->verify('SecretPass!123', $hash));
        $this->assertFalse($hasher->verify('wrong', $hash));
    }
}
