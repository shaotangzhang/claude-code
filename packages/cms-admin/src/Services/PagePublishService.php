<?php

declare(strict_types=1);

namespace Acme\CmsAdmin\Services;

use Acme\CmsAdmin\Events\PagePublished;
use Acme\CmsCore\Models\Page;
use Acme\CmsCore\Models\PageVersion;
use Acme\Contracts\Auth\UserResolver;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Publish a specific PageVersion. Optionally schedule it for a future time
 * (status=scheduled). The actual switch to "published" at the scheduled
 * moment happens by querying status+publish_at; no cron required for that
 * read path, though a cron can transition the status label if you want one.
 */
final class PagePublishService
{
    public function __construct(
        private readonly Dispatcher $events,
        private readonly UserResolver $users,
    ) {}

    public function publish(Page $page, PageVersion $version, ?CarbonInterface $at = null): void
    {
        if ($version->page_id !== $page->id) {
            throw new RuntimeException("Version {$version->id} does not belong to page {$page->id}.");
        }

        $snapshot = $this->renderSnapshot($version);

        DB::transaction(function () use ($page, $version, $snapshot, $at): void {
            $version->snapshot_json = $snapshot;
            $version->save();

            $page->current_version_id = $version->id;
            $page->publish_at         = $at;
            $page->status             = $at && $at->isFuture()
                ? Page::STATUS_SCHEDULED
                : Page::STATUS_PUBLISHED;
            $page->save();
        });

        $this->events->dispatch(new PagePublished(
            pageId:    $page->id,
            versionId: $version->id,
            authorId:  $this->users->currentUserId(),
            scheduled: $page->status === Page::STATUS_SCHEDULED,
            publishAt: $at?->toIso8601String(),
        ));
    }

    /**
     * Denormalize a version's blocks into snapshot_json so the read path
     * can survive a corrupted blocks table without re-querying.
     */
    private function renderSnapshot(PageVersion $version): array
    {
        return [
            'version_id' => $version->id,
            'page_id'    => $version->page_id,
            'blocks'     => $version->blocks->map(fn ($b) => [
                'slot_key'   => $b->slot_key,
                'position'   => $b->position,
                'block_type' => $b->block_type,
                'data'       => $b->data_json,
                'locale'     => $b->locale,
            ])->values()->toArray(),
            'compiled_at' => now()->toIso8601String(),
        ];
    }
}
