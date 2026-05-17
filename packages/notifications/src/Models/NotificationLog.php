<?php

declare(strict_types=1);

namespace Acme\Notifications\Models;

use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    use HasUlid;

    public $timestamps = false;

    protected $table = 'acme_notifications_log';

    protected $fillable = [
        'event_type', 'channel', 'user_id', 'recipient',
        'payload_json', 'status', 'failure_reason', 'created_at',
    ];

    protected function casts(): array
    {
        return ['payload_json' => 'array', 'created_at' => 'datetime'];
    }
}
