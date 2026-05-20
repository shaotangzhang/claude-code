<?php

declare(strict_types=1);

namespace Acme\SearchElastic;

use Illuminate\Http\Client\Factory as Http;
use Illuminate\Http\Client\PendingRequest;

/**
 * Thin Elasticsearch REST wrapper. Supports either:
 *   - ELASTIC_API_KEY  (Authorization: ApiKey ...)
 *   - ELASTIC_USERNAME + ELASTIC_PASSWORD (basic auth)
 */
class ElasticClient
{
    public function __construct(
        private readonly Http $http,
        private readonly string $host,
        private readonly string $apiKey,
        private readonly string $username,
        private readonly string $password,
        private readonly int $timeoutSeconds = 5,
    ) {}

    public function indexDoc(string $index, string $id, array $doc): array
    {
        return $this->request()
            ->put("/{$index}/_doc/{$id}", $doc)
            ->throw()->json() ?? [];
    }

    public function deleteDoc(string $index, string $id): array
    {
        return $this->request()
            ->delete("/{$index}/_doc/{$id}")
            ->throw()->json() ?? [];
    }

    public function search(string $index, array $body): array
    {
        return $this->request()
            ->post("/{$index}/_search", $body)
            ->throw()->json() ?? [];
    }

    /** Create index with mappings if absent. */
    public function ensureIndex(string $index, array $mappings): array
    {
        $head = $this->request()->head("/{$index}");
        if ($head->status() === 200) {
            return ['ok' => true, 'existed' => true];
        }
        $this->request()->put("/{$index}", ['mappings' => $mappings])->throw();

        return ['ok' => true, 'existed' => false];
    }

    private function request(): PendingRequest
    {
        $req = $this->http->baseUrl($this->host)
            ->acceptJson()->asJson()
            ->timeout($this->timeoutSeconds);

        if ($this->apiKey !== '') {
            $req = $req->withHeaders(['Authorization' => 'ApiKey ' . $this->apiKey]);
        } elseif ($this->username !== '') {
            $req = $req->withBasicAuth($this->username, $this->password);
        }

        return $req;
    }
}
