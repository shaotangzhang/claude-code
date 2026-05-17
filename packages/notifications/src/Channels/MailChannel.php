<?php

declare(strict_types=1);

namespace Acme\Notifications\Channels;

use Illuminate\Contracts\Mail\Factory as MailFactory;
use Illuminate\Mail\Message;

final class MailChannel implements Channel
{
    public function __construct(private readonly MailFactory $mailer) {}

    public function key(): string { return 'mail'; }

    public function send(array $payload): void
    {
        $to = (string) ($payload['recipient'] ?? '');
        if ($to === '') {
            return; // nothing to send to
        }

        $from     = (string) config('acme.notifications.mail.from',   '');
        $fromName = (string) config('acme.notifications.mail.from_name', 'Acme');
        $subject  = (string) ($payload['subject']   ?? 'Notification');
        $text     = (string) ($payload['body_text'] ?? '');
        $html     = (string) ($payload['body_html'] ?? '');

        $this->mailer->mailer()->send([], [], function (Message $message) use ($to, $subject, $text, $html, $from, $fromName): void {
            $message->to($to)->subject($subject);
            if ($from !== '') {
                $message->from($from, $fromName);
            }
            if ($html !== '') { $message->html($html); }
            if ($text !== '') { $message->text($text); }
        });
    }
}
