<?php

declare(strict_types=1);

namespace Acme\Search\Services;

use Acme\Catalog\Models\Product;
use Acme\Search\Drivers\Driver;

/**
 * Converts a catalog Product into a search document and pushes it to the
 * active driver. Pure read against catalog — never writes back there.
 */
final class IndexBuilder
{
    public function __construct(private readonly Driver $driver) {}

    public function index(Product $product): void
    {
        $product->loadMissing(['brand', 'category', 'skus']);

        $text = trim(implode(' ', array_filter([
            $product->title,
            $product->summary,
            $product->description,
            $product->brand?->name,
            $product->category?->name,
        ])));

        $prices = $product->skus->pluck('price_cents');
        $this->driver->upsert($product->id, [
            'locale'          => $product->locale,
            'title'           => (string) $product->title,
            'brand'           => $product->brand?->slug,
            'category'        => $product->category?->slug,
            'searchable_text' => $text,
            'min_price_cents' => $prices->min(),
            'max_price_cents' => $prices->max(),
            'attrs_json'      => $product->attrs_json,
        ]);
    }

    public function remove(string $productId): void
    {
        $this->driver->delete($productId);
    }

    public function rebuildAll(?string $locale = null): int
    {
        $count = 0;
        $q     = Product::query()->with(['brand', 'category', 'skus'])->published();
        if ($locale) { $q->where('locale', $locale); }

        $q->chunkById(200, function ($chunk) use (&$count): void {
            foreach ($chunk as $p) {
                $this->index($p);
                $count++;
            }
        });

        return $count;
    }
}
