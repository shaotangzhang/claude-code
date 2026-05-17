<?php

declare(strict_types=1);

namespace Acme\Search\Drivers;

use Acme\Search\Models\SearchIndex;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Default driver. Uses LIKE for fuzzy matching and groupBy for facets.
 *
 * Performance ceiling: fine for catalogs up to ~50k products with
 * MySQL/Postgres. Beyond that, install acme/search-meili or
 * acme/search-elastic (future packages) and bind Driver to that.
 */
final class DatabaseDriver implements Driver
{
    public function upsert(string $productId, array $document): void
    {
        SearchIndex::query()->updateOrCreate(
            ['product_id' => $productId],
            [
                'locale'            => (string) ($document['locale'] ?? 'en'),
                'title'             => (string) ($document['title'] ?? ''),
                'brand'             => $document['brand']    ?? null,
                'category'          => $document['category'] ?? null,
                'searchable_text'   => (string) ($document['searchable_text'] ?? ''),
                'min_price_cents'   => $document['min_price_cents'] ?? null,
                'max_price_cents'   => $document['max_price_cents'] ?? null,
                'attrs_json'        => $document['attrs_json'] ?? null,
                'indexed_at'        => CarbonImmutable::now(),
            ],
        );
    }

    public function delete(string $productId): void
    {
        SearchIndex::query()->where('product_id', $productId)->delete();
    }

    public function search(array $filters, int $page = 1, int $perPage = 20): array
    {
        $minLen = (int) config('acme.search.database.min_query_length', 2);

        $q = SearchIndex::query();
        if (! empty($filters['locale']))   { $q->where('locale', $filters['locale']); }
        if (! empty($filters['category'])) { $q->where('category', $filters['category']); }
        if (! empty($filters['brand']))    { $q->where('brand', $filters['brand']); }
        if (isset($filters['min_price_cents'])) { $q->where('min_price_cents', '>=', (int) $filters['min_price_cents']); }
        if (isset($filters['max_price_cents'])) { $q->where('max_price_cents', '<=', (int) $filters['max_price_cents']); }

        $term = trim((string) ($filters['q'] ?? ''));
        if ($term !== '' && mb_strlen($term) >= $minLen) {
            $escaped = str_replace(['%', '_'], ['\%', '\_'], $term);
            $q->where(function (Builder $w) use ($escaped): void {
                $w->where('title',            'like', "%{$escaped}%")
                  ->orWhere('searchable_text', 'like', "%{$escaped}%");
            });
        }

        $total = (clone $q)->count();
        $items = (clone $q)->orderByDesc('indexed_at')
            ->forPage($page, $perPage)
            ->get()->toArray();

        // Facets (count buckets ignoring the filter on the facet field itself
        // would be ideal; for 0.1 we report buckets within the filtered set).
        $facets = [
            'category' => $this->facet($q, 'category'),
            'brand'    => $this->facet($q, 'brand'),
        ];

        return ['items' => $items, 'total' => $total, 'facets' => $facets];
    }

    private function facet(Builder $q, string $column): array
    {
        return (clone $q)->whereNotNull($column)
            ->selectRaw("{$column} as bucket, count(*) as n")
            ->groupBy($column)
            ->pluck('n', 'bucket')->all();
    }
}
