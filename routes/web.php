<?php

use App\Http\Controllers\AiAssistantController;
use App\Http\Controllers\CourseScheduleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\PinController;
use App\Http\Controllers\ProductivityController;
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
    Route::patch('/tasks/{task}/subtask-toggle', [TaskController::class, 'toggleSubtask'])->name('tasks.subtask-toggle');

    Route::resource('schedules', CourseScheduleController::class)->except(['create', 'show', 'edit']);
    Route::post('/schedules/bulk-import', [CourseScheduleController::class, 'bulkImport'])->name('schedules.bulk-import');

    Route::resource('notes', NoteController::class)->except(['create', 'show', 'edit']);
    Route::patch('/notes/{note}/pin', [NoteController::class, 'togglePin'])->name('notes.pin');

    Route::resource('events', EventController::class)->except(['create', 'show', 'edit']);

    // Push Notification Testing & Manual Trigger
    Route::post('/push/test', [PushSubscriptionController::class, 'sendTest'])->name('push.test');
    Route::post('/push/test-schedule', [PushSubscriptionController::class, 'sendScheduleTest'])->name('push.test-schedule');
    Route::post('/push/check-deadlines', [PushSubscriptionController::class, 'checkDeadlines'])->name('push.check');

    // AI Assistant (V3)
    Route::prefix('ai')->name('ai.')->group(function () {
        Route::post('/parse', [AiAssistantController::class, 'parseInput'])->name('parse');
        Route::post('/breakdown', [AiAssistantController::class, 'breakdown'])->name('breakdown');
        Route::post('/parse-ocr', [AiAssistantController::class, 'parseOcr'])->name('parse-ocr');
        Route::post('/confirm-draft', [AiAssistantController::class, 'confirmDraft'])->name('confirm-draft');
        Route::get('/daily-insight', [AiAssistantController::class, 'dailyInsight'])->name('daily-insight');
    });

    // Personal OS (V4)
    Route::prefix('productivity')->name('productivity.')->group(function () {
        Route::get('/', [ProductivityController::class, 'index'])->name('index');
        Route::post('/focus-sessions', [ProductivityController::class, 'storeFocusSession'])->name('focus-sessions.store');
        Route::post('/habits', [ProductivityController::class, 'storeHabit'])->name('habits.store');
        Route::post('/habits/{habit}/toggle', [ProductivityController::class, 'toggleHabit'])->name('habits.toggle');
        Route::delete('/habits/{habit}', [ProductivityController::class, 'destroyHabit'])->name('habits.destroy');
        Route::get('/reviews/weekly', [ProductivityController::class, 'weeklyReview'])->name('reviews.weekly');
        Route::get('/reviews/monthly', [ProductivityController::class, 'monthlyReview'])->name('reviews.monthly');
    });

    // Finance Module (Personal Planner Finance)
    Route::prefix('finance')->name('finance.')->group(function () {
        Route::get('/', [FinanceController::class, 'index'])->name('index');
        Route::post('/accounts', [FinanceController::class, 'storeAccount'])->name('accounts.store');
        Route::post('/transactions', [FinanceController::class, 'storeTransaction'])->name('transactions.store');
        Route::delete('/transactions/{transaction}', [FinanceController::class, 'destroyTransaction'])->name('transactions.destroy');

        // Phase 2: Income & Allowance
        Route::post('/income/schedule', [FinanceController::class, 'saveIncomeSchedule'])->name('income.schedule');
        Route::post('/income/overrides', [FinanceController::class, 'storeIncomeOverride'])->name('income.overrides.store');
        Route::delete('/income/overrides/{override}', [FinanceController::class, 'destroyIncomeOverride'])->name('income.overrides.destroy');
        Route::post('/budget', [FinanceController::class, 'saveEssentialBudget'])->name('budget.save');

        // Phase 3: Installments
        Route::post('/installments', [FinanceController::class, 'storeInstallment'])->name('installments.store');
        Route::delete('/installments/{installment}', [FinanceController::class, 'destroyInstallment'])->name('installments.destroy');
        Route::post('/installments/{installment}/reserve', [FinanceController::class, 'allocateInstallmentReserve'])->name('installments.reserve');
        Route::post('/installments/{installment}/pay', [FinanceController::class, 'payInstallment'])->name('installments.pay');

        // Phase 4: Savings Goals
        Route::post('/savings', [FinanceController::class, 'storeSavingsGoal'])->name('savings.store');
        Route::delete('/savings/{goal}', [FinanceController::class, 'destroySavingsGoal'])->name('savings.destroy');
        Route::post('/savings/{goal}/allocate', [FinanceController::class, 'allocateSavings'])->name('savings.allocate');
        Route::post('/savings/{goal}/withdraw', [FinanceController::class, 'withdrawSavings'])->name('savings.withdraw');

        // Phase 5: Intelligence & Integration
        Route::post('/safe-to-spend', [FinanceController::class, 'getSafeToSpendForDate'])->name('safe-to-spend');
    });
});
