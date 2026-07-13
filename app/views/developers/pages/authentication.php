<?php

declare(strict_types=1);

/** @var string $api_base */
?>
<section class="dp-section">
    <h2>Authentication</h2>
    <p class="lead">API routes use personal access tokens. Browser portal pages keep session CSRF.</p>

    <div class="dp-panel">
        <h3>Header</h3>
        <pre class="dp-code">Authorization: Bearer jv1_&lt;secret&gt;</pre>
    </div>
    <div class="dp-panel">
        <h3>Security model</h3>
        <p>Only an HMAC-SHA256 hash is stored. Raw tokens are shown once at creation. Expired and revoked tokens return <code>401</code>.</p>
    </div>
    <div class="dp-panel">
        <h3>Roles</h3>
        <p>Seeker, employer, and admin permissions are enforced separately. Employer routes reject seeker tokens with <code>403</code>.</p>
    </div>
    <p><a class="dp-btn dp-btn-primary" href="<?= e(url('/developers/tokens')) ?>">Create a token</a></p>
</section>
