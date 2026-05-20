<?php

declare(strict_types=1);

namespace Acme\SearchMeili\Tests\Unit;

use Acme\SearchMeili\MeiliClient;
use Acme\SearchMeili\MeiliDriver;
use Orchestra\Testbench\TestCase;

final class MeiliDriverTest extends TestCase
{
    public function test_upsert_and_delete_route_to_locale_index(): void
    {
        config()->set('acme.search-meili.index_prefix', 'acme_products');
        $client = $this->fake();

        $driver = new MeiliDriver($client);
        $driver->upsert('p1', ['locale' => 'zh', 'title' => 'Hello']);
        $driver->delete('p1');

        $this->assertSame('acme_products_zh', $client->lastAdd[0] ?? null);
        $this->assertSame('p1',                $client->lastAdd[1][0]['product_id'] ?? null);
        $this->assertSame('acme_products_en',  $client->lastDelete[0] ?? null);
    }

    public function test_search_passes_filters_and_facets(): void
    {
        config()->set('acme.search-meili.index_prefix', 'p');
        $client = $this->fake([
            'hits' => [['product_id' => 'a']],
            'estimatedTotalHits' => 7,
            'facetDistribution' => ['brand' => ['acme' => 4, 'wonka' => 3]],
        ]);

        $result = (new MeiliDriver($client))->search([
            'locale' => 'en', 'q' => 'foo',
            'category' => 'shoes', 'brand' => 'acme',
            'min_price_cents' => 1000, 'max_price_cents' => 9999,
        ], page: 2, perPage: 5);

        $this->assertSame('p_en', $client->lastSearch[0]);
        $body = $client->lastSearch[1];
        $this->assertSame('foo', $body['q']);
        $this->assertSame(5, $body['offset']);
        $this->assertSame(5, $body['limit']);
        $this->assertContains('category = "shoes"', $body['filter']);
        $this->assertContains('brand = "acme"',     $body['filter']);
        $this->assertContains('min_price_cents >= 1000', $body['filter']);

        $this->assertSame(7, $result['total']);
        $this->assertSame(4, $result['facets']['brand']['acme']);
    }

    public function test_search_with_no_filters_passes_null_filter(): void
    {
        $client = $this->fake(['hits' => [], 'estimatedTotalHits' => 0]);

        (new MeiliDriver($client))->search(['locale' => 'en']);
        $this->assertNull($client->lastSearch[1]['filter']);
    }

    private function fake(array $searchResp = []): MeiliClient
    {
        return new class($searchResp) extends MeiliClient {
            public array $lastAdd = []; public array $lastDelete = []; public array $lastSearch = [];
            public function __construct(private readonly array $searchResp) {}
            public function addDocuments(string $index, array $documents): array
            { $this->lastAdd = [$index, $documents]; return []; }
            public function deleteDocument(string $index, string $id): array
            { $this->lastDelete = [$index, $id]; return []; }
            public function search(string $index, array $body): array
            { $this->lastSearch = [$index, $body]; return $this->searchResp; }
            public function ensureIndex(string $index, array $filterable): array
            { return ['ok' => true]; }
        };
    }
}
