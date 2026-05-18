<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('billing:check-overdue-subscriptions')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('sites:verify-suspensions --limit=50')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();