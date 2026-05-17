<?php

declare(strict_types=1);

namespace Acme\CmsCore\Rendering;

use Acme\CmsCore\Models\PageVersion;
use Acme\Contracts\Cms\BlockRegistry;
use Acme\Contracts\Cms\RenderContext;

final class SlotRenderer
{
    public function __construct(private readonly BlockRegistry $registry) {}

    public function render(PageVersion $version, string $slotKey, RenderContext $ctx): string
    {
        $blocks = $version->blocks->where('slot_key', $slotKey)->sortBy('position');
        $html   = '';

        foreach ($blocks as $block) {
            if (! $this->registry->has($block->block_type)) {
                $html .= "<!-- missing block type: {$block->block_type} -->";

                continue;
            }
            $type = $this->registry->resolve($block->block_type);
            $html .= $type->render((array) $block->data_json, $ctx);
        }

        return $html;
    }

    /** @return array<string,string>  slot_key => rendered html */
    public function renderAll(PageVersion $version, RenderContext $ctx): array
    {
        $out = [];
        foreach ($version->blocks->groupBy('slot_key') as $slotKey => $_) {
            $out[$slotKey] = $this->render($version, (string) $slotKey, $ctx);
        }

        return $out;
    }
}
