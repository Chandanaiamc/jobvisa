<?php

declare(strict_types=1);

/**
 * Skip to main content (Sprint 4.8).
 * Target landmark: #main
 */

if (!(bool) config('frontend.skip_link', true) || !(bool) config('frontend.enabled', true)) {
    return;
}
?>
<a class="skip-link" href="#main">Skip to main content</a>
