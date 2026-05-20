<?php

declare(strict_types=1);

namespace Acme\SearchElastic\Tests\Unit;

use Acme\SearchElastic\ElasticClient;
use Acme\SearchElastic\ElasticDriver;
use Orchestra\Testbench\TestCase;

final class ElasticDriverTest extends TestCase
{
    public function test_upsert_routes_to_locale_index(): void
    {
        config()->set('acme.search-elastic.index_prefix', 'acme_products');
        $client = $this->fake();

        (new ElasticDriver($client))->upsert('p1', ['locale' => 'zh', 'title' => 'Hi']);
        $this->assertSame('acme_products_zh', $client->lastIndex[0]);
        $this->assertSame('p1',                $client->lastIndex[1]);
        $this->assertSame('p1',                $client->lastIndex[2]['product_id']);
    }

    public function test_search_compiles_query_dsl(): void
    {
        config()->set('acme.search-elastic.index_prefix', 'p');
        $client = $this->fake([
            'hits' => ['total' => ['value' => 12], 'hits' => [['_source' => ['product_id' => 'a']]]],
            'aggregations' => [
                'brand'    => ['buckets' => [['key' => 'acme', 'doc_count' => 4]]],
                'category' => ['buckets' => [['key' => 'shoes', 'doc_count' => 3]]],
            ],
        ]);

        $r = (new ElasticDriver($client))->search([
            'locale' => 'en', 'q' => 'hello',
            'category' => 'shoes', 'brand' => 'acme',
            'min_price_cents' => 100, 'max_price_cents' => 9999,
        ], page: 3, perPage: 5);

        $body = $client->lastSearch[1];
        $this->assertSame('p_en', $client->lastSearch[0]);
        $this->assertSame(10, $body['from']);
        $this->assertSame(5,  $body['size']);

        $must = $body['query']['bool']['must'];
        $this->assertSame('hello', $must[0]['multi_match']['query']);

        $filter = $body['query']['bool']['filter'];
        $this->assertContains(['term' => ['category' => 'shoes']], $filter);
        $this->assertContains(['term' => ['brand' => 'acme']],     $filter);

        $this->assertSame(12, $r['total']);
        $this->assertSame(4,  $r['facets']['brand']['acme']);
    }

    public function test_empty_query_uses_match_all(): void
    {
        $client = $this->fake([
            'hits' => ['total' => ['value' => 0], 'hits' => []],
            'aggregations' => [],
        ]);
        (new ElasticDriver($client))->search(['locale' => 'en']);

        $body = $client->lastSearch[1];
        $this->assertArrayNotHasKey('must', $body['query']['bool'] ?? []);
    }

    private function fake(array $searchResp = []): ElasticClient
    {
        return new class($searchResp) extends ElasticClient {
            public array $lastIndex = []; public array $lastDelete = []; public array $lastSearch = [];
            public function __construct(private readonly array $r) {}
            public function indexDoc(string $index, string $id, array $doc): array
            { $this->lastIndex = [$index, $id, $doc]; return []; }
            public function deleteDoc(string $index, string $id): array
            { $this->lastDelete = [$index, $id]; return []; }
            public function search(string $index, array $body): array
            { $this->lastSearch = [$index, $body]; return $this->r; }
            public function ensureIndex(string $index, array $mappings): array
            { return ['ok' => true]; }
        };
    }
}
