<?php

declare(strict_types=1);

namespace Acme\AbandonedCart\Models;

use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;

class AbandonedReminder extends Model
{
    use HasUlid;

    public $timestamps = false;

    protected $table = 'acme_abandoned_reminders';

    protected $fillable = ['cart_id', 'round', 'coupon_id', 'sent_at'];

    protected function casts(): array
    {
        return ['round' => 'integer', 'sent_at' => 'datetime'];
    }
}
