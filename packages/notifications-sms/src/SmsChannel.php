<?php

declare(strict_types=1);

namespace Acme\NotificationsSms;

use Acme\Notifications\Channels\Channel;
use Psr\Log\LoggerInterface;
use Throwable;

final class SmsChannel implements Channel
{
    public function __construct(
        private readonly SmsClient $client,
        private readonly LoggerInterface $log,
    ) {}

    public function key(): string { return 'sms'; }

    public function send(array $payload): void
    {
        $to   = (string) ($payload['recipient'] ?? '');
        $body = trim(($payload['subject'] ?? '') . "\n" . ($payload['body_text'] ?? ''));
        if ($to === '' || $body === '') {
            return;
        }

        $from = (string) config('acme.notifications-sms.from', '');
        if ($from === '' || ! config('acme.notifications-sms.sid') || ! config('acme.notifications-sms.token')) {
            if (config('acme.notifications-sms.log_when_unconfigured', true)) {
                $this->log->info('[acme.notify.sms] (unconfigured) ' . $to, ['body' => $body]);

                return;
            }
            throw new \RuntimeException('SMS gateway not configured.');
        }

        try {
            $this->client->send($from, $to, $body);
        } catch (Throwable $e) {
            $this->log->error('[acme.notify.sms] send failed', ['to' => $to, 'err' => $e->getMessage()]);
            throw $e;
        }
    }
}
