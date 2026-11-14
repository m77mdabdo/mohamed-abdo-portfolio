<?php

use Illuminate\Support\Facades\Schedule;

// Needs one cron entry (or `php artisan schedule:work` locally) — see README › Running.
Schedule::command('portfolio:backup')->dailyAt('03:30')->withoutOverlapping();
Schedule::command('queue:prune-failed --hours=720')->weekly();
// Anything that was queued (older notices, retries) is processed by the same cron entry; no daemon is needed on shared hosting.
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')->everyMinute()->withoutOverlapping();
