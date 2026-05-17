<?php

declare(strict_types=1);

namespace Acme\Notifications\Channels;

/**
 * One delivery channel — mail, log, sms, webhook, ...
 *
 * send() must be idempotent in the eyes of the channel's transport (mail
 * doesn't dedupe; webhook should). The Dispatcher itself doesn't dedupe;
 * higher-level resilience belongs to the channel impl.
 */
interface Channel
{
    public function key(): string;

    /**
     * @param  array{user_id?:?string,recipient?:?string,subject:string,body_text?:string,body_html?:string}  $payload
     */
    public function send(array $payload): void;
}
