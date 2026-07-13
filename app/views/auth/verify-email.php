<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var array<string, list<string>> $errors
 * @var array<string, mixed>|null $user
 * @var string $email
 */

$contentView = 'auth/verify-email-form';
require base_path('app/views/auth/layout.php');
