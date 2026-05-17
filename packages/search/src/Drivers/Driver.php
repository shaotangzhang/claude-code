<?php

declare(strict_types=1);

namespace Acme\Search\Drivers;

/**
 * Search backend abstraction. Default: database LIKE. Swap with a
 * MeiliSearch / Elasticsearch driver in a sub-package by binding this
 * interface to a new implementation in the host SP.
 *
 * Each driver is responsible for (a) maintaining its own index data
 * (called via IndexBuilder) and (b) executing queries with facets.
 */
interface Driver
{
    /** @param array<string,mixed> $document */
    public function upsert(string $productId, array $document): void;

    public function delete(string $productId): void;

    /**
     * @param  array{q?:string,locale?:string,category?:string,brand?:string,min_price_cents?:int,max_price_cents?:int}  $filters
     * @return array{items: list<array<string,mixed>>, total: int, facets: array<string,array<string,int>>}
     */
    public function search(array $filters, int $page = 1, int $perPage = 20): array;
}
