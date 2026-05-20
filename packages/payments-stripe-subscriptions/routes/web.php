<?php

declare(strict_types=1);

use Acme\PaymentsStripeSubscriptions\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::middleware('api')
    ->prefix(config('acme.payments-stripe-subscriptions.route_prefix', 'payments/stripe-subs'))
    ->group(function (): void {
        Route::post('/webhook', WebhookController::class)->name('acme.payments-stripe-subs.webhook');
    });
