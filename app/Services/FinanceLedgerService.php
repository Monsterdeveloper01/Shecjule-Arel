<?php

namespace App\Services;

use App\Models\FinanceAccount;
use App\Models\FinanceCategory;
use App\Models\FinanceTransaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class FinanceLedgerService
{
    /**
     * Ensure default accounts and categories exist.
     */
    public function ensureDefaultsExist(): void
    {
        if (FinanceAccount::count() === 0) {
            FinanceAccount::create([
                'name' => 'Dompet Kas Utama',
                'type' => 'cash',
                'opening_balance' => 0.00,
                'current_balance' => 0.00,
                'installment_reserve' => 0.00,
                'savings_reserve' => 0.00,
                'is_primary' => true,
                'notes' => 'Akun kas utama default',
            ]);
        }

        if (FinanceCategory::count() === 0) {
            $categories = [
                // Incomes
                ['name' => 'Uang Saku / Bulanan', 'type' => 'income', 'icon' => '💵', 'color' => '#22c55e', 'is_system' => true],
                ['name' => 'Gaji / Freelance Extra', 'type' => 'income', 'icon' => '💻', 'color' => '#3b82f6', 'is_system' => true],
                ['name' => 'Pemberian / Lainnya', 'type' => 'income', 'icon' => '🎁', 'color' => '#8b5cf6', 'is_system' => true],
                // Expenses
                ['name' => 'Makanan & Minuman', 'type' => 'expense', 'icon' => '🍔', 'color' => '#ef4444', 'is_system' => true],
                ['name' => 'Transportasi & Bensin', 'type' => 'expense', 'icon' => '🛵', 'color' => '#f97316', 'is_system' => true],
                ['name' => 'Kebutuhan Kuliah & Buku', 'type' => 'expense', 'icon' => '📚', 'color' => '#eab308', 'is_system' => true],
                ['name' => 'Cicilan (Installment)', 'type' => 'expense', 'icon' => '💳', 'color' => '#ec4899', 'is_system' => true],
                ['name' => 'Hiburan & Ngopi', 'type' => 'expense', 'icon' => '☕', 'color' => '#6366f1', 'is_system' => true],
                ['name' => 'Lain-lain', 'type' => 'expense', 'icon' => '📦', 'color' => '#6b7280', 'is_system' => true],
            ];

            foreach ($categories as $cat) {
                FinanceCategory::create($cat);
            }
        }
    }

    /**
     * Record a new transaction and update balances atomically.
     *
     * @param array{
     *     account_id: int,
     *     destination_account_id?: int|null,
     *     type: string,
     *     category_id?: int|null,
     *     amount: float|int|string,
     *     transaction_date: string,
     *     description: string,
     *     installment_id?: int|null,
     *     savings_goal_id?: int|null,
     *     notes?: string|null
     * } $data
     */
    public function recordTransaction(array $data): FinanceTransaction
    {
        return DB::transaction(function () use ($data) {
            $account = FinanceAccount::findOrFail($data['account_id']);
            $amount = round((float) $data['amount'], 2);

            $transaction = FinanceTransaction::create([
                'account_id' => $account->id,
                'destination_account_id' => $data['destination_account_id'] ?? null,
                'type' => $data['type'],
                'category_id' => $data['category_id'] ?? null,
                'amount' => $amount,
                'transaction_date' => $data['transaction_date'] ?? now()->toDateString(),
                'description' => $data['description'],
                'installment_id' => $data['installment_id'] ?? null,
                'savings_goal_id' => $data['savings_goal_id'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // Handle reserve locking for savings allocation
            if ($data['type'] === 'savings_allocation') {
                $account->savings_reserve = round((float) $account->savings_reserve + $amount, 2);
                $account->save();
            }

            // Recalculate source account
            $account->recalculateBalance();

            // If transfer, recalculate destination account
            if ($data['type'] === 'transfer' && ! empty($data['destination_account_id'])) {
                $destAccount = FinanceAccount::find($data['destination_account_id']);
                $destAccount?->recalculateBalance();
            }

            return $transaction;
        });
    }

    /**
     * Adjust or set installment reserve on an account.
     */
    public function setInstallmentReserve(FinanceAccount $account, float $reservedAmount): void
    {
        $account->installment_reserve = max(0.0, round($reservedAmount, 2));
        $account->save();
    }

    /**
     * Adjust or set savings reserve on an account.
     */
    public function setSavingsReserve(FinanceAccount $account, float $reservedAmount): void
    {
        $account->savings_reserve = max(0.0, round($reservedAmount, 2));
        $account->save();
    }

    /**
     * Get aggregate balances across all accounts.
     *
     * @return array{
     *     total_balance: float,
     *     available_spending: float,
     *     installment_reserve: float,
     *     savings_reserve: float
     * }
     */
    public function getBalanceBuckets(): array
    {
        $accounts = FinanceAccount::all();

        $totalBalance = (float) $accounts->sum('current_balance');
        $installmentReserve = (float) $accounts->sum('installment_reserve');
        $savingsReserve = (float) $accounts->sum('savings_reserve');
        $availableSpending = max(0.0, round($totalBalance - $installmentReserve - $savingsReserve, 2));

        return [
            'total_balance' => round($totalBalance, 2),
            'available_spending' => $availableSpending,
            'installment_reserve' => round($installmentReserve, 2),
            'savings_reserve' => round($savingsReserve, 2),
        ];
    }

    /**
     * Get recent transactions with relationships.
     */
    public function getRecentTransactions(int $limit = 20): Collection
    {
        return FinanceTransaction::with(['account', 'destinationAccount', 'category'])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }
}
