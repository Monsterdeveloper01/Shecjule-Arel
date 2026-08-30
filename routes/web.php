<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\PinController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

// Auth (PIN-based)
Route::get('/login', [PinController::class, 'showLogin'])->name('login');
Route::post('/login', [PinController::class, 'login']);
Route::get('/setup', [PinController::class, 'showSetup'])->name('pin.setup');
Route::post('/setup', [PinController::class, 'setup']);
Route::post('/logout', [PinController::class, 'logout'])->name('logout');

// Web Push Public Key & Subscriptions (can be accessed by client worker)
Route::get('/push/vapid-public-key', [PushSubscriptionController::class, 'vapidPublicKey'])->name('push.vapid');
Route::post('/push/subscribe', [PushSubscriptionController::class, 'subscribe'])->name('push.subscribe');
Route::post('/push/unsubscribe', [PushSubscriptionController::class, 'unsubscribe'])->name('push.unsubscribe');

// Protected routes
Route::middleware('pin.auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/calendar-data', [DashboardController::class, 'calendarData'])->name('calendar.data');

    Route::resource('tasks', TaskController::class)->except(['create', 'show', 'edit']);
    Route::patch('/tasks/{task}/toggle', [TaskController::class, 'toggleStatus'])->name('tasks.toggle');

    Route::resource('notes', NoteController::class)->except(['create', 'show', 'edit']);
    Route::patch('/notes/{note}/pin', [NoteController::class, 'togglePin'])->name('notes.pin');

    Route::resource('events', EventController::class)->except(['create', 'show', 'edit']);

    // Push Notification Testing & Manual Trigger
    Route::post('/push/test', [PushSubscriptionController::class, 'sendTest'])->name('push.test');
    Route::post('/push/check-deadlines', [PushSubscriptionController::class, 'checkDeadlines'])->name('push.check');
});
