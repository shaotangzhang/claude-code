<?php

declare(strict_types=1);

namespace Acme\Blog\Models;

use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasUlid;

    protected $table = 'acme_blog_subscriptions';

    protected $fillable = ['email', 'token', 'locale', 'confirmed_at', 'unsubscribed_at'];

    protected function casts(): array
    {
        return ['confirmed_at' => 'datetime', 'unsubscribed_at' => 'datetime'];
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null && $this->unsubscribed_at === null;
    }
}
