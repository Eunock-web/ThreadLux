<?php

use App\Jobs\ReleaseExpiredEscrows;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
 * |--------------------------------------------------------------------------
 * | Console Routes / Scheduled Commands
 * |--------------------------------------------------------------------------
 */

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

/*
 * Auto-release held escrows that have passed their auto_release_at deadline.
 * Runs daily at midnight. Configure delay via ESCROW_AUTO_RELEASE_DAYS in .env (default: 7).
 *
 * Test manually: php artisan schedule:run
 * Or directly:   php artisan schedule:test
 */
Schedule::job(new ReleaseExpiredEscrows)->daily()->name('release-expired-escrows');
