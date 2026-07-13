<?php

declare(strict_types=1);

namespace JobVisa\Tests\Security;

use JobVisa\App\Domain\Security\Services\PasswordPolicy;
use PHPUnit\Framework\TestCase;

final class PasswordPolicyRegressionTest extends TestCase
{
    public function testDefaultPolicyAcceptsEightCharPassword(): void
    {
        $policy = new PasswordPolicy();
        $this->assertTrue($policy->passes('abcdefgh'));
    }

    public function testDefaultPolicyRejectsShortPassword(): void
    {
        $policy = new PasswordPolicy();
        $this->assertFalse($policy->passes('short'));
        $this->assertNotEmpty($policy->validate('short'));
    }
}
