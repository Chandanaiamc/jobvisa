<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var array<string, list<string>> $errors
 * @var array{first_name: string, last_name: string, email: string, phone: string, role: string, terms: string} $old
 */

$contentView = 'auth/register-form';
require base_path('app/views/auth/layout.php');
