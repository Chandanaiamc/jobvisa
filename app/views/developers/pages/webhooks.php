<?php

declare(strict_types=1);

/** @var list<string> $events */
?>
<section class="dp-section">
    <h2>Webhooks</h2>
    <p class="lead">Foundation for outbound events. Disabled until <code>API_WEBHOOKS_ENABLED=true</code> and subscriptions are configured.</p>

    <div class="dp-panel">
        <h3>Events</h3>
        <ul>
            <?php foreach ($events as $event): ?>
                <li><code><?= e($event) ?></code></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="dp-panel">
        <h3>Signature</h3>
        <pre class="dp-code">X-JobVisa-Signature: sha256=&lt;hmac_sha256(body, secret)&gt;
X-JobVisa-Event: job.applied</pre>
    </div>
</section>
