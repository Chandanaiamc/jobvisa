<?php

declare(strict_types=1);

/** @var array<string, mixed> $overview */
/** @var string $api_base */
?>
<section class="dp-hero">
    <p class="dp-badge">Enterprise API</p>
    <h1>JobVisa.lk</h1>
    <p>Build integrations on a versioned, token-secured JSON API — without changing the JobVisa web experience.</p>
    <div class="dp-actions">
        <a class="dp-btn dp-btn-primary" href="<?= e(url('/developers/getting-started')) ?>">Get started</a>
        <a class="dp-btn dp-btn-ghost" href="<?= e(url('/developers/sdk')) ?>">PHP SDK</a>
        <a class="dp-btn dp-btn-ghost" href="<?= e(url('/developers/tokens')) ?>">Manage tokens</a>
    </div>
</section>

<section class="dp-section">
    <h2>What you can build</h2>
    <p class="lead">Public job discovery, authenticated resume intelligence, and employer ranking — all behind the same envelope.</p>
    <div class="dp-grid">
        <div class="dp-panel">
            <h3>Versioned API</h3>
            <p>Stable <code>/api/v1</code> surface with room for a future v2.</p>
        </div>
        <div class="dp-panel">
            <h3>Personal access tokens</h3>
            <p>Hashed secrets, expiry, revocation, and last-used tracking.</p>
        </div>
        <div class="dp-panel">
            <h3>SDK foundation</h3>
            <p>A minimal PHP client that understands success/error envelopes and request IDs.</p>
        </div>
    </div>
</section>

<section class="dp-section">
    <h2>Quick health check</h2>
    <p class="lead">No token required.</p>
    <pre class="dp-code">GET <?= e($api_base) ?>/health</pre>
</section>
