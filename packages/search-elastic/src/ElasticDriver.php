<?php

declare(strict_types=1);

namespace Acme\SearchElastic;

use Acme\Search\Drivers\Driver;

/**
 * Elasticsearch implementation of acme/search's Driver. Same external
 * contract as DatabaseDriver / MeiliDriver — bound via container so
 * host code keeps using IndexBuilder + SearchController unchanged.
 */
final class ElasticDriver implements Driver
{
    public function __construct(private readonly ElasticClient $client) {}

    public function upsert(string $productId, array $document): void
    {
        $this->client->indexDoc(
            $this->indexFor((string) ($document['locale'] ?? 'en')),
            $productId,
            $document + ['product_id' => $productId],
        );
    }

    public function delete(string $productId): void
    {
        // Best-effort across primary locale — multi-locale fan-out left to 0.2.
        $this->client->deleteDoc($this->indexFor('en'), $productId);
    }

    public function search(array $filters, int $page = 1, int $perPage = 20): array
    {
        $index = $this->indexFor((string) ($filters['locale'] ?? 'en'));

        $must   = [];
        $filter = [];

        if (! empty($filters['q']) && $filters['q'] !== '') {
            $must[] = ['multi_match' => [
                'query'  => (string) $filters['q'],
                'fields' => ['title^3', 'searchable_text', 'brand', 'category'],
                'fuzziness' => 'AUTO',
            ]];
        }
        if (! empty($filters['category'])) { $filter[] = ['term' => ['category' => $filters['category']]]; }
        if (! empty($filters['brand']))    { $filter[] = ['term' => ['brand'    => $filters['brand']]]; }
        if (isset($filters['min_price_cents'])) {
            $filter[] = ['range' => ['min_price_cents' => ['gte' => (int) $filters['min_price_cents']]]];
        }
        if (isset($filters['max_price_cents'])) {
            $filter[] = ['range' => ['max_price_cents' => ['lte' => (int) $filters['max_price_cents']]]];
        }

        $body = [
            'from' => max(0, ($page - 1) * $perPage),
            'size' => $perPage,
            'query' => [
                'bool' => array_filter([
                    'must'   => $must ?: null,
                    'filter' => $filter ?: null,
                ]),
            ] ?: ['match_all' => new \stdClass()],
            'aggs' => [
                'brand'    => ['terms' => ['field' => 'brand',    'size' => 50]],
                'category' => ['terms' => ['field' => 'category', 'size' => 50]],
            ],
        ];

        $resp = $this->client->search($index, $body);

        $items = array_map(fn ($hit) => (array) ($hit['_source'] ?? []), (array) ($resp['hits']['hits'] ?? []));
        $total = (int) ($resp['hits']['total']['value'] ?? count($items));

        $facets = [];
        foreach (['brand', 'category'] as $field) {
            $buckets = (array) ($resp['aggregations'][$field]['buckets'] ?? []);
            $facets[$field] = [];
            foreach ($buckets as $b) {
                $facets[$field][(string) $b['key']] = (int) ($b['doc_count'] ?? 0);
            }
        }

        return ['items' => $items, 'total' => $total, 'facets' => $facets];
    }

    public function ensureIndex(string $locale = 'en'): void
    {
        $this->client->ensureIndex($this->indexFor($locale), [
            'properties' => [
                'product_id'      => ['type' => 'keyword'],
                'locale'          => ['type' => 'keyword'],
                'title'           => ['type' => 'text'],
                'brand'           => ['type' => 'keyword'],
                'category'        => ['type' => 'keyword'],
                'searchable_text' => ['type' => 'text'],
                'min_price_cents' => ['type' => 'long'],
                'max_price_cents' => ['type' => 'long'],
                'attrs_json'      => ['type' => 'object', 'enabled' => false],
                'indexed_at'      => ['type' => 'date'],
            ],
        ]);
    }

    private function indexFor(string $locale): string
    {
        $prefix = (string) config('acme.search-elastic.index_prefix', 'acme_products');
        $locale = preg_replace('/[^A-Za-z0-9_-]/', '', $locale) ?: 'en';

        return strtolower("{$prefix}_{$locale}");
    }
}
