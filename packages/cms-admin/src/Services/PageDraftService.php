<?php

declare(strict_types=1);

namespace Acme\CmsAdmin\Services;

use Acme\CmsAdmin\Events\PageDraftCreated;
use Acme\CmsCore\Models\Block;
use Acme\CmsCore\Models\Page;
use Acme\CmsCore\Models\PageVersion;
use Acme\Contracts\Auth\UserResolver;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Spawns a new editable PageVersion by cloning the current version's blocks.
 * Does NOT change Page.current_version_id — the draft is invisible to
 * front-end visitors until PagePublishService::publish is called.
 */
final class PageDraftService
{
    public function __construct(
        private readonly Dispatcher $events,
        private readonly UserResolver $users,
    ) {}

    public function createFrom(Page $page, ?string $note = null): PageVersion
    {
        return DB::transaction(function () use ($page, $note): PageVersion {
            $source = $page->currentVersion;

            $draft = PageVersion::create([
                'page_id'       => $page->id,
                'author_id'     => $this->users->currentUserId(),
                'snapshot_json' => $source?->snapshot_json ?? [],
                'note'          => $note ?? 'Draft',
                'created_at'    => now(),
            ]);

            if ($source) {
                foreach ($source->blocks as $b) {
                    Block::create([
                        'page_version_id' => $draft->id,
                        'slot_key'        => $b->slot_key,
                        'position'        => $b->position,
                        'block_type'      => $b->block_type,
                        'data_json'       => $b->data_json,
                        'locale'          => $b->locale,
                    ]);
                }
            }

            $this->events->dispatch(new PageDraftCreated(
                pageId:    $page->id,
                versionId: $draft->id,
                authorId:  $this->users->currentUserId(),
            ));

            return $draft;
        });
    }

    public function discard(PageVersion $draft): void
    {
        if ($draft->page->current_version_id === $draft->id) {
            throw new RuntimeException("Cannot discard the currently-published version.");
        }
        $draft->delete();
    }
}
