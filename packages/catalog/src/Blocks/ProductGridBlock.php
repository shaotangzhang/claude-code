<?php

declare(strict_types=1);

namespace Acme\Catalog\Blocks;

use Acme\Catalog\Models\Brand;
use Acme\Catalog\Models\Category;
use Acme\Catalog\Models\Product;
use Acme\CmsCore\Blocks\AbstractBlock;
use Acme\Contracts\Cms\RenderContext;

/**
 * Paginated product grid, filterable. data:
 *   { category_slug?, brand_slug?, min_price_cents?, max_price_cents?,
 *     per_page?, order?: 'newest'|'price-asc'|'price-desc' }
 *
 * Filters can also come from query string (?category=...&brand=...) so the
 * same block instance works for both static landing pages and dynamic listings.
 */
final class ProductGridBlock extends AbstractBlock
{
    public static function key(): string { return 'catalog.product-grid'; }

    public static function label(): string { return 'Catalog · Product grid'; }

    public static function icon(): ?string { return 'grid'; }

    public function render(array $data, RenderContext $context): string
    {
        $perPage = max(1, min(96, (int) ($data['per_page'] ?? config('acme.catalog.grid_per_page', 24))));
        $locale  = $context->locale();
        $req     = request();

        $q = Product::query()->with(['brand', 'category', 'skus', 'images'])->published()->where('locale', $locale);

        $catSlug = (string) ($data['category_slug'] ?? $req->query('category', ''));
        if ($catSlug !== '') {
            $cat = Category::query()->where('locale', $locale)->where('slug', $catSlug)->first();
            if ($cat) {
                $q->where('category_id', $cat->id);
            }
        }

        $brandSlug = (string) ($data['brand_slug'] ?? $req->query('brand', ''));
        if ($brandSlug !== '') {
            $brand = Brand::query()->where('locale', $locale)->where('slug', $brandSlug)->first();
            if ($brand) {
                $q->where('brand_id', $brand->id);
            }
        }

        $min = (int) ($data['min_price_cents'] ?? $req->query('min', 0));
        $max = (int) ($data['max_price_cents'] ?? $req->query('max', 0));
        if ($min > 0 || $max > 0) {
            $q->whereHas('skus', function ($sq) use ($min, $max): void {
                if ($min > 0) { $sq->where('price_cents', '>=', $min); }
                if ($max > 0) { $sq->where('price_cents', '<=', $max); }
            });
        }

        $order = (string) ($data['order'] ?? $req->query('order', 'newest'));
        $q = match ($order) {
            'price-asc'  => $q->orderBy('id'),       // server-side sort by SKU min price requires join; deferred
            'price-desc' => $q->orderByDesc('id'),
            default      => $q->orderByDesc('created_at'),
        };

        $products = $q->paginate($perPage)->withQueryString();

        return $this->view('acme-catalog::blocks.product-grid', [
            'products' => $products,
        ], $context);
    }
}
