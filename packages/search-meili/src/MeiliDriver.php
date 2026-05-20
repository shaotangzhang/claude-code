<?php

declare(strict_types=1);

namespace Acme\SearchMeili;

use Acme\Search\Drivers\Driver;

/**
 * Drop-in replacement for acme/search's DatabaseDriver. Bound to the
 * Driver contract in SearchMeiliServiceProvider — host code keeps using
 * IndexBuilder / SearchController unchanged.
 */
final class MeiliDriver implements Driver
{
    public function __construct(private readonly MeiliClient $client) {}

    public function upsert(string $productId, array $document): void
    {
        $index = $this->indexFor((string) ($document['locale'] ?? 'en'));
        $doc   = $document + ['product_id' => $productId];

        $this->client->addDocuments($index, [$doc]);
    }

    public function delete(string $productId): void
    {
        // We don't know the locale at delete time, so issue across known locales.
        // For 0.1, default to the index-prefix-locale only; multi-locale projects
        // should provide a locale via host listener.
        $index = $this->indexFor('en');
        $this->client->deleteDocument($index, $productId);
    }

    public function search(array $filters, int $page = 1, int $perPage = 20): array
    {
        $locale = (string) ($filters['locale'] ?? 'en');
        $index  = $this->indexFor($locale);

        $facets = ['category', 'brand'];

        $body = [
            'q'      => (string) ($filters['q'] ?? ''),
            'offset' => max(0, ($page - 1) * $perPage),
            'limit'  => $perPage,
            'facets' => $facets,
            'filter' => $this->compileFilters($filters),
        ];

        $resp = $this->client->search($index, $body);

        return [
            'items'  => $resp['hits'] ?? [],
            'total'  => (int) ($resp['estimatedTotalHits'] ?? count($resp['hits'] ?? [])),
            'facets' => $this->normaliseFacets((array) ($resp['facetDistribution'] ?? [])),
        ];
    }

    public function ensureIndex(string $locale = 'en'): void
    {
        $this->client->ensureIndex(
            $this->indexFor($locale),
            (array) config('acme.search-meili.filterable_attributes', []),
        );
    }

    /** @return list<string>|null */
    private function compileFilters(array $filters): ?array
    {
        $clauses = [];
        if (! empty($filters['category'])) { $clauses[] = "category = \"{$filters['category']}\""; }
        if (! empty($filters['brand']))    { $clauses[] = "brand = \"{$filters['brand']}\""; }
        if (isset($filters['min_price_cents'])) { $clauses[] = "min_price_cents >= " . (int) $filters['min_price_cents']; }
        if (isset($filters['max_price_cents'])) { $clauses[] = "max_price_cents <= " . (int) $filters['max_price_cents']; }

        return $clauses ?: null;
    }

    /** @return array<string,array<string,int>> */
    private function normaliseFacets(array $raw): array
    {
        $out = [];
        foreach ($raw as $field => $buckets) {
            $out[$field] = array_map('intval', (array) $buckets);
        }

        return $out;
    }

    private function indexFor(string $locale): string
    {
        $prefix = (string) config('acme.search-meili.index_prefix', 'acme_products');
        $locale = preg_replace('/[^A-Za-z0-9_-]/', '', $locale) ?: 'en';

        return "{$prefix}_{$locale}";
    }
}
