<?php

declare(strict_types=1);

namespace Acme\Notifications\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    protected $table = 'acme_notifications_preferences';

    protected $fillable = ['user_id', 'event_type', 'channel', 'enabled'];

    protected function casts(): array { return ['enabled' => 'bool']; }

    public $incrementing = false;
}
