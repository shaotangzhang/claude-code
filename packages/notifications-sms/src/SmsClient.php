<?php

declare(strict_types=1);

namespace Acme\NotificationsSms;

use Illuminate\Http\Client\Factory as Http;
use RuntimeException;

/**
 * Twilio-compatible HTTP client. Other carriers (Vonage, Plivo, AWS SNS)
 * are accommodated by swapping this class via container binding.
 */
class SmsClient
{
    public function __construct(
        private readonly Http $http,
        private readonly string $apiBase,
        private readonly string $sid,
        private readonly string $token,
    ) {}

    /** @return array<string,mixed> raw provider response */
    public function send(string $from, string $to, string $body): array
    {
        if ($this->sid === '' || $this->token === '') {
            throw new RuntimeException('SMS credentials not configured.');
        }
        if ($from === '' || $to === '' || $body === '') {
            throw new RuntimeException('SMS from/to/body all required.');
        }

        return $this->http->baseUrl($this->apiBase)
            ->withBasicAuth($this->sid, $this->token)
            ->asForm()->acceptJson()
            ->post("/Accounts/{$this->sid}/Messages.json", [
                'From' => $from,
                'To'   => $to,
                'Body' => $body,
            ])
            ->throw()->json() ?? [];
    }
}
