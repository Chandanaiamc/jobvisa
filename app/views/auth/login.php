<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var array<string, list<string>> $errors
 * @var array{email: string, remember: string} $old
 */

$contentView = 'auth/login-form';
require base_path('app/views/auth/layout.php');
