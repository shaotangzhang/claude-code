<?php

declare(strict_types=1);

namespace Acme\PaymentsStripeSubscriptions\Models;

use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;

class StripeLink extends Model
{
    use HasUlid;

    protected $table = 'acme_subs_stripe_links';

    protected $fillable = [
        'subscription_id', 'stripe_customer_id', 'stripe_subscription_id',
        'stripe_price_id', 'status', 'current_period_end', 'last_invoice_id',
    ];

    protected function casts(): array
    {
        return ['current_period_end' => 'datetime'];
    }
}
