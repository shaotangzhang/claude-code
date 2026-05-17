<?php

declare(strict_types=1);

namespace Acme\Notifications;

use Acme\Notifications\Channels\Channel;
use RuntimeException;

final class ChannelRegistry
{
    /** @var array<string,Channel> */
    private array $channels = [];

    public function register(Channel $channel): void
    {
        $this->channels[$channel->key()] = $channel;
    }

    public function get(string $key): Channel
    {
        return $this->channels[$key] ?? throw new RuntimeException("Unknown notification channel: {$key}");
    }

    public function has(string $key): bool
    {
        return isset($this->channels[$key]);
    }

    /** @return array<string,Channel> */
    public function all(): array
    {
        return $this->channels;
    }
}
