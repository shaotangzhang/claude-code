<?php

declare(strict_types=1);

namespace Acme\Notifications\Channels;

use Psr\Log\LoggerInterface;

final class LogChannel implements Channel
{
    public function __construct(private readonly LoggerInterface $log) {}

    public function key(): string { return 'log'; }

    public function send(array $payload): void
    {
        $this->log->info('[acme.notify] ' . ($payload['subject'] ?? 'event'), $payload);
    }
}
