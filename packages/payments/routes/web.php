<?php

declare(strict_types=1);

use Acme\Payments\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::middleware('api')->prefix(config('acme.payments.route_prefix', 'payments'))->group(function (): void {
    Route::post('/{gateway}/webhook', WebhookController::class)->name('acme.payments.webhook');
});
