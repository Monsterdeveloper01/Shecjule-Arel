<?php

namespace App\Http\Controllers;

use App\Models\FinanceAccount;
use App\Models\FinanceCategory;
use App\Models\FinanceIncomeOverride;
use App\Models\FinanceInstallment;
use App\Models\FinanceSavingsGoal;
use App\Models\FinanceTransaction;
use App\Services\EssentialBudgetService;
use App\Services\FinanceIntelligenceService;
use App\Services\FinanceLedgerService;
use App\Services\IncomeForecastService;
use App\Services\InstallmentService;
use App\Services\SavingsGoalService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinanceController extends Controller
{
    /**
     * Display the Finance Module dashboard (Phase 1, 2, 3, 4 & 5).
     */
    public function index(
        FinanceLedgerService $ledgerService,
        IncomeForecastService $forecastService,
        EssentialBudgetService $budgetService,
        InstallmentService $installmentService,
        SavingsGoalService $savingsService,
        FinanceIntelligenceService $intelligenceService
    ): View {
        $ledgerService->ensureDefaultsExist();
        $incomeSchedule = $forecastService->ensureDefaultSchedule();
        $essentialBudget = $budgetService->ensureDefaultBudget();

        $buckets = $ledgerService->getBalanceBuckets();
        $accounts = FinanceAccount::orderByDesc('is_primary')->orderBy('name')->get();
        $categories = FinanceCategory::orderBy('name')->get();
        $recentTransactions = $ledgerService->getRecentTransactions(20);

        $now = now();
        $monthlyForecast = $forecastService->getForecastForMonth($now->year, $now->month);
        $todayIncome = $forecastService->resolveIncomeForDate($now);
        $monthlyEssential = $budgetService->getMonthlyRequirement($now->year, $now->month);
        $remainingEssential = $budgetService->getRemainingMonthlyRequirement($now);

        $incomeOverrides = FinanceIncomeOverride::forMonth($now->year, $now->month)
            ->orderBy('override_date')
            ->get();

        $installmentsSummary = $installmentService->getInstallmentsSummary($now);

        // Daily flexible surplus for affordability check
        $dailyExpected = (float) ($todayIncome['total_expected'] ?? 0);
        $dailyEssential = (float) ($essentialBudget->total_daily_minimum ?? 0);
        $dailyInstallment = (float) ($installmentsSummary['total_daily_required_today'] ?? 0);
        $estimatedDailySurplus = max(0.0, $dailyExpected - $dailyEssential - $dailyInstallment);

        $savingsSummary = $savingsService->getSavingsGoalsSummary($now, $estimatedDailySurplus);

        // Phase 5: Intelligence (Safe-to-Spend & Monthly Forecast Status)
        $safeToSpend = $intelligenceService->calculateSafeToSpend($now);
        $monthlyIntelligence = $intelligenceService->getMonthlyForecastAndStatus($now->year, $now->month);

        return view('finance.index', compact(
            'buckets',
            'accounts',
            'categories',
            'recentTransactions',
            'incomeSchedule',
            'essentialBudget',
            'monthlyForecast',
            'todayIncome',
            'monthlyEssential',
            'remainingEssential',
            'incomeOverrides',
            'installmentsSummary',
            'savingsSummary',
            'safeToSpend',
            'monthlyIntelligence'
        ));
    }

    /**
     * Store a new financial account.
     */
    public function storeAccount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:cash,bank,ewallet,savings',
            'opening_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $openingBalance = round((float) $validated['opening_balance'], 2);

        $account = FinanceAccount::create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'opening_balance' => $openingBalance,
            'current_balance' => $openingBalance,
            'installment_reserve' => 0.00,
            'savings_reserve' => 0.00,
            'is_primary' => FinanceAccount::count() === 0,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Akun keuangan berhasil ditambahkan!',
            'account' => $account,
        ]);
    }

    /**
     * Record a new financial transaction (income, expense, transfer, savings allocation).
     */
    public function storeTransaction(Request $request, FinanceLedgerService $ledgerService): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:finance_accounts,id',
            'destination_account_id' => 'nullable|exists:finance_accounts,id|different:account_id',
            'type' => 'required|in:income,expense,transfer,savings_allocation,installment_payment',
            'category_id' => 'nullable|exists:finance_categories,id',
            'amount' => 'required|numeric|min:1',
            'transaction_date' => 'required|date',
            'description' => 'required|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        $transaction = $ledgerService->recordTransaction($validated);

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil dicatat ke buku kas!',
            'transaction' => $transaction,
        ]);
    }

    /**
     * Delete a transaction and recalculate the affected account balances.
     */
    public function destroyTransaction(FinanceTransaction $transaction): JsonResponse
    {
        $account = $transaction->account;
        $destAccount = $transaction->destinationAccount;

        $transaction->delete();

        $account->recalculateBalance();
        $destAccount?->recalculateBalance();

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil dihapus!',
        ]);
    }

    /**
     * Update recurring income schedule (weekday amounts or monthly).
     */
    public function saveIncomeSchedule(Request $request, IncomeForecastService $forecastService): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:daily_variable,monthly',
            'weekday_amounts' => 'nullable|array',
            'monthly_amount' => 'nullable|numeric|min:0',
            'monthly_due_day' => 'nullable|integer|between:1,31',
        ]);

        $schedule = $forecastService->ensureDefaultSchedule();
        $schedule->update([
            'type' => $validated['type'],
            'weekday_amounts' => $validated['weekday_amounts'] ?? $schedule->weekday_amounts,
            'monthly_amount' => isset($validated['monthly_amount']) ? round((float) $validated['monthly_amount'], 2) : $schedule->monthly_amount,
            'monthly_due_day' => $validated['monthly_due_day'] ?? $schedule->monthly_due_day,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pola pemasukan rutin berhasil diperbarui!',
            'schedule' => $schedule,
        ]);
    }

    /**
     * Store a date-specific income override or freelance extra income.
     */
    public function storeIncomeOverride(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'override_date' => 'required|date',
            'title' => 'required|string|max:150',
            'amount' => 'required|numeric|min:1',
            'is_extra' => 'required|boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        $override = FinanceIncomeOverride::create([
            'override_date' => $validated['override_date'],
            'title' => $validated['title'],
            'amount' => round((float) $validated['amount'], 2),
            'is_extra' => (bool) $validated['is_extra'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => $override->is_extra ? 'Pemasukan ekstra berhasil dijadwalkan!' : 'Override pemasukan harian berhasil disimpan!',
            'override' => $override,
        ]);
    }

    /**
     * Delete an income override.
     */
    public function destroyIncomeOverride(FinanceIncomeOverride $override): JsonResponse
    {
        $override->delete();

        return response()->json([
            'success' => true,
            'message' => 'Override pemasukan berhasil dihapus!',
        ]);
    }

    /**
     * Update essential daily budget (food, transport, snack, other).
     */
    public function saveEssentialBudget(Request $request, EssentialBudgetService $budgetService): JsonResponse
    {
        $validated = $request->validate([
            'food' => 'nullable|numeric|min:0',
            'transport' => 'nullable|numeric|min:0',
            'snack' => 'nullable|numeric|min:0',
            'other' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $budget = $budgetService->updateBudget($validated);

        return response()->json([
            'success' => true,
            'message' => 'Kebutuhan pokok harian berhasil diperbarui!',
            'budget' => $budget,
        ]);
    }

    /**
     * Store a new installment.
     */
    public function storeInstallment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'total_amount' => 'required|numeric|min:1',
            'monthly_amount' => 'required|numeric|min:1',
            'due_day' => 'required|integer|between:1,31',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'category' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:500',
        ]);

        $installment = FinanceInstallment::create([
            'name' => $validated['name'],
            'total_amount' => round((float) $validated['total_amount'], 2),
            'monthly_amount' => round((float) $validated['monthly_amount'], 2),
            'due_day' => (int) $validated['due_day'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'category' => $validated['category'] ?? 'elektronik',
            'status' => 'active',
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cicilan berhasil ditambahkan!',
            'installment' => $installment,
        ]);
    }

    /**
     * Delete an installment.
     */
    public function destroyInstallment(FinanceInstallment $installment): JsonResponse
    {
        $installment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cicilan berhasil dihapus!',
        ]);
    }

    /**
     * Allocate funds into installment reserve without spending it.
     */
    public function allocateInstallmentReserve(
        Request $request,
        FinanceInstallment $installment,
        InstallmentService $installmentService
    ): JsonResponse {
        $validated = $request->validate([
            'account_id' => 'required|exists:finance_accounts,id',
            'amount' => 'required|numeric|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        $allocation = $installmentService->allocateReserve(
            $installment->id,
            (int) $validated['account_id'],
            (float) $validated['amount'],
            $validated['notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Dana berhasil dicadangkan untuk cicilan!',
            'allocation' => $allocation,
        ]);
    }

    /**
     * Record actual installment payment (creates an installment_payment transaction and releases reserve).
     */
    public function payInstallment(
        Request $request,
        FinanceInstallment $installment,
        InstallmentService $installmentService
    ): JsonResponse {
        $validated = $request->validate([
            'account_id' => 'required|exists:finance_accounts,id',
            'amount' => 'required|numeric|min:1',
            'transaction_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $transaction = $installmentService->payInstallment(
            $installment->id,
            (int) $validated['account_id'],
            (float) $validated['amount'],
            $validated['transaction_date'] ?? now()->toDateString(),
            $validated['notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran cicilan berhasil dicatat ke buku kas!',
            'transaction' => $transaction,
        ]);
    }

    /**
     * Store a new savings goal (Deadline or Open-ended).
     */
    public function storeSavingsGoal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'target_amount' => 'required|numeric|min:1',
            'type' => 'required|in:deadline,open_ended',
            'target_date' => 'nullable|required_if:type,deadline|date',
            'color' => 'nullable|string|max:20',
            'icon' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:500',
        ]);

        $goal = FinanceSavingsGoal::create([
            'name' => $validated['name'],
            'target_amount' => round((float) $validated['target_amount'], 2),
            'current_amount' => 0.00,
            'type' => $validated['type'],
            'target_date' => $validated['target_date'] ?? null,
            'color' => $validated['color'] ?? '#10b981',
            'icon' => $validated['icon'] ?? '🎯',
            'status' => 'active',
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Target tabungan berhasil dibuat!',
            'goal' => $goal,
        ]);
    }

    /**
     * Delete a savings goal.
     */
    public function destroySavingsGoal(FinanceSavingsGoal $goal): JsonResponse
    {
        $goal->delete();

        return response()->json([
            'success' => true,
            'message' => 'Target tabungan berhasil dihapus!',
        ]);
    }

    /**
     * Allocate funds into a savings goal (locks money into savings_reserve without spending).
     */
    public function allocateSavings(
        Request $request,
        FinanceSavingsGoal $goal,
        SavingsGoalService $savingsService
    ): JsonResponse {
        $validated = $request->validate([
            'account_id' => 'required|exists:finance_accounts,id',
            'amount' => 'required|numeric|min:1',
            'allocation_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $allocation = $savingsService->allocateSavings(
            $goal->id,
            (int) $validated['account_id'],
            (float) $validated['amount'],
            $validated['allocation_date'] ?? now()->toDateString(),
            $validated['notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Dana berhasil dialokasikan ke target tabungan!',
            'allocation' => $allocation,
        ]);
    }

    /**
     * Withdraw or release funds from a savings goal.
     */
    public function withdrawSavings(
        Request $request,
        FinanceSavingsGoal $goal,
        SavingsGoalService $savingsService
    ): JsonResponse {
        $validated = $request->validate([
            'account_id' => 'required|exists:finance_accounts,id',
            'amount' => 'required|numeric|min:1',
            'is_expense' => 'required|boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        $updatedGoal = $savingsService->withdrawSavings(
            $goal->id,
            (int) $validated['account_id'],
            (float) $validated['amount'],
            (bool) $validated['is_expense'],
            $validated['notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => $validated['is_expense'] ? 'Penggunaan tabungan berhasil dicatat sebagai pengeluaran!' : 'Dana tabungan berhasil dikembalikan ke saldo bebas!',
            'goal' => $updatedGoal,
        ]);
    }

    /**
     * Dynamically calculate Safe-to-Spend for any specific date.
     */
    public function getSafeToSpendForDate(Request $request, FinanceIntelligenceService $intelligenceService): JsonResponse
    {
        $d = $request->input('date') ? Carbon::parse($request->input('date')) : now();
        $result = $intelligenceService->calculateSafeToSpend($d);

        return response()->json([
            'success' => true,
            'data' => $result,
            'safe_to_spend' => $result,
        ]);
    }
}
