<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Scheduled maintenance tasks ───────────────────────────────────────────────

/** Warn tenant owners when their trial expires within 3 days; expire past trials. */
Schedule::command('trials:check-expiring')->daily();

/** Mark active/trial subscriptions past their ends_at as expired. */
Schedule::command('subscriptions:process-expired')->daily();
