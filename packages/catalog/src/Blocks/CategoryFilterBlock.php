<?php

declare(strict_types=1);

namespace Acme\Catalog\Blocks;

use Acme\Catalog\Models\Brand;
use Acme\Catalog\Models\Category;
use Acme\CmsCore\Blocks\AbstractBlock;
use Acme\Contracts\Cms\RenderContext;

/**
 * Sidebar filter widget that pairs with ProductGridBlock by writing query
 * strings to the same page URL. data: { show_brands?, show_price_buckets? }
 */
final class CategoryFilterBlock extends AbstractBlock
{
    public static function key(): string { return 'catalog.category-filter'; }

    public static function label(): string { return 'Catalog · Category filter'; }

    public static function icon(): ?string { return 'filter'; }

    public function render(array $data, RenderContext $context): string
    {
        $locale = $context->locale();

        $categories = Category::query()->where('locale', $locale)->whereNull('parent_id')
            ->with('children')->orderBy('position')->get();

        $brands = ($data['show_brands'] ?? true)
            ? Brand::query()->where('locale', $locale)->orderBy('name')->get()
            : collect();

        return $this->view('acme-catalog::blocks.category-filter', [
            'categories'         => $categories,
            'brands'             => $brands,
            'show_price_buckets' => (bool) ($data['show_price_buckets'] ?? false),
        ], $context);
    }
}
