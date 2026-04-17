<?php

use App\Jobs\CollectSystemMetrics;
use App\Jobs\FetchApiStats;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Collect live system metrics every minute
Schedule::job(new CollectSystemMetrics())->everyMinute();

// Fetch third-party API stats every 5 minutes to respect rate limits
Schedule::job(new FetchApiStats())->everyFiveMinutes();
