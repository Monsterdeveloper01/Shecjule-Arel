<?php

namespace App\Services;

use App\Models\FinanceAccount;
use App\Models\FinanceInstallment;
use App\Models\FinanceInstallmentAllocation;
use App\Models\FinanceTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InstallmentService
{
    public function __construct(
        protected FinanceLedgerService $ledgerService
    ) {}

    /**
     * Get a comprehensive summary of all installments, obligations, reserves, and daily requirements for a given month.
     *
     * @return array{
     *     total_monthly_obligation: float,
     *     total_already_reserved: float,
     *     total_remaining_needed: float,
     *     total_daily_required_today: float,
     *     active_count: int,
     *     items: array<int, array<string, mixed>>
     * }
     */
    public function getInstallmentsSummary(Carbon|string $date): array
    {
        $d = $date instanceof Carbon ? $date->copy() : Carbon::parse($date);
        $year = $d->year;
        $month = $d->month;

        $installments = FinanceInstallment::orderBy('status')
            ->orderBy('due_day')
            ->get();

        $totalMonthlyObligation = 0.0;
        $totalAlreadyReserved = 0.0;
        $totalRemainingNeeded = 0.0;
        $totalDailyRequiredToday = 0.0;
        $activeCount = 0;
        $items = [];

        foreach ($installments as $inst) {
            $isPaidThisMonth = $inst->payments()
                ->whereYear('transaction_date', $year)
                ->whereMonth('transaction_date', $month)
                ->exists();

            $alreadyReservedThisMonth = (float) $inst->allocations()
                ->where('year', $year)
                ->where('month', $month)
                ->sum('amount');

            $calculation = $inst->calculateDailyAllocation($d, $alreadyReservedThisMonth, $isPaidThisMonth);

            if ($inst->status === 'active') {
                $activeCount++;
                $totalMonthlyObligation += (float) $inst->monthly_amount;
                $totalAlreadyReserved += $alreadyReservedThisMonth;
                $totalRemainingNeeded += $calculation['remaining_obligation'];
                $totalDailyRequiredToday += $calculation['daily_required'];
            }

            $items[] = [
                'id' => $inst->id,
                'name' => $inst->name,
                'type' => $inst->type ?? 'debt',
                'category' => $inst->category,
                'status' => $inst->status,
                'total_amount' => $inst->total_amount !== null ? (float) $inst->total_amount : null,
                'monthly_amount' => (float) $inst->monthly_amount,
                'due_day' => $inst->due_day,
                'total_paid' => $inst->total_paid,
                'remaining_total' => $inst->remaining_total,
                'progress_percent' => $inst->progress_percent,
                'due_date' => $calculation['due_date']->format('d M Y'),
                'days_remaining' => $calculation['days_remaining'],
                'already_reserved' => $calculation['already_reserved'],
                'remaining_obligation' => $calculation['remaining_obligation'],
                'daily_required' => $calculation['daily_required'],
                'is_due_today' => $calculation['is_due_today'],
                'is_overdue' => $calculation['is_overdue'],
                'is_fully_reserved' => $calculation['is_fully_reserved'],
                'is_paid' => $isPaidThisMonth,
                'notes' => $inst->notes,
            ];
        }

        return [
            'total_monthly_obligation' => round($totalMonthlyObligation, 2),
            'total_already_reserved' => round($totalAlreadyReserved, 2),
            'total_remaining_needed' => round($totalRemainingNeeded, 2),
            'total_daily_required_today' => round($totalDailyRequiredToday, 2),
            'active_count' => $activeCount,
            'items' => $items,
        ];
    }

    /**
     * Reserve funds for an installment without triggering a consumption expense.
     */
    public function allocateReserve(
        int $installmentId,
        int $accountId,
        float $amount,
        ?string $notes = null,
        ?string $date = null
    ): FinanceInstallmentAllocation {
        return DB::transaction(function () use ($installmentId, $accountId, $amount, $notes, $date) {
            $installment = FinanceInstallment::findOrFail($installmentId);
            $account = FinanceAccount::findOrFail($accountId);
            $d = $date ? Carbon::parse($date) : now();

            $allocation = FinanceInstallmentAllocation::create([
                'installment_id' => $installment->id,
                'account_id' => $account->id,
                'amount' => round($amount, 2),
                'allocation_date' => $d->toDateString(),
                'month' => $d->month,
                'year' => $d->year,
                'notes' => $notes,
            ]);

            // Atomically lock the reserve in the account
            $account->installment_reserve = round((float) $account->installment_reserve + $amount, 2);
            $account->save();

            return $allocation;
        });
    }

    /**
     * Record actual installment payment (creates an installment_payment transaction and releases reserve).
     */
    public function payInstallment(
        int $installmentId,
        int $accountId,
        float $amount,
        ?string $transactionDate = null,
        ?string $notes = null
    ): FinanceTransaction {
        return DB::transaction(function () use ($installmentId, $accountId, $amount, $transactionDate, $notes) {
            $installment = FinanceInstallment::findOrFail($installmentId);
            $account = FinanceAccount::findOrFail($accountId);
            $dateStr = $transactionDate ?: now()->toDateString();
            $d = Carbon::parse($dateStr);

            $typeLabel = $installment->isRecurringBill() ? 'Tagihan Rutin' : 'Cicilan';

            // Record transaction in ledger
            $transaction = $this->ledgerService->recordTransaction([
                'account_id' => $account->id,
                'type' => 'installment_payment',
                'amount' => $amount,
                'transaction_date' => $dateStr,
                'description' => "Pembayaran {$typeLabel}: {$installment->name}",
                'installment_id' => $installment->id,
                'notes' => $notes,
            ]);

            // Release reserved money from this account's installment_reserve
            $account->refresh();
            $account->installment_reserve = max(0.0, round((float) $account->installment_reserve - $amount, 2));
            $account->save();

            // Check if overall debt is fully satisfied (only for debt type, never for ongoing recurring bills)
            $installment->refresh();
            if ($installment->isDebt() && $installment->remaining_total !== null && $installment->remaining_total <= 0.0) {
                $installment->update(['status' => 'paid_off']);
            }

            return $transaction;
        });
    }
}
