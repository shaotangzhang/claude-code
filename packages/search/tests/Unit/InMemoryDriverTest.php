<?php

declare(strict_types=1);

namespace Acme\Search\Tests\Unit;

use Acme\CmsCore\Registry\InMemoryBlockRegistry;
use Acme\Search\Blocks\SearchBoxBlock;
use Acme\Search\Drivers\Driver;
use PHPUnit\Framework\TestCase;

/**
 * The DatabaseDriver needs an Eloquent table; full integration lives in
 * a feature suite. Here we verify the contract shape with an in-memory
 * fake driver and the SearchBoxBlock metadata.
 */
final class InMemoryDriverTest extends TestCase
{
    public function test_in_memory_driver_round_trip(): void
    {
        $driver = new class implements Driver {
            /** @var array<string,array<string,mixed>> */
            private array $docs = [];
            public function upsert(string $productId, array $document): void { $this->docs[$productId] = $document + ['product_id' => $productId]; }
            public function delete(string $productId): void { unset($this->docs[$productId]); }
            public function search(array $filters, int $page = 1, int $perPage = 20): array
            {
                $term = strtolower((string) ($filters['q'] ?? ''));
                $hits = array_values(array_filter($this->docs, fn ($d) =>
                    $term === '' || str_contains(strtolower($d['title'] ?? ''), $term)));
                return ['items' => $hits, 'total' => count($hits), 'facets' => []];
            }
        };

        $driver->upsert('p1', ['title' => 'Red Sneaker', 'brand' => 'acme', 'locale' => 'en']);
        $driver->upsert('p2', ['title' => 'Blue Hat',    'brand' => 'acme', 'locale' => 'en']);

        $this->assertSame(2, $driver->search([])['total']);
        $this->assertSame(1, $driver->search(['q' => 'sneaker'])['total']);

        $driver->delete('p1');
        $this->assertSame(1, $driver->search([])['total']);
    }

    public function test_search_box_block_registers(): void
    {
        $reg = new InMemoryBlockRegistry();
        $reg->register(SearchBoxBlock::class);
        $this->assertTrue($reg->has('search.box'));
        $this->assertSame('Search · Box', SearchBoxBlock::label());
    }
}
