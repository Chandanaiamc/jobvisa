<?php

declare(strict_types=1);

/** @var string $api_base */
/** @var bool $sdk_enabled */
?>
<section class="dp-section">
    <h2>PHP SDK foundation</h2>
    <p class="lead">A thin client that speaks the JobVisa envelope. Source: <code>sdk/php</code> and <code>JobVisa\App\Domain\Api\Sdk\JobVisaClient</code>.</p>

    <?php if (!$sdk_enabled): ?>
        <div class="dp-alert dp-alert-warn">SDK examples are disabled via configuration.</div>
    <?php endif; ?>

    <pre class="dp-code">use JobVisa\App\Domain\Api\Sdk\JobVisaClient;

$client = new JobVisaClient('<?= e($api_base) ?>', getenv('JOBVISA_API_TOKEN'));
$health = $client->health();
$me = $client->get('/me');

if ($me['ok']) {
    echo $me['body']['data']['user']['email'];
}</pre>

    <div class="dp-panel">
        <h3>Guarantees</h3>
        <p>Propagates <code>X-Request-Id</code>, parses JSON envelopes, and surfaces HTTP status for retries on <code>429</code>.</p>
    </div>
</section>
