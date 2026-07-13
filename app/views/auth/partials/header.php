<?php

declare(strict_types=1);
?>
<header class="auth-header">
    <div class="auth-header__inner">
        <a class="auth-brand" href="<?= e(app_url('/')) ?>" aria-label="JobVisa.lk home">
            <span class="auth-brand__mark" aria-hidden="true">JV</span>
            <span class="auth-brand__text">
                <strong>JobVisa</strong><span>.lk</span>
            </span>
        </a>
        <nav class="auth-header__nav" aria-label="Account">
            <a href="<?= e(app_url('/login')) ?>">Sign in</a>
            <a class="auth-header__cta" href="<?= e(app_url('/register')) ?>">Register</a>
        </nav>
    </div>
</header>
