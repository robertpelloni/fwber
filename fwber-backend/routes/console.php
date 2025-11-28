<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule relationship tier updates daily at midnight
Schedule::command('tiers:update-days')->daily();

// Schedule system data pruning daily at 03:00
Schedule::command('system:prune-data')->dailyAt('03:00');
