<?php

use Illuminate\Support\Facades\Schedule;

// Needs one cron entry (or `php artisan schedule:work` locally) — see README › Running.
Schedule::command('portfolio:backup')->dailyAt('03:30')->withoutOverlapping();
Schedule::command('queue:prune-failed --hours=720')->weekly();
