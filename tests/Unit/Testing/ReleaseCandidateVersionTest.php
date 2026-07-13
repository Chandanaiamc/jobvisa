<?php

declare(strict_types=1);

namespace JobVisa\Tests\Unit\Testing;

use JobVisa\App\Domain\Testing\Support\ReleaseCandidateVersion;
use JobVisa\App\Domain\Testing\Support\RcChecklist;
use PHPUnit\Framework\TestCase;

final class ReleaseCandidateVersionTest extends TestCase
{
    public function testCurrentVersionIs490(): void
    {
        $this->assertSame('4.9.0', ReleaseCandidateVersion::CURRENT);
    }

    public function testChecklistHasRequiredCategories(): void
    {
        $items = RcChecklist::items();
        $this->assertNotEmpty($items);
        $categories = array_unique(array_column($items, 'category'));
        foreach (['meta', 'tests', 'gates', 'compat'] as $needed) {
            $this->assertContains($needed, $categories);
        }
    }
}
