<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('notifications:check-deadlines')
    ->dailyAt('07:00')
    ->name('daily-morning-briefing')
    ->withoutOverlapping();

Schedule::command('notifications:check-deadlines')
    ->hourly()
    ->name('hourly-deadline-check')
    ->withoutOverlapping();
