<?php

declare(strict_types=1);

/** @var array<string, mixed> $user */
?>
<section class="panel">
    <h2 class="panel__title">Account settings</h2>
    <ul class="meta-list">
        <li><strong>Name:</strong> <?= e((string) ($user['full_name'] ?? '')) ?></li>
        <li><strong>Email:</strong> <?= e((string) ($user['email'] ?? '')) ?></li>
        <li><strong>Role:</strong> <?= e((string) ($user['role'] ?? '')) ?></li>
        <li><strong>Email verified:</strong> <?= !empty($user['email_verified_at']) ? 'Yes' : 'No' ?></li>
    </ul>
    <p class="muted">Password changes use the forgot-password flow. Authentication settings are managed separately.</p>
    <p><a class="btn btn--secondary" href="<?= e(app_url('/forgot-password')) ?>">Reset password</a></p>
</section>
