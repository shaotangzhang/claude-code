<?php

declare(strict_types=1);

namespace Acme\SearchMeili;

use Illuminate\Http\Client\Factory as Http;
use Illuminate\Http\Client\PendingRequest;

/**
 * Thin REST wrapper around MeiliSearch v1. Other engines (Elasticsearch,
 * OpenSearch, Typesense) follow the same shape — write a sibling Driver +
 * Client and bind it to acme/search's Driver contract.
 */
class MeiliClient
{
    public function __construct(
        private readonly Http $http,
        private readonly string $host,
        private readonly string $apiKey,
        private readonly int $timeoutSeconds = 5,
    ) {}

    /** Upsert documents into the locale-prefixed index. */
    public function addDocuments(string $index, array $documents): array
    {
        return $this->request()->post("/indexes/{$index}/documents", $documents)
            ->throw()->json() ?? [];
    }

    public function deleteDocument(string $index, string $id): array
    {
        return $this->request()->delete("/indexes/{$index}/documents/{$id}")
            ->throw()->json() ?? [];
    }

    public function search(string $index, array $body): array
    {
        return $this->request()->post("/indexes/{$index}/search", $body)
            ->throw()->json() ?? [];
    }

    /** Idempotent: creates index + sets filterable attributes. */
    public function ensureIndex(string $index, array $filterable): array
    {
        $this->request()->post('/indexes', ['uid' => $index, 'primaryKey' => 'product_id']);
        $this->request()->patch("/indexes/{$index}/settings", [
            'filterableAttributes' => $filterable,
        ]);

        return ['ok' => true];
    }

    private function request(): PendingRequest
    {
        $req = $this->http->baseUrl($this->host)
            ->acceptJson()->asJson()
            ->timeout($this->timeoutSeconds);

        if ($this->apiKey !== '') {
            $req = $req->withToken($this->apiKey);
        }

        return $req;
    }
}
