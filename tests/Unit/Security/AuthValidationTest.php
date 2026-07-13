<?php

declare(strict_types=1);

namespace JobVisa\Tests\Unit\Security;

use JobVisa\App\Security\Validator;
use PHPUnit\Framework\TestCase;

final class AuthValidationTest extends TestCase
{
    public function testRegistrationRulesPass(): void
    {
        $validator = Validator::make([
            'full_name' => 'Demo User',
            'email' => 'demo@example.com',
            'password' => 'SecretPass!123',
            'password_confirmation' => 'SecretPass!123',
            'role' => 'seeker',
        ])
            ->required('full_name')
            ->required('email')->email('email')
            ->required('password')->min('password', 8)->confirmed('password')
            ->required('role')->in('role', ['seeker', 'employer']);

        $this->assertTrue($validator->passes());
    }

    public function testRegistrationRulesFailOnMismatch(): void
    {
        $validator = Validator::make([
            'email' => 'bad',
            'password' => 'short',
            'password_confirmation' => 'other',
            'role' => 'admin',
        ])
            ->required('email')->email('email')
            ->required('password')->min('password', 8)->confirmed('password')
            ->required('role')->in('role', ['seeker', 'employer']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors());
        $this->assertArrayHasKey('password', $validator->errors());
        $this->assertArrayHasKey('role', $validator->errors());
    }
}
