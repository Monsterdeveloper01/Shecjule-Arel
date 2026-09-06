<?php

use Illuminate\Support\Facades\Schedule;

// 1. Pengingat H-1 Jadwal Kuliah Besok (Setiap malam jam 20:00)
Schedule::command('notifications:check-deadlines')
    ->dailyAt('16:00')
    ->name('daily-evening-schedule-briefing')
    ->withoutOverlapping();

// 2. Pengingat Jadwal Kuliah & Tugas Hari Ini (Setiap pagi jam 07:00)
Schedule::command('notifications:check-deadlines')
    ->dailyAt('07:00')
    ->name('daily-morning-briefing')
    ->withoutOverlapping();

// 3. Pengecekan Deadline Tugas & Overdue (Setiap Jam)
Schedule::command('notifications:check-deadlines')
    ->hourly()
    ->name('hourly-deadline-check')
    ->withoutOverlapping();
