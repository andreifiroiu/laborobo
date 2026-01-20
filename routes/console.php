<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Agent Scheduled Tasks
|--------------------------------------------------------------------------
|
| Schedule agent-related commands to run at specific times.
|
*/

// Reset daily spend for all agent configurations at midnight
Schedule::command('agents:reset-daily-spend')->daily();
