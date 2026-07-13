<?php

declare(strict_types=1);
?>
<section class="dp-section">
    <h2>Errors &amp; rate limits</h2>
    <p class="lead">Every error is JSON. Production never returns stack traces on <code>/api/*</code>.</p>

    <table class="dp-table">
        <thead>
        <tr><th>Code</th><th>HTTP</th><th>Meaning</th></tr>
        </thead>
        <tbody>
        <tr><td><code>unauthorized</code></td><td>401</td><td>Missing/invalid token</td></tr>
        <tr><td><code>token_expired</code> / <code>token_revoked</code></td><td>401</td><td>Token lifecycle</td></tr>
        <tr><td><code>forbidden</code></td><td>403</td><td>Role / ownership</td></tr>
        <tr><td><code>not_found</code></td><td>404</td><td>Missing resource (IDOR-safe)</td></tr>
        <tr><td><code>validation_error</code></td><td>422</td><td>Structured field details</td></tr>
        <tr><td><code>rate_limited</code></td><td>429</td><td>See <code>Retry-After</code></td></tr>
        </tbody>
    </table>

    <div class="dp-panel">
        <h3>Rate-limit headers</h3>
        <pre class="dp-code">X-RateLimit-Limit
X-RateLimit-Remaining
X-RateLimit-Reset
Retry-After</pre>
    </div>
</section>
