<?php

declare(strict_types=1);

namespace JobVisa\Tests\Security;

use PHPUnit\Framework\TestCase;

final class XssEscapeRegressionTest extends TestCase
{
    public function testHelperEscapesHtml(): void
    {
        $this->assertSame('&lt;script&gt;', e('<script>'));
        $this->assertSame('a&amp;b', e('a&b'));
    }
}
