<?php

declare(strict_types=1);

namespace Acme\CmsAdmin\Services;

use Acme\CmsAdmin\Events\PageRolledBack;
use Acme\CmsCore\Models\Page;
use Acme\CmsCore\Models\PageVersion;
use Acme\Contracts\Auth\UserResolver;
use Illuminate\Contracts\Events\Dispatcher;
use RuntimeException;

/**
 * Restore a historical version by re-pointing Page.current_version_id.
 * The old version data is preserved on disk; rollback is a pointer move,
 * not a destructive operation. Idempotent.
 */
final class PageRollbackService
{
    public function __construct(
        private readonly Dispatcher $events,
        private readonly UserResolver $users,
    ) {}

    public function restore(Page $page, PageVersion $target): void
    {
        if ($target->page_id !== $page->id) {
            throw new RuntimeException("Version {$target->id} does not belong to page {$page->id}.");
        }

        $previous = $page->current_version_id;
        if ($previous === $target->id) {
            return;
        }

        $page->current_version_id = $target->id;
        $page->status             = Page::STATUS_PUBLISHED;
        $page->publish_at         = null;
        $page->save();

        $this->events->dispatch(new PageRolledBack(
            pageId:        $page->id,
            fromVersionId: (string) $previous,
            toVersionId:   $target->id,
            authorId:      $this->users->currentUserId(),
        ));
    }
}
