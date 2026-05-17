<?php

declare(strict_types=1);

namespace Acme\Search\Blocks;

use Acme\CmsCore\Blocks\AbstractBlock;
use Acme\Contracts\Cms\RenderContext;

/**
 * Header/sidebar search form. Renders a GET form pointing at /search.
 * data: { placeholder?: string }
 */
final class SearchBoxBlock extends AbstractBlock
{
    public static function key(): string { return 'search.box'; }

    public static function label(): string { return 'Search · Box'; }

    public static function icon(): ?string { return 'search'; }

    public function render(array $data, RenderContext $context): string
    {
        return $this->view('acme-search::blocks.box', [
            'placeholder' => (string) ($data['placeholder'] ?? 'Search products…'),
        ], $context);
    }
}
