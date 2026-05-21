<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Scheduled tasks for the demo. Host projects pick whichever they
 * actually need.
 */
Schedule::command('acme:membership:tick')
    ->everyFifteenMinutes()->withoutOverlapping();

Schedule::command('acme:abandoned-cart:tick')
    ->everyThirtyMinutes()->withoutOverlapping();

Schedule::command('acme:inventory-fefo:auto-discount --days=14 --percent=20')
    ->dailyAt('03:00')->withoutOverlapping();

Schedule::command('acme:search:reindex')
    ->dailyAt('04:00')->withoutOverlapping();
