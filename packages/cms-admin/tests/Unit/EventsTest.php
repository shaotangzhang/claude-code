<?php

declare(strict_types=1);

namespace Acme\CmsAdmin\Tests\Unit;

use Acme\CmsAdmin\Events\PageDraftCreated;
use Acme\CmsAdmin\Events\PagePublished;
use Acme\CmsAdmin\Events\PageRolledBack;
use Acme\CmsAdmin\Events\ThemeActivated;
use PHPUnit\Framework\TestCase;

final class EventsTest extends TestCase
{
    public function test_events_are_immutable_value_objects(): void
    {
        $draft = new PageDraftCreated('p1', 'v1', 'u1');
        $this->assertSame('p1', $draft->pageId);
        $this->assertSame('v1', $draft->versionId);

        $pub = new PagePublished('p1', 'v1', 'u1', scheduled: true, publishAt: '2027-01-01T00:00:00Z');
        $this->assertTrue($pub->scheduled);
        $this->assertSame('2027-01-01T00:00:00Z', $pub->publishAt);

        $back = new PageRolledBack('p1', 'v2', 'v1', 'u1');
        $this->assertSame('v2', $back->fromVersionId);
        $this->assertSame('v1', $back->toVersionId);

        $theme = new ThemeActivated('dark', 'light', 'u1');
        $this->assertSame('dark', $theme->themeKey);
        $this->assertSame('light', $theme->previousThemeKey);
    }
}
