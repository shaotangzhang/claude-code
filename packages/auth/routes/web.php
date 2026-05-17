<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::view('/login', 'acme-auth::login')->name('acme.auth.login');
    Route::view('/register', 'acme-auth::register')->name('acme.auth.register');
    Route::view('/password/forgot', 'acme-auth::password.forgot')->name('acme.auth.password.forgot');
    // Controllers are intentionally scaffolds — wired up in M1.x once
    // the chosen auth UX (Breeze / Fortify / custom) is decided per project.
});
