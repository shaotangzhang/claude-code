<?php

declare(strict_types=1);

namespace Acme\NotificationsWebhook;

use Acme\Notifications\Channels\Channel;
use Illuminate\Http\Client\Factory as Http;
use Psr\Log\LoggerInterface;
use Throwable;

final class WebhookChannel implements Channel
{
    public function __construct(
        private readonly Http $http,
        private readonly LoggerInterface $log,
    ) {}

    public function key(): string { return 'webhook'; }

    public function send(array $payload): void
    {
        $url    = (string) config('acme.notifications-webhook.url', '');
        $secret = (string) config('acme.notifications-webhook.secret', '');
        $header = (string) config('acme.notifications-webhook.signature_header', 'X-Acme-Signature');
        if ($url === '') {
            return;
        }

        $body      = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $signature = $secret !== '' ? WebhookSignature::sign((string) $body, $secret) : null;

        try {
            $this->http
                ->timeout((int) config('acme.notifications-webhook.timeout_seconds', 5))
                ->withHeaders($signature ? [$header => $signature] : [])
                ->acceptJson()
                ->withBody((string) $body, 'application/json')
                ->post($url)
                ->throw();
        } catch (Throwable $e) {
            $this->log->error('[acme.notify.webhook] post failed', [
                'url' => $url, 'err' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
