<?php

declare(strict_types=1);

/** @var string $api_base */
?>
<section class="dp-section">
    <h2>Getting started</h2>
    <p class="lead">Three steps from zero to an authenticated call.</p>

    <div class="dp-panel">
        <h3>1. Create a token</h3>
        <p>Sign in and visit <a href="<?= e(url('/developers/tokens')) ?>">API tokens</a>, or call <code>POST /api/v1/tokens</code>.</p>
    </div>
    <div class="dp-panel">
        <h3>2. Call with Bearer auth</h3>
        <pre class="dp-code">curl -H "Authorization: Bearer jv1_…" \
     -H "Accept: application/json" \
     <?= e($api_base) ?>/me</pre>
    </div>
    <div class="dp-panel">
        <h3>3. Read the envelope</h3>
        <pre class="dp-code">{
  "success": true,
  "data": { },
  "meta": { },
  "request_id": "…"
}</pre>
    </div>
</section>
