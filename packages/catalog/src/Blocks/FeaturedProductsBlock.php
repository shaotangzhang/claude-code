<?php

declare(strict_types=1);

namespace Acme\Catalog\Blocks;

use Acme\Catalog\Models\Product;
use Acme\CmsCore\Blocks\AbstractBlock;
use Acme\Contracts\Cms\RenderContext;

/**
 * Marquee-style product strip. data: { ids?: string[], limit?: int }
 * If ids is non-empty, renders exactly those products in that order;
 * otherwise picks N latest published.
 */
final class FeaturedProductsBlock extends AbstractBlock
{
    public static function key(): string { return 'catalog.featured'; }

    public static function label(): string { return 'Catalog · Featured products'; }

    public static function icon(): ?string { return 'star'; }

    public function render(array $data, RenderContext $context): string
    {
        $ids = (array) ($data['ids'] ?? []);
        $q   = Product::query()->with(['skus', 'images'])->published()->where('locale', $context->locale());

        if ($ids) {
            $q->whereIn('id', $ids);
            $products = $q->get()->sortBy(fn ($p) => array_search($p->id, $ids, true))->values();
        } else {
            $products = $q->orderByDesc('created_at')->limit((int) ($data['limit'] ?? 6))->get();
        }

        return $this->view('acme-catalog::blocks.featured', ['products' => $products], $context);
    }
}
