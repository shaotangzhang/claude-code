<?php

declare(strict_types=1);

use Acme\Membership\Http\Controllers\MembershipController;
use Acme\Membership\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix(config('acme.membership.route_prefix', 'membership'))
    ->name('acme.membership.')
    ->group(function (): void {
        Route::get('/',                  [MembershipController::class, 'show'])->name('show');
        Route::post('/subscribe',        [SubscriptionController::class, 'subscribe'])->name('subscribe');
        Route::delete('/{subscription}', [SubscriptionController::class, 'cancel'])->name('cancel');
        Route::post('/{subscription}/pause',  [SubscriptionController::class, 'pause'])->name('pause');
        Route::post('/{subscription}/resume', [SubscriptionController::class, 'resume'])->name('resume');
    });
