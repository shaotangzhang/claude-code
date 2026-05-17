<?php

declare(strict_types=1);

namespace Acme\Notifications;

use Acme\Auth\Models\User;
use Acme\Notifications\Models\NotificationLog;
use Acme\Notifications\Models\NotificationPreference;
use Carbon\CarbonImmutable;
use Throwable;

/**
 * Notification orchestrator. Listeners call dispatcher->dispatch(event, payload)
 * with a user OR a recipient string and an event type; dispatcher resolves
 * channels via config + preferences and sends through each.
 *
 * Every attempt writes a row to `acme_notifications_log` — successful or not.
 */
class Dispatcher
{
    public function __construct(private readonly ChannelRegistry $channels) {}

    /**
     * @param  array{user_id?:?string,recipient?:?string,subject:string,body_text?:string,body_html?:string}  $payload
     */
    public function dispatch(string $eventType, array $payload): void
    {
        $channels = $this->resolveChannels($eventType, $payload['user_id'] ?? null);
        if ($channels === []) {
            return;
        }

        $recipient = $this->resolveRecipient($eventType, $payload);

        foreach ($channels as $channelKey) {
            if (! $this->channels->has($channelKey)) {
                $this->log($eventType, $channelKey, $payload, 'skipped', "unknown channel: {$channelKey}");
                continue;
            }
            try {
                $this->channels->get($channelKey)->send($payload + ['recipient' => $recipient]);
                $this->log($eventType, $channelKey, $payload + ['recipient' => $recipient], 'sent');
            } catch (Throwable $e) {
                $this->log($eventType, $channelKey, $payload, 'failed', $e->getMessage());
            }
        }
    }

    /** @return list<string> */
    private function resolveChannels(string $eventType, ?string $userId): array
    {
        $configured = (array) (config("acme.notifications.events.{$eventType}", []));
        if (! $userId) {
            return array_values($configured);
        }

        // Honour per-user opt-outs.
        $disabled = NotificationPreference::query()
            ->where('user_id', $userId)->where('event_type', $eventType)
            ->where('enabled', false)
            ->pluck('channel')->all();

        return array_values(array_diff($configured, $disabled));
    }

    private function resolveRecipient(string $eventType, array $payload): ?string
    {
        if (! empty($payload['recipient'])) {
            return (string) $payload['recipient'];
        }
        if (! empty($payload['user_id'])) {
            $user = User::query()->find($payload['user_id']);
            if ($user) {
                return (string) $user->email;
            }
        }
        if (str_starts_with($eventType, 'stock.')) {
            return (string) config('acme.notifications.mail.ops_to', '') ?: null;
        }

        return null;
    }

    private function log(string $eventType, string $channel, array $payload, string $status, ?string $reason = null): void
    {
        NotificationLog::create([
            'event_type'     => $eventType,
            'channel'        => $channel,
            'user_id'        => $payload['user_id'] ?? null,
            'recipient'      => $payload['recipient'] ?? null,
            'payload_json'   => array_diff_key($payload, ['body_html' => null]),
            'status'         => $status,
            'failure_reason' => $reason,
            'created_at'     => CarbonImmutable::now(),
        ]);
    }
}
