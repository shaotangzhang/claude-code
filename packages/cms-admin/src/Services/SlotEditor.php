<?php

declare(strict_types=1);

namespace Acme\CmsAdmin\Services;

use Acme\CmsCore\Models\Block;
use Acme\CmsCore\Models\PageVersion;
use Acme\Contracts\Cms\BlockRegistry;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The block editor's write API. Operates on a draft PageVersion only —
 * by convention the caller must never pass the currently-published version,
 * which is enforced by checking the parent Page.current_version_id.
 *
 * @phpstan-type BlockInput array{block_type:string,slot_key:string,position?:int,data:array<string,mixed>,locale?:?string}
 */
final class SlotEditor
{
    public function __construct(private readonly BlockRegistry $blocks) {}

    /**
     * @param  list<BlockInput>  $blocks  ordered list; ignores previous block rows.
     */
    public function replace(PageVersion $version, array $blocks): void
    {
        $this->assertDraft($version);
        $this->validate($blocks);

        DB::transaction(function () use ($version, $blocks): void {
            Block::where('page_version_id', $version->id)->delete();

            foreach ($blocks as $i => $b) {
                Block::create([
                    'page_version_id' => $version->id,
                    'slot_key'        => $b['slot_key'],
                    'position'        => $b['position'] ?? $i,
                    'block_type'      => $b['block_type'],
                    'data_json'       => $b['data'] ?? [],
                    'locale'          => $b['locale'] ?? null,
                ]);
            }
        });
    }

    /** @param list<BlockInput> $blocks */
    private function validate(array $blocks): void
    {
        $errors = [];
        foreach ($blocks as $i => $b) {
            if (! isset($b['block_type'], $b['slot_key'])) {
                $errors["#{$i}"][] = 'block_type and slot_key are required';

                continue;
            }
            if (! $this->blocks->has($b['block_type'])) {
                $errors["#{$i}"][] = "Unknown block type: {$b['block_type']}";

                continue;
            }
            $fieldErrors = $this->blocks->resolve($b['block_type'])->validate($b['data'] ?? []);
            if ($fieldErrors) {
                $errors["#{$i}"] = array_merge($errors["#{$i}"] ?? [], array_merge(...array_values($fieldErrors)));
            }
        }
        if ($errors) {
            throw new RuntimeException("Block validation failed: " . json_encode($errors));
        }
    }

    private function assertDraft(PageVersion $version): void
    {
        if ($version->page->current_version_id === $version->id) {
            throw new RuntimeException("Refusing to edit the currently-published version {$version->id}. Spawn a draft first.");
        }
    }
}
