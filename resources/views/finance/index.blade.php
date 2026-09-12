@extends('layouts.app')

@section('title', 'Keuangan')
@section('page-title', 'Personal Finance — Manajemen Kas, Income & Allowance')

@section('content')
<div class="finance-wrapper">
    {{-- Top Overview: Actual Balance & Buckets --}}
    <div class="finance-hero-card">
        <div class="finance-hero-glow"></div>
        <div class="finance-hero-content">
            <div class="finance-hero-left">
                <span class="finance-badge-tag">💰 Personal Finance · Phase 1, 2, 3, 4 & 5 (Intelligence)</span>
                <h2 class="finance-hero-title">Manajemen Kas & Target Keuangan</h2>
                <p class="finance-hero-desc">
                    Saldo riil murni berbasis buku besar kas nyata. Dilengkapi pola pemasukan harian, alokasi kebutuhan pokok, pencadangan cicilan, dan target tabungan impian.
                </p>
            </div>
            <div class="finance-hero-actions">
                <button type="button" class="btn btn-success btn-sm" onclick="openTransactionModal('income')">
                    + Pemasukan Riil
                </button>
                <button type="button" class="btn btn-primary btn-sm" onclick="openTransactionModal('expense')">
                    - Pengeluaran
                </button>
                <button type="button" class="btn btn-outline btn-sm" onclick="openTransactionModal('transfer')">
                    ⇄ Transfer
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="openAccountModal()">
                    + Akun Kas
                </button>
            </div>
        </div>

        {{-- 4 Balance Buckets Grid --}}
        <div class="balance-buckets-grid">
            <div class="bucket-box bucket-total glass-card">
                <div class="bucket-label">
                    <span>💵 Total Saldo Kas (Actual)</span>
                    <span class="bucket-hint">Faktual di dompet/bank</span>
                </div>
                <div class="bucket-amount text-accent">
                    Rp {{ number_format($buckets['total_balance'], 0, ',', '.') }}
                </div>
            </div>

            <div class="bucket-box bucket-available glass-card">
                <div class="bucket-label">
                    <span>🟢 Uang Aman Belanja (Available)</span>
                    <span class="bucket-hint">Saldo setelah cadangan</span>
                </div>
                <div class="bucket-amount text-success">
                    Rp {{ number_format($buckets['available_spending'], 0, ',', '.') }}
                </div>
            </div>

            <div class="bucket-box bucket-installment glass-card">
                <div class="bucket-label">
                    <span>💳 Cadangan Cicilan (Reserved)</span>
                    <span class="bucket-hint">Disisihkan untuk tagihan</span>
                </div>
                <div class="bucket-amount text-pink">
                    Rp {{ number_format($buckets['installment_reserve'], 0, ',', '.') }}
                </div>
            </div>

            <div class="bucket-box bucket-savings glass-card">
                <div class="bucket-label">
                    <span>🔒 Pos Tabungan (Savings Reserve)</span>
                    <span class="bucket-hint">Terkunci untuk target/impian</span>
                </div>
                <div class="bucket-amount text-purple">
                    Rp {{ number_format($buckets['savings_reserve'], 0, ',', '.') }}
                </div>
            </div>
        </div>

        {{-- Mini Info Banner --}}
        <div style="margin-top: 16px; padding: 10px 14px; background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: var(--radius-sm); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
            <div style="display: flex; gap: 12px; align-items: center;">
                <span style="font-size: 14px;">⚡</span>
                <div>
                    <span style="font-size: 11px; color: var(--text-secondary);">Ekspektasi Hari Ini ({{ now()->translatedFormat('l, d M') }}):</span>
                    <strong style="color: #86efac; font-size: 13px;">Rp {{ number_format($todayIncome['total_expected'], 0, ',', '.') }}</strong>
                    <div style="font-size: 11px; color: var(--text-tertiary); display: inline-block; margin-left: 6px;">
                        @if($todayIncome['has_actual'])
                            · <span style="color: #4ade80;">Sudah masuk: Rp {{ number_format($todayIncome['actual_amount'], 0, ',', '.') }} (Selisih: {{ $todayIncome['variance'] >= 0 ? '+' : '-' }}Rp {{ number_format(abs($todayIncome['variance']), 0, ',', '.') }})</span>
                        @else
                            · <span style="color: var(--text-tertiary);">Belum ada mutasi masuk hari ini</span>
                        @endif
                    </div>
                </div>
            </div>
            <div style="display: flex; gap: 16px; align-items: center;">
                <div>
                    <span style="font-size: 10px; color: var(--text-tertiary); text-transform: uppercase;">Proyeksi Bulan Ini:</span>
                    <div style="font-size: 14px; font-weight: 800; color: #93c5fd;">Rp {{ number_format($monthlyForecast['total_expected_income'], 0, ',', '.') }}</div>
                </div>
                <div>
                    <span style="font-size: 10px; color: var(--text-tertiary); text-transform: uppercase;">Kebutuhan Pokok Bulan Ini:</span>
                    <div style="font-size: 14px; font-weight: 800; color: #facc15;">Rp {{ number_format($monthlyEssential, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Sub-Navigation Tabs --}}
    <div class="prod-tabs-bar" style="margin-bottom: 4px;">
        <button type="button" class="prod-tab-btn active" id="tabBtnLedger" onclick="switchFinanceTab('ledger')">
            📖 Buku Kas & Saldo
        </button>
        <button type="button" class="prod-tab-btn" id="tabBtnIncome" onclick="switchFinanceTab('income')">
            📅 Pola Pemasukan & Jadwal
        </button>
        <button type="button" class="prod-tab-btn" id="tabBtnBudget" onclick="switchFinanceTab('budget')">
            🍱 Kebutuhan Pokok (Allowance)
        </button>
        <button type="button" class="prod-tab-btn" id="tabBtnInstallments" onclick="switchFinanceTab('installments')">
            💳 Cicilan & Kewajiban Rutin
        </button>
        <button type="button" class="prod-tab-btn" id="tabBtnSavings" onclick="switchFinanceTab('savings')">
            🎯 Target Tabungan
        </button>
        <button type="button" class="prod-tab-btn" id="tabBtnIntelligence" onclick="switchFinanceTab('intelligence')">
            📊 Intelligence & Safe-to-Spend
        </button>
    </div>

    {{-- TAB 1: Ledger & Accounts --}}
    <div class="finance-tab-section active" id="tabSectionLedger">
        <div class="finance-main-grid">
            {{-- Left: Accounts List --}}
            <div class="finance-col-accounts">
                <div class="glass-card accounts-card">
                    <div class="card-header-flex">
                        <h3 class="card-title-sm">🏦 Akun & Dompet Keuangan</h3>
                        <button type="button" class="btn-link-sm" onclick="openAccountModal()">+ Tambah</button>
                    </div>
                    <div class="accounts-list">
                        @foreach($accounts as $acc)
                        <div class="account-item-box">
                            <div class="account-item-top">
                                <div class="account-item-identity">
                                    <span class="account-type-badge type-{{ $acc->type }}">
                                        @switch($acc->type)
                                            @case('bank') 🏛️ Bank @break
                                            @case('ewallet') 📱 E-Wallet @break
                                            @case('savings') 🔒 Tabungan @break
                                            @default 💵 Tunai
                                        @endswitch
                                    </span>
                                    <strong class="account-name">{{ $acc->name }}</strong>
                                    @if($acc->is_primary)
                                        <span class="primary-badge">Utama</span>
                                    @endif
                                </div>
                                <div class="account-balance">
                                    Rp {{ number_format($acc->current_balance, 0, ',', '.') }}
                                </div>
                            </div>
                            <div class="account-item-bottom">
                                <span title="Saldo belanja bebas di akun ini">Bebas: <strong>Rp {{ number_format($acc->available_spending, 0, ',', '.') }}</strong></span>
                                @if($acc->installment_reserve > 0)
                                    <span title="Cadangan cicilan">Cicilan: Rp {{ number_format($acc->installment_reserve, 0, ',', '.') }}</span>
                                @endif
                                @if($acc->savings_reserve > 0)
                                    <span title="Cadangan tabungan">Tabungan: Rp {{ number_format($acc->savings_reserve, 0, ',', '.') }}</span>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Right: Transaction Ledger --}}
            <div class="finance-col-ledger">
                <div class="glass-card ledger-card">
                    <div class="card-header-flex">
                        <div>
                            <h3 class="card-title-sm">📖 Buku Kas & Riwayat Transaksi</h3>
                            <p class="card-sub-sm">Setiap mutasi kas tercatat nyata tanpa estimasi asumtif.</p>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <button type="button" class="btn btn-primary btn-sm" onclick="openTransactionModal('expense')">
                                + Catat Transaksi
                            </button>
                        </div>
                    </div>

                    @if($recentTransactions->isEmpty())
                        <div class="empty-state-box">
                            <span style="font-size: 32px;">🧾</span>
                            <p style="margin: 8px 0 0 0; color: var(--text-tertiary); font-size: 13px;">
                                Belum ada riwayat transaksi kas yang dicatat.
                            </p>
                        </div>
                    @else
                        <div class="transactions-table-wrap">
                            <table class="finance-table">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Keterangan & Kategori</th>
                                        <th>Akun Kas</th>
                                        <th>Tipe</th>
                                        <th style="text-align: right;">Nominal</th>
                                        <th style="text-align: center;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentTransactions as $tx)
                                    <tr>
                                        <td class="tx-date-cell">
                                            {{ $tx->transaction_date->format('d M Y') }}
                                        </td>
                                        <td class="tx-desc-cell">
                                            <strong>{{ $tx->description }}</strong>
                                            <div class="tx-meta-cat">
                                                <span>{{ $tx->category?->icon ?? '🏷️' }} {{ $tx->category?->name ?? 'Tanpa Kategori' }}</span>
                                                @if($tx->notes)
                                                    <span class="tx-note-text">· {{ $tx->notes }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="tx-account-cell">
                                            <span>{{ $tx->account?->name ?? 'Akun Dihapus' }}</span>
                                            @if($tx->type === 'transfer' && $tx->destinationAccount)
                                                <span style="color: var(--text-tertiary);">➔ {{ $tx->destinationAccount->name }}</span>
                                            @endif
                                        </td>
                                        <td class="tx-type-cell">
                                            <span class="tx-badge type-{{ $tx->type }}">
                                                @switch($tx->type)
                                                    @case('income') Pemasukan @break
                                                    @case('expense') Pengeluaran @break
                                                    @case('transfer') Transfer Kas @break
                                                    @case('savings_allocation') Simpan Tabungan @break
                                                    @case('installment_payment') Bayar Cicilan @break
                                                    @default {{ $tx->type }}
                                                @endswitch
                                            </span>
                                        </td>
                                        <td class="tx-amount-cell text-right {{ $tx->type === 'income' ? 'amount-income' : ($tx->type === 'transfer' ? 'amount-transfer' : 'amount-expense') }}">
                                            @if($tx->type === 'income')
                                                + Rp {{ number_format($tx->amount, 0, ',', '.') }}
                                            @elseif($tx->type === 'transfer')
                                                ⇄ Rp {{ number_format($tx->amount, 0, ',', '.') }}
                                            @else
                                                - Rp {{ number_format($tx->amount, 0, ',', '.') }}
                                            @endif
                                        </td>
                                        <td style="text-align: center;">
                                            <button type="button" class="btn-del-tx" onclick="deleteTransaction({{ $tx->id }})" title="Hapus Transaksi">&times;</button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- TAB 2: Income Patterns & Calendar --}}
    <div class="finance-tab-section" id="tabSectionIncome" style="display: none;">
        <div class="income-view-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            {{-- Left: Recurring Weekday Pattern --}}
            <div class="glass-card" style="padding: 20px; border-radius: var(--radius-lg); border: 1px solid var(--border-color);">
                <div class="card-header-flex">
                    <div>
                        <h3 class="card-title-sm">🔄 Pola Pemasukan Rutin</h3>
                        <p class="card-sub-sm">Nominal terencana berdasarkan hari dalam seminggu.</p>
                    </div>
                    <button type="button" class="btn btn-outline btn-sm" onclick="openIncomeScheduleModal()">
                        ✏️ Atur Pola
                    </button>
                </div>

                <div class="weekday-pills-list" style="display: flex; flex-direction: column; gap: 8px;">
                    @php
                        $daysMap = [
                            '1' => 'Senin', '2' => 'Selasa', '3' => 'Rabu',
                            '4' => 'Kamis', '5' => 'Jumat', '6' => 'Sabtu', '7' => 'Minggu'
                        ];
                    @endphp
                    @foreach($daysMap as $dNum => $dLabel)
                        @php $amt = $incomeSchedule->getAmountForIsoDay((int) $dNum); @endphp
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; background: rgba(255,255,255,0.03); border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="font-weight: 700; font-size: 12px; color: #fff; width: 60px;">{{ $dLabel }}</span>
                                <span style="font-size: 11px; color: var(--text-secondary);">
                                    {{ $amt > 0 ? 'Pemasukan terjadwal' : 'Tidak ada pemasukan rutin' }}
                                </span>
                            </div>
                            <strong style="font-size: 13px; color: {{ $amt > 0 ? '#86efac' : 'var(--text-tertiary)' }};">
                                Rp {{ number_format($amt, 0, ',', '.') }}
                            </strong>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Right: Specific Date Overrides & Extra Incomes --}}
            <div class="glass-card" style="padding: 20px; border-radius: var(--radius-lg); border: 1px solid var(--border-color);">
                <div class="card-header-flex">
                    <div>
                        <h3 class="card-title-sm">🌟 Pemasukan Ekstra & Override Tanggal</h3>
                        <p class="card-sub-sm">Freelance, bonus, atau penggantian nominal tanggal spesifik.</p>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" onclick="openOverrideModal()">
                        + Tambah Ekstra
                    </button>
                </div>

                @if($incomeOverrides->isEmpty())
                    <div class="empty-state-box" style="padding: 24px;">
                        <span style="font-size: 28px;">📅</span>
                        <p style="margin: 6px 0 0 0; color: var(--text-tertiary); font-size: 12px;">
                            Belum ada jadwal freelance atau override tanggal bulan ini.
                        </p>
                    </div>
                @else
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        @foreach($incomeOverrides as $ov)
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 12px; background: rgba(255,255,255,0.03); border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                            <div>
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <strong style="font-size: 13px; color: #fff;">{{ $ov->title }}</strong>
                                    <span class="account-type-badge" style="background: {{ $ov->is_extra ? 'rgba(59,130,246,0.2)' : 'rgba(234,179,8,0.2)' }}; color: {{ $ov->is_extra ? '#93c5fd' : '#fde047' }};">
                                        {{ $ov->is_extra ? 'Freelance / Ekstra' : 'Override Harian' }}
                                    </span>
                                </div>
                                <div style="font-size: 11px; color: var(--text-secondary); margin-top: 2px;">
                                    Tanggal: {{ $ov->override_date->format('d M Y') }}
                                    @if($ov->notes) · <em>{{ $ov->notes }}</em> @endif
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <strong style="font-size: 13px; color: #86efac;">+ Rp {{ number_format($ov->amount, 0, ',', '.') }}</strong>
                                <button type="button" class="btn-del-tx" onclick="deleteIncomeOverride({{ $ov->id }})" title="Hapus Override">&times;</button>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- TAB 3: Essential Budget & Allowance --}}
    <div class="finance-tab-section" id="tabSectionBudget" style="display: none;">
        <div class="budget-view-grid" style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 20px;">
            {{-- Left: Essential Daily Breakdown Form --}}
            <div class="glass-card" style="padding: 24px; border-radius: var(--radius-lg); border: 1px solid var(--border-color);">
                <div class="card-header-flex">
                    <div>
                        <h3 class="card-title-sm">🍱 Kebutuhan Pokok Harian (Daily Essential Budget)</h3>
                        <p class="card-sub-sm">Alokasi minimum mutlak yang wajib dipenuhi setiap hari untuk bertahan.</p>
                    </div>
                </div>

                <form id="essentialBudgetForm" onsubmit="submitEssentialBudget(event)">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                        <div class="form-group">
                            <label>🍔 Makanan & Minuman Pokok (Rp/hari) <span class="required">*</span></label>
                            <input type="text" inputmode="numeric" class="form-input rupiah-input" id="budgetFoodInput" value="{{ number_format($essentialBudget->food, 0, ',', '.') }}" placeholder="0" required>
                        </div>
                        <div class="form-group">
                            <label>🛵 Transportasi & Bensin (Rp/hari) <span class="required">*</span></label>
                            <input type="text" inputmode="numeric" class="form-input rupiah-input" id="budgetTransportInput" value="{{ number_format($essentialBudget->transport, 0, ',', '.') }}" placeholder="0" required>
                        </div>
                        <div class="form-group">
                            <label>☕ Camilan / Kopi (Rp/hari)</label>
                            <input type="text" inputmode="numeric" class="form-input rupiah-input" id="budgetSnackInput" value="{{ number_format($essentialBudget->snack, 0, ',', '.') }}" placeholder="0">
                        </div>
                        <div class="form-group">
                            <label>📦 Kebutuhan Harian Lainnya (Rp/hari)</label>
                            <input type="text" inputmode="numeric" class="form-input rupiah-input" id="budgetOtherInput" value="{{ number_format($essentialBudget->other, 0, ',', '.') }}" placeholder="0">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Catatan Anggaran Pokok</label>
                        <input type="text" class="form-input" id="budgetNotesInput" value="{{ $essentialBudget->notes }}" placeholder="Catatan anggaran harian...">
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--border-color);">
                        <div>
                            <span style="font-size: 11px; color: var(--text-tertiary); text-transform: uppercase;">Total Minimum Pokok Harian:</span>
                            <div style="font-size: 20px; font-weight: 800; color: #facc15;" id="budgetTotalPreview">
                                Rp {{ number_format($essentialBudget->total_daily_minimum, 0, ',', '.') }} / hari
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary" id="btnSaveBudget">Simpan Kebutuhan Pokok</button>
                    </div>
                </form>
            </div>

            {{-- Right: Monthly Essential Impact Summary --}}
            <div class="glass-card" style="padding: 24px; border-radius: var(--radius-lg); border: 1px solid var(--border-color); display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <h3 class="card-title-sm">📊 Analisis Kebutuhan Pokok</h3>
                    <p class="card-sub-sm">Pengaruh kebutuhan pokok terhadap cashflow bulan ini.</p>

                    <div style="display: flex; flex-direction: column; gap: 14px; margin-top: 18px;">
                        <div style="padding: 12px; background: rgba(255,255,255,0.03); border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                            <span style="font-size: 11px; color: var(--text-secondary);">Total Hari Bulan Ini:</span>
                            <div style="font-size: 16px; font-weight: 800; color: #fff;">{{ $monthlyForecast['total_days'] }} Hari</div>
                        </div>

                        <div style="padding: 12px; background: rgba(255,255,255,0.03); border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                            <span style="font-size: 11px; color: var(--text-secondary);">Komitmen Pokok Sebulan Penuh:</span>
                            <div style="font-size: 18px; font-weight: 800; color: #facc15;">
                                Rp {{ number_format($monthlyEssential, 0, ',', '.') }}
                            </div>
                        </div>

                        <div style="padding: 12px; background: rgba(255,255,255,0.03); border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                            <span style="font-size: 11px; color: var(--text-secondary);">Sisa Kebutuhan Pokok Bulan Ini:</span>
                            <div style="font-size: 18px; font-weight: 800; color: #86efac;">
                                Rp {{ number_format($remainingEssential, 0, ',', '.') }}
                            </div>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 20px; font-size: 11px; color: var(--text-tertiary); line-height: 1.5;">
                    💡 <em>Uang pokok ini otomatis dilindungi dari perhitungan <strong>Safe to Spend</strong> agar Anda tidak kekurangan makan atau bensin.</em>
                </div>
            </div>
        </div>
    </div>

    {{-- TAB 4: Installments & Obligations --}}
    <div class="finance-tab-section" id="tabSectionInstallments" style="display: none;">
        {{-- Overview Metrics --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; margin-bottom: 24px;">
            <div class="glass-card" style="padding: 16px 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color); background: rgba(0,0,0,0.35);">
                <span style="font-size: 11px; color: var(--text-tertiary); text-transform: uppercase; font-weight: 700;">Total Kewajiban Bulan Ini</span>
                <div style="font-size: 20px; font-weight: 800; color: #f472b6; margin-top: 6px;">
                    Rp {{ number_format($installmentsSummary['total_monthly_obligation'], 0, ',', '.') }}
                </div>
                <div style="font-size: 11px; color: var(--text-secondary); margin-top: 4px;">
                    {{ $installmentsSummary['active_count'] }} tagihan aktif berjalan
                </div>
            </div>

            <div class="glass-card" style="padding: 16px 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color); background: rgba(0,0,0,0.35);">
                <span style="font-size: 11px; color: var(--text-tertiary); text-transform: uppercase; font-weight: 700;">Sudah Dicadangkan</span>
                <div style="font-size: 20px; font-weight: 800; color: #38bdf8; margin-top: 6px;">
                    Rp {{ number_format($installmentsSummary['total_already_reserved'], 0, ',', '.') }}
                </div>
                <div style="font-size: 11px; color: var(--text-secondary); margin-top: 4px;">
                    🔒 Dana aman terkunci di akun kas
                </div>
            </div>

            <div class="glass-card" style="padding: 16px 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color); background: rgba(0,0,0,0.35);">
                <span style="font-size: 11px; color: var(--text-tertiary); text-transform: uppercase; font-weight: 700;">Sisa Perlu Dicadangkan</span>
                <div style="font-size: 20px; font-weight: 800; color: #fbbf24; margin-top: 6px;">
                    Rp {{ number_format($installmentsSummary['total_remaining_needed'], 0, ',', '.') }}
                </div>
                <div style="font-size: 11px; color: var(--text-secondary); margin-top: 4px;">
                    Kewajiban belum teralokasi bulan ini
                </div>
            </div>

            <div class="glass-card" style="padding: 16px 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color); background: rgba(0,0,0,0.35);">
                <span style="font-size: 11px; color: var(--text-tertiary); text-transform: uppercase; font-weight: 700;">Rekomendasi Cadangan Hari Ini</span>
                <div style="font-size: 20px; font-weight: 800; color: #86efac; margin-top: 6px;">
                    Rp {{ number_format($installmentsSummary['total_daily_required_today'], 0, ',', '.') }}
                </div>
                <div style="font-size: 11px; color: var(--text-secondary); margin-top: 4px;">
                    Berdasarkan sisa hari sebelum jatuh tempo
                </div>
            </div>
        </div>

        {{-- Installment Cards List --}}
        <div class="glass-card" style="padding: 24px; border-radius: var(--radius-lg); border: 1px solid var(--border-color);">
            <div class="card-header-flex">
                <div>
                    <h3 class="card-title-sm">💳 Daftar Cicilan & Pengeluaran Rutin Bulanan</h3>
                    <p class="card-sub-sm">
                        Kelola <strong>Cicilan Pinjaman</strong> (dengan plafon lunas) maupun <strong>Pengeluaran Rutin Bulanan</strong> (WiFi, Kos, Listrik, Gym, Langganan Digital). Semua komitmen dipisahkan antara kewajiban dan cadangan kas riil.
                    </p>
                </div>
                <button type="button" class="btn btn-primary btn-sm" onclick="openInstallmentModal()">
                    + Tambah Cicilan / Tagihan
                </button>
            </div>

            @if(empty($installmentsSummary['items']))
                <div class="empty-state-box">
                    <span style="font-size: 36px;">🎉</span>
                    <p style="margin: 10px 0 0 0; color: #fff; font-size: 14px; font-weight: 700;">
                        Tidak ada kewajiban cicilan atau pengeluaran rutin yang tercatat!
                    </p>
                    <p style="margin: 4px 0 16px 0; color: var(--text-tertiary); font-size: 12px;">
                        Tambahkan pengeluaran rutin bulanan (WiFi, Kos, Gym) atau cicilan kredit untuk mengelola pencadangan dananya.
                    </p>
                    <button type="button" class="btn btn-outline btn-sm" onclick="openInstallmentModal()">
                        + Tambah Komitmen Sekarang
                    </button>
                </div>
            @else
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 18px; margin-top: 16px;">
                    @foreach($installmentsSummary['items'] as $item)
                        <div class="glass-card" style="padding: 18px; border-radius: var(--radius-md); border: 1px solid rgba(255,255,255,0.08); background: rgba(0,0,0,0.4); display: flex; flex-direction: column; justify-content: space-between; gap: 16px;">
                            <div>
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px;">
                                    <div>
                                        <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap; margin-bottom: 4px;">
                                            @if(($item['type'] ?? 'debt') === 'recurring_bill')
                                                <span style="font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 9999px; background: rgba(168, 85, 247, 0.18); color: #c084fc;">
                                                    🔁 Rutin Bulanan
                                                </span>
                                            @else
                                                <span style="font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 9999px; background: rgba(244, 114, 182, 0.15); color: #f472b6;">
                                                    💳 Cicilan Hutang
                                                </span>
                                            @endif

                                            <span style="font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 9999px; background: rgba(255, 255, 255, 0.06); color: var(--text-secondary); text-transform: uppercase;">
                                                {{ ucfirst(str_replace('_', ' ', $item['category'] ?? 'Umum')) }}
                                            </span>

                                            @if(($item['type'] ?? 'debt') === 'debt' && $item['status'] === 'paid_off')
                                                <span style="font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 9999px; background: rgba(34, 197, 94, 0.18); color: #86efac;">
                                                    ✅ Lunas Sepenuhnya
                                                </span>
                                            @endif
                                        </div>
                                        <h4 style="font-size: 16px; font-weight: 800; color: #fff; margin: 0;">{{ $item['name'] }}</h4>
                                        <div style="font-size: 11px; color: var(--text-secondary); margin-top: 2px;">
                                            Tagihan: <strong style="color: #f472b6;">Rp {{ number_format($item['monthly_amount'], 0, ',', '.') }}</strong> / bulan (Jatuh tempo: Tgl {{ $item['due_day'] }})
                                        </div>
                                    </div>
                                    <div>
                                        @if($item['is_paid'])
                                            <span style="font-size: 11px; font-weight: 700; color: #86efac; background: rgba(34, 197, 94, 0.15); padding: 4px 8px; border-radius: 6px;">
                                                ✅ Lunas Bulan Ini
                                            </span>
                                        @elseif($item['is_due_today'])
                                            <span style="font-size: 11px; font-weight: 800; color: #ef4444; background: rgba(239, 68, 68, 0.2); padding: 4px 8px; border-radius: 6px;">
                                                ⚠️ Jatuh Tempo Hari Ini!
                                            </span>
                                        @elseif($item['is_overdue'])
                                            <span style="font-size: 11px; font-weight: 800; color: #f87171; background: rgba(239, 68, 68, 0.15); padding: 4px 8px; border-radius: 6px;">
                                                🚨 Terlewat Jatuh Tempo
                                            </span>
                                        @else
                                            <span style="font-size: 11px; font-weight: 700; color: #93c5fd; background: rgba(59, 130, 246, 0.15); padding: 4px 8px; border-radius: 6px;">
                                                ⏳ {{ $item['days_remaining'] }} hari lagi ({{ $item['due_date'] }})
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                @if(($item['type'] ?? 'debt') === 'debt')
                                    {{-- Progress 1: Pelunasan Total Plafon (Cicilan Hutang) --}}
                                    <div style="margin-top: 14px; background: rgba(255,255,255,0.03); padding: 10px 12px; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                                        <div style="display: flex; justify-content: space-between; font-size: 11px; color: var(--text-secondary); margin-bottom: 6px;">
                                            <span>Progres Pelunasan Plafon:</span>
                                            <strong style="color: #fff;">{{ $item['progress_percent'] ?? 0 }}%</strong>
                                        </div>
                                        <div style="width: 100%; height: 6px; background: rgba(255,255,255,0.1); border-radius: 9999px; overflow: hidden;">
                                            <div style="height: 100%; width: {{ $item['progress_percent'] ?? 0 }}%; background: linear-gradient(90deg, #a855f7, #ec4899); border-radius: 9999px;"></div>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; font-size: 10px; color: var(--text-tertiary); margin-top: 4px;">
                                            <span>Terbayar: Rp {{ number_format($item['total_paid'], 0, ',', '.') }}</span>
                                            <span>Sisa: Rp {{ number_format($item['remaining_total'] ?? 0, 0, ',', '.') }}</span>
                                        </div>
                                    </div>
                                @else
                                    {{-- Info Tagihan Rutin Bulanan Berkelanjutan --}}
                                    <div style="margin-top: 14px; background: rgba(168, 85, 247, 0.05); padding: 10px 12px; border-radius: var(--radius-sm); border: 1px solid rgba(168, 85, 247, 0.2); display: flex; align-items: center; gap: 8px;">
                                        <span style="font-size: 16px;">🔁</span>
                                        <div style="font-size: 11px; color: var(--text-secondary);">
                                            <strong style="color: #e9d5ff;">Pengeluaran Rutin Berkelanjutan</strong>
                                            <div style="color: var(--text-tertiary);">Otomatis aktif kembali tiap bulan baru tanpa batas plafon pinjaman.</div>
                                        </div>
                                    </div>
                                @endif

                                {{-- Progress 2: Pencadangan Bulan Ini --}}
                                <div style="margin-top: 10px; background: rgba(255,255,255,0.03); padding: 10px 12px; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                                    <div style="display: flex; justify-content: space-between; font-size: 11px; color: var(--text-secondary); margin-bottom: 6px;">
                                        <span>Cadangan Bulan Ini:</span>
                                        @if($item['is_fully_reserved'])
                                            <strong style="color: #86efac;">100% Tercukupi 🔒</strong>
                                        @else
                                            <strong style="color: #fbbf24;">Perlu: Rp {{ number_format($item['remaining_obligation'], 0, ',', '.') }}</strong>
                                        @endif
                                    </div>
                                    @php
                                        $reservePercent = $item['monthly_amount'] > 0 ? min(100, round(($item['already_reserved'] / $item['monthly_amount']) * 100)) : 100;
                                    @endphp
                                    <div style="width: 100%; height: 6px; background: rgba(255,255,255,0.1); border-radius: 9999px; overflow: hidden;">
                                        <div style="height: 100%; width: {{ $reservePercent }}%; background: linear-gradient(90deg, #06b6d4, #3b82f6); border-radius: 9999px;"></div>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 10px; color: var(--text-tertiary); margin-top: 6px;">
                                        <span>Terkunci: Rp {{ number_format($item['already_reserved'], 0, ',', '.') }}</span>
                                        @if(!$item['is_fully_reserved'] && !$item['is_paid'])
                                            <span style="color: #86efac; font-weight: 700;">
                                                Saran: Rp {{ number_format($item['daily_required'], 0, ',', '.') }} / hari
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                @if($item['notes'])
                                    <div style="font-size: 11px; color: var(--text-tertiary); font-style: italic; margin-top: 8px;">
                                        "{{ $item['notes'] }}"
                                    </div>
                                @endif
                            </div>

                            {{-- Actions --}}
                            <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.06);">
                                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                    @if(!$item['is_paid'] && $item['status'] === 'active')
                                        <button type="button" class="btn btn-outline btn-xs" onclick="openReserveModal({{ $item['id'] }}, '{{ addslashes($item['name']) }}', {{ $item['remaining_obligation'] }})">
                                            🔒 Cadangkan Dana
                                        </button>
                                        <button type="button" class="btn btn-success btn-xs" onclick="openPayInstallmentModal({{ $item['id'] }}, '{{ addslashes($item['name']) }}', {{ $item['monthly_amount'] }})">
                                            💸 Bayar Tagihan
                                        </button>
                                    @endif
                                </div>
                                <button type="button" class="btn-del-tx" title="Hapus Cicilan" onclick="deleteInstallment({{ $item['id'] }})">
                                    🗑️
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- TAB 5: Savings Goals (Phase 4) --}}
    <div class="finance-tab-section" id="tabSectionSavings" style="display: none;">
        {{-- Overview Metrics --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; margin-bottom: 24px;">
            <div class="glass-card" style="padding: 16px 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color); background: rgba(0,0,0,0.35);">
                <span style="font-size: 11px; color: var(--text-tertiary); text-transform: uppercase; font-weight: 700;">Total Tabungan Terkumpul</span>
                <div style="font-size: 20px; font-weight: 800; color: #a855f7; margin-top: 6px;">
                    Rp {{ number_format($savingsSummary['total_current'], 0, ',', '.') }}
                </div>
                <div style="font-size: 11px; color: var(--text-secondary); margin-top: 4px;">
                    🔒 Terkunci aman di pos cadangan tabungan
                </div>
            </div>

            <div class="glass-card" style="padding: 16px 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color); background: rgba(0,0,0,0.35);">
                <span style="font-size: 11px; color: var(--text-tertiary); text-transform: uppercase; font-weight: 700;">Total Target Finansial</span>
                <div style="font-size: 20px; font-weight: 800; color: #38bdf8; margin-top: 6px;">
                    Rp {{ number_format($savingsSummary['total_target'], 0, ',', '.') }}
                </div>
                <div style="font-size: 11px; color: var(--text-secondary); margin-top: 4px;">
                    {{ $savingsSummary['active_count'] }} target aktif · {{ $savingsSummary['achieved_count'] }} tercapai
                </div>
            </div>

            <div class="glass-card" style="padding: 16px 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color); background: rgba(0,0,0,0.35);">
                <span style="font-size: 11px; color: var(--text-tertiary); text-transform: uppercase; font-weight: 700;">Progres Keseluruhan</span>
                <div style="font-size: 20px; font-weight: 800; color: #34d399; margin-top: 6px;">
                    {{ $savingsSummary['overall_progress'] }}%
                </div>
                <div style="font-size: 11px; color: var(--text-secondary); margin-top: 4px;">
                    Sisa Rp {{ number_format($savingsSummary['total_remaining'], 0, ',', '.') }} untuk dicapai
                </div>
            </div>

            <div class="glass-card" style="padding: 16px 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color); background: rgba(0,0,0,0.35);">
                <span style="font-size: 11px; color: var(--text-tertiary); text-transform: uppercase; font-weight: 700;">Rekomendasi Tabungan Hari Ini</span>
                <div style="font-size: 20px; font-weight: 800; color: #facc15; margin-top: 6px;">
                    Rp {{ number_format($savingsSummary['total_daily_required_today'], 0, ',', '.') }} / hari
                </div>
                <div style="font-size: 11px; color: var(--text-secondary); margin-top: 4px;">
                    Untuk target bertenggat waktu (deadline)
                </div>
            </div>
        </div>

        {{-- Goals List Section --}}
        <div class="glass-card" style="padding: 24px; border-radius: var(--radius-lg); border: 1px solid var(--border-color);">
            <div class="card-header-flex">
                <div>
                    <h3 class="card-title-sm">🎯 Target Tabungan & Dana Impian</h3>
                    <p class="card-sub-sm">
                        Mendukung <strong>Target Bertenggat Waktu (Deadline)</strong> dengan hitungan harian/mingguan dan uji ketercapaian (affordability), serta <strong>Target Terbuka (Open-ended)</strong> untuk akumulasi bertahap.
                    </p>
                </div>
                <button type="button" class="btn btn-primary btn-sm" onclick="openSavingsGoalModal()">
                    + Tambah Target Tabungan
                </button>
            </div>

            @if(empty($savingsSummary['items']))
                <div class="empty-state-box">
                    <span style="font-size: 36px;">🎯</span>
                    <p style="margin: 10px 0 0 0; color: #fff; font-size: 14px; font-weight: 700;">
                        Belum ada target tabungan yang dibuat!
                    </p>
                    <p style="margin: 4px 0 16px 0; color: var(--text-tertiary); font-size: 12px;">
                        Mulai sisihkan dana untuk barang impian, dana darurat, atau rencana masa depan.
                    </p>
                    <button type="button" class="btn btn-outline btn-sm" onclick="openSavingsGoalModal()">
                        + Buat Target Tabungan Sekarang
                    </button>
                </div>
            @else
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 18px; margin-top: 16px;">
                    @foreach($savingsSummary['items'] as $item)
                        <div class="glass-card" style="padding: 18px; border-radius: var(--radius-md); border: 1px solid rgba(255,255,255,0.08); background: rgba(0,0,0,0.4); display: flex; flex-direction: column; justify-content: space-between; gap: 16px;">
                            <div>
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px;">
                                    <div style="display: flex; gap: 12px; align-items: flex-start;">
                                        <div style="width: 42px; height: 42px; border-radius: 10px; background: rgba(255,255,255,0.06); border: 1px solid {{ $item['color'] }}; display: flex; align-items: center; justify-content: center; font-size: 22px;">
                                            {{ $item['icon'] }}
                                        </div>
                                        <div>
                                            <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap; margin-bottom: 2px;">
                                                @if($item['type'] === 'deadline')
                                                    <span style="font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 9999px; background: rgba(56, 189, 248, 0.15); color: #38bdf8;">
                                                        ⏳ Target Deadline
                                                    </span>
                                                @else
                                                    <span style="font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 9999px; background: rgba(168, 85, 247, 0.15); color: #d8b4fe;">
                                                        ♾️ Tabungan Terbuka
                                                    </span>
                                                @endif

                                                @if($item['status'] === 'achieved')
                                                    <span style="font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 9999px; background: rgba(34, 197, 94, 0.18); color: #86efac;">
                                                        🎉 Target Tercapai!
                                                    </span>
                                                @endif
                                            </div>
                                            <h4 style="font-size: 16px; font-weight: 800; color: #fff; margin: 0;">{{ $item['name'] }}</h4>
                                            <div style="font-size: 11px; color: var(--text-secondary); margin-top: 2px;">
                                                Target: <strong style="color: #38bdf8;">Rp {{ number_format($item['target_amount'], 0, ',', '.') }}</strong>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        @if($item['is_achieved'])
                                            <span style="font-size: 11px; font-weight: 700; color: #86efac; background: rgba(34, 197, 94, 0.15); padding: 4px 8px; border-radius: 6px;">
                                                ✅ Selesai
                                            </span>
                                        @elseif($item['type'] === 'deadline')
                                            @if($item['is_due_today'])
                                                <span style="font-size: 11px; font-weight: 800; color: #ef4444; background: rgba(239, 68, 68, 0.2); padding: 4px 8px; border-radius: 6px;">
                                                    ⚠️ Deadline Hari Ini!
                                                </span>
                                            @elseif($item['is_overdue'])
                                                <span style="font-size: 11px; font-weight: 800; color: #f87171; background: rgba(239, 68, 68, 0.15); padding: 4px 8px; border-radius: 6px;">
                                                    🚨 Lewat Deadline
                                                </span>
                                            @else
                                                <span style="font-size: 11px; font-weight: 700; color: #93c5fd; background: rgba(59, 130, 246, 0.15); padding: 4px 8px; border-radius: 6px;">
                                                    ⏳ {{ $item['days_remaining'] }} hari lagi ({{ $item['target_date'] }})
                                                </span>
                                            @endif
                                        @endif
                                    </div>
                                </div>

                                {{-- Progress Meter --}}
                                <div style="margin-top: 14px; background: rgba(255,255,255,0.03); padding: 12px; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                                    <div style="display: flex; justify-content: space-between; font-size: 11px; color: var(--text-secondary); margin-bottom: 6px;">
                                        <span>Progres Terkumpul:</span>
                                        <strong style="color: #fff;">{{ $item['progress_percent'] }}%</strong>
                                    </div>
                                    <div style="width: 100%; height: 8px; background: rgba(255,255,255,0.1); border-radius: 9999px; overflow: hidden;">
                                        <div style="height: 100%; width: {{ $item['progress_percent'] }}%; background: linear-gradient(90deg, #10b981, #06b6d4); border-radius: 9999px;"></div>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; font-size: 11px; color: var(--text-tertiary); margin-top: 6px;">
                                        <span>Terkumpul: <strong style="color: #86efac;">Rp {{ number_format($item['current_amount'], 0, ',', '.') }}</strong></span>
                                        <span>Sisa: Rp {{ number_format($item['remaining_amount'], 0, ',', '.') }}</span>
                                    </div>
                                </div>

                                {{-- Deadline Analysis & Affordability --}}
                                @if($item['type'] === 'deadline' && !$item['is_achieved'])
                                    <div style="margin-top: 10px; padding: 10px 12px; background: rgba(255,255,255,0.02); border-radius: var(--radius-sm); border: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                                        <div>
                                            <span style="font-size: 10px; color: var(--text-tertiary); text-transform: uppercase;">Rekomendasi Rutin:</span>
                                            <div style="font-size: 12px; font-weight: 800; color: #facc15;">
                                                Rp {{ number_format($item['daily_required'], 0, ',', '.') }} / hari
                                                <span style="font-size: 10px; font-weight: 400; color: var(--text-secondary);">(Rp {{ number_format($item['weekly_required'], 0, ',', '.') }}/mgg)</span>
                                            </div>
                                        </div>

                                        <div>
                                            @switch($item['affordability'])
                                                @case('achievable')
                                                    <span style="font-size: 10px; font-weight: 700; color: #86efac; background: rgba(34, 197, 94, 0.15); padding: 3px 8px; border-radius: 6px;">
                                                        🟢 Realistis
                                                    </span>
                                                    @break
                                                @case('demanding')
                                                    <span style="font-size: 10px; font-weight: 700; color: #fbbf24; background: rgba(251, 191, 36, 0.15); padding: 3px 8px; border-radius: 6px;">
                                                        🟡 Perlu Disiplin
                                                    </span>
                                                    @break
                                                @case('unrealistic')
                                                    <span style="font-size: 10px; font-weight: 700; color: #f87171; background: rgba(239, 68, 68, 0.15); padding: 3px 8px; border-radius: 6px;">
                                                        🔴 Menantang
                                                    </span>
                                                    @break
                                                @default
                                                    <span style="font-size: 10px; color: var(--text-tertiary);">
                                                        ⚪ Fleksibel
                                                    </span>
                                            @endswitch
                                        </div>
                                    </div>
                                @endif

                                @if($item['notes'])
                                    <div style="font-size: 11px; color: var(--text-tertiary); font-style: italic; margin-top: 8px;">
                                        "{{ $item['notes'] }}"
                                    </div>
                                @endif
                            </div>

                            {{-- Actions --}}
                            <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.06);">
                                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                    <button type="button" class="btn btn-outline btn-xs" onclick="openSaveFundsModal({{ $item['id'] }}, '{{ addslashes($item['name']) }}', {{ $item['remaining_amount'] }})">
                                        💰 + Tabung Dana
                                    </button>
                                    @if($item['current_amount'] > 0)
                                        <button type="button" class="btn btn-secondary btn-xs" onclick="openWithdrawSavingsModal({{ $item['id'] }}, '{{ addslashes($item['name']) }}', {{ $item['current_amount'] }})">
                                            📤 Tarik / Gunakan
                                        </button>
                                    @endif
                                </div>
                                <button type="button" class="btn-del-tx" title="Hapus Target" onclick="deleteSavingsGoal({{ $item['id'] }})">
                                    🗑️
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- TAB 6: Intelligence, Safe-to-Spend & Forecast --}}
    <div class="finance-tab-section" id="tabSectionIntelligence" style="display: none;">
        <div style="display: flex; flex-direction: column; gap: 20px;">
            {{-- Top Hero Banner for Safe-to-Spend --}}
            <div class="glass-card" style="padding: 24px; position: relative; overflow: hidden; border: 1px solid rgba(255, 255, 255, 0.08);">
                <div style="position: absolute; right: -20px; top: -20px; width: 140px; height: 140px; background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, rgba(0,0,0,0) 70%); pointer-events: none;"></div>
                
                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                            <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #a5b4fc; background: rgba(99, 102, 241, 0.15); padding: 3px 8px; border-radius: 6px;">
                                🛡️ Smart Financial Engine
                            </span>
                            @if($safeToSpend['status_tone'] === 'positive')
                                <span style="font-size: 11px; font-weight: 700; color: #86efac; background: rgba(34, 197, 94, 0.15); padding: 3px 8px; border-radius: 6px;">
                                    🟢 AMAN BELANJA
                                </span>
                            @elseif($safeToSpend['status_tone'] === 'cautious')
                                <span style="font-size: 11px; font-weight: 700; color: #fde047; background: rgba(234, 179, 8, 0.15); padding: 3px 8px; border-radius: 6px;">
                                    🟡 PERLU DISIPLIN
                                </span>
                            @else
                                <span style="font-size: 11px; font-weight: 700; color: #f87171; background: rgba(239, 68, 68, 0.15); padding: 3px 8px; border-radius: 6px;">
                                    🔴 OVERSPENT / DEFISIT
                                </span>
                            @endif
                        </div>
                        <h3 style="font-size: 20px; font-weight: 800; color: #fff; margin-bottom: 4px;">
                            Safe-to-Spend Hari Ini ({{ now()->translatedFormat('l, d F Y') }})
                        </h3>
                        <p style="font-size: 12px; color: var(--text-secondary); max-width: 600px;">
                            Batas aman pengeluaran fleksibel hari ini setelah diproteksi dari kebutuhan pokok, cadangan cicilan, dan target tabungan.
                        </p>
                    </div>

                    <div style="text-align: right;">
                        <span style="font-size: 11px; color: var(--text-tertiary); text-transform: uppercase;">Plafon Aman Fleksibel Hari Ini</span>
                        <div style="font-size: 32px; font-weight: 900; color: {{ $safeToSpend['safe_to_spend_today'] > 0 ? '#4ade80' : '#f87171' }}; line-height: 1.1;">
                            Rp {{ number_format($safeToSpend['safe_to_spend_today'], 0, ',', '.') }}
                        </div>
                        <div style="font-size: 11px; color: var(--text-secondary); margin-top: 4px;">
                            Realisasi pengeluaran hari ini: <strong style="color: #cbd5e1;">Rp {{ number_format($safeToSpend['actual_spent_today'], 0, ',', '.') }}</strong>
                        </div>
                    </div>
                </div>

                {{-- Metric Breakdown Grid --}}
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-top: 20px; padding-top: 16px; border-top: 1px solid rgba(255, 255, 255, 0.06);">
                    <div style="background: rgba(255, 255, 255, 0.02); padding: 10px 14px; border-radius: var(--radius-sm); border: 1px solid rgba(255, 255, 255, 0.04);">
                        <span style="font-size: 10px; color: var(--text-tertiary); text-transform: uppercase;">Pemasukan Hari Ini</span>
                        <div style="font-size: 14px; font-weight: 700; color: #86efac; margin-top: 2px;">
                            Rp {{ number_format($safeToSpend['expected_income_today'], 0, ',', '.') }}
                        </div>
                        <div style="font-size: 10px; color: var(--text-secondary);">Ekspektasi harian</div>
                    </div>

                    <div style="background: rgba(255, 255, 255, 0.02); padding: 10px 14px; border-radius: var(--radius-sm); border: 1px solid rgba(255, 255, 255, 0.04);">
                        <span style="font-size: 10px; color: var(--text-tertiary); text-transform: uppercase;">Komitmen Pokok (Allowance)</span>
                        <div style="font-size: 14px; font-weight: 700; color: #facc15; margin-top: 2px;">
                            - Rp {{ number_format($safeToSpend['essential_allowance_today'], 0, ',', '.') }}
                        </div>
                        <div style="font-size: 10px; color: var(--text-secondary);">Makan, bensin, pulsa dll</div>
                    </div>

                    <div style="background: rgba(255, 255, 255, 0.02); padding: 10px 14px; border-radius: var(--radius-sm); border: 1px solid rgba(255, 255, 255, 0.04);">
                        <span style="font-size: 10px; color: var(--text-tertiary); text-transform: uppercase;">Penyisihan Cicilan</span>
                        <div style="font-size: 14px; font-weight: 700; color: #f472b6; margin-top: 2px;">
                            - Rp {{ number_format($safeToSpend['installment_daily_today'], 0, ',', '.') }}
                        </div>
                        <div style="font-size: 10px; color: var(--text-secondary);">Porsi harian tagihan</div>
                    </div>

                    <div style="background: rgba(255, 255, 255, 0.02); padding: 10px 14px; border-radius: var(--radius-sm); border: 1px solid rgba(255, 255, 255, 0.04);">
                        <span style="font-size: 10px; color: var(--text-tertiary); text-transform: uppercase;">Penyisihan Tabungan</span>
                        <div style="font-size: 14px; font-weight: 700; color: #c084fc; margin-top: 2px;">
                            - Rp {{ number_format($safeToSpend['savings_daily_today'], 0, ',', '.') }}
                        </div>
                        <div style="font-size: 10px; color: var(--text-secondary);">Target deadline aktif</div>
                    </div>

                    <div style="background: rgba(255, 255, 255, 0.02); padding: 10px 14px; border-radius: var(--radius-sm); border: 1px solid rgba(255, 255, 255, 0.04);">
                        <span style="font-size: 10px; color: var(--text-tertiary); text-transform: uppercase;">Uang Aman Kas (Available)</span>
                        <div style="font-size: 14px; font-weight: 700; color: #38bdf8; margin-top: 2px;">
                            Rp {{ number_format($safeToSpend['available_cash'], 0, ',', '.') }}
                        </div>
                        <div style="font-size: 10px; color: var(--text-secondary);">Saldo bebas cadangan</div>
                    </div>

                    <div style="background: rgba(255, 255, 255, 0.02); padding: 10px 14px; border-radius: var(--radius-sm); border: 1px solid rgba(255, 255, 255, 0.04);">
                        <span style="font-size: 10px; color: var(--text-tertiary); text-transform: uppercase;">Hard Limit Hari Ini</span>
                        <div style="font-size: 14px; font-weight: 700; color: #a78bfa; margin-top: 2px;">
                            Rp {{ number_format($safeToSpend['daily_hard_limit'], 0, ',', '.') }}
                        </div>
                        <div style="font-size: 10px; color: var(--text-secondary);">Plafon maks belanja</div>
                    </div>
                </div>
            </div>

            {{-- 2 Columns: Monthly Status & Insights vs Date Simulator --}}
            <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 20px;">
                {{-- Column Left: Monthly Forecast Status & Insights --}}
                <div class="glass-card" style="padding: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                        <h4 style="font-size: 15px; font-weight: 700; color: #fff;">
                            🗓️ Status & Proyeksi Bulan {{ now()->translatedFormat('F Y') }}
                        </h4>
                        <span style="font-size: 12px; font-weight: 800; padding: 4px 10px; border-radius: 8px; color: {{ $monthlyIntelligence['status_color'] }}; background: rgba(255, 255, 255, 0.05); border: 1px solid {{ $monthlyIntelligence['status_color'] }}44;">
                            {{ $monthlyIntelligence['status_label'] }}
                        </span>
                    </div>

                    {{-- Monthly Math Table Summary --}}
                    <div style="display: flex; flex-direction: column; gap: 8px; font-size: 13px;">
                        <div style="display: flex; justify-content: space-between; padding: 8px 10px; background: rgba(255,255,255,0.02); border-radius: 6px;">
                            <span style="color: var(--text-secondary);">Total Proyeksi Pemasukan:</span>
                            <strong style="color: #86efac;">Rp {{ number_format($monthlyIntelligence['total_income'], 0, ',', '.') }}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 8px 10px; background: rgba(255,255,255,0.02); border-radius: 6px;">
                            <span style="color: var(--text-secondary);">Total Kebutuhan Pokok:</span>
                            <strong style="color: #facc15;">Rp {{ number_format($monthlyIntelligence['total_essential'], 0, ',', '.') }}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 8px 10px; background: rgba(255,255,255,0.02); border-radius: 6px;">
                            <span style="color: var(--text-secondary);">Total Kewajiban Cicilan:</span>
                            <strong style="color: #f472b6;">Rp {{ number_format($monthlyIntelligence['total_installments'], 0, ',', '.') }}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 8px 10px; background: rgba(255,255,255,0.02); border-radius: 6px;">
                            <span style="color: var(--text-secondary);">Target Tabungan Aktif:</span>
                            <strong style="color: #c084fc;">Rp {{ number_format($monthlyIntelligence['total_savings_target'], 0, ',', '.') }}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 10px 10px; background: rgba(255,255,255,0.04); border-radius: 6px; border-top: 1px solid rgba(255,255,255,0.08);">
                            <span style="color: #fff; font-weight: 700;">Proyeksi Buffer / Surplus Akhir:</span>
                            <strong style="color: {{ $monthlyIntelligence['projected_buffer'] >= 0 ? '#4ade80' : '#f87171' }}; font-size: 15px;">
                                Rp {{ number_format($monthlyIntelligence['projected_buffer'], 0, ',', '.') }}
                                <span style="font-size: 11px; font-weight: 400; color: var(--text-secondary);">({{ $monthlyIntelligence['buffer_percent'] }}%)</span>
                            </strong>
                        </div>
                    </div>

                    {{-- Empirical Insights List --}}
                    <div style="margin-top: 20px;">
                        <h5 style="font-size: 12px; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 10px;">
                            💡 Analisis & Rekomendasi Kuantitatif
                        </h5>
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            @foreach($monthlyIntelligence['insights'] as $insight)
                                <div style="display: flex; align-items: flex-start; gap: 8px; font-size: 12px; line-height: 1.5; color: #cbd5e1; background: rgba(255,255,255,0.015); padding: 8px 12px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.04);">
                                    <span>👉</span>
                                    <div>{{ $insight }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Column Right: Interactive Date Simulator --}}
                <div class="glass-card" style="padding: 20px;">
                    <h4 style="font-size: 15px; font-weight: 700; color: #fff; margin-bottom: 6px;">
                        🔮 Simulator Safe-to-Spend
                    </h4>
                    <p style="font-size: 12px; color: var(--text-secondary); margin-bottom: 16px;">
                        Pilih tanggal di bulan berjalan untuk melihat simulasi plafon aman belanja, komitmen kebutuhan harian, dan ketersediaan kas.
                    </p>

                    <div style="display: flex; gap: 8px; margin-bottom: 16px;">
                        <input type="date" class="form-input" id="simDateInput" value="{{ now()->addDay()->toDateString() }}" min="{{ now()->startOfMonth()->toDateString() }}" max="{{ now()->endOfMonth()->toDateString() }}" style="flex: 1;">
                        <button type="button" class="btn btn-primary btn-sm" onclick="checkDateSafeToSpend()" id="btnSimulate">
                            ⚡ Hitung
                        </button>
                    </div>

                    {{-- Simulation Result Box --}}
                    <div id="simResultBox" style="background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.06); border-radius: var(--radius-sm); padding: 16px;">
                        <div style="text-align: center; color: var(--text-tertiary); font-size: 12px;" id="simEmptyText">
                            Pilih tanggal di atas lalu tekan tombol <strong>Hitung</strong> untuk menjalankan kalkulasi.
                        </div>
                        <div id="simDetails" style="display: none;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                                <span style="font-size: 12px; color: var(--text-secondary);" id="simDateLabel">Tanggal</span>
                                <span class="badge" id="simBadge" style="font-size: 10px; padding: 2px 8px; border-radius: 6px;">Status</span>
                            </div>
                            <div style="text-align: center; margin-bottom: 14px; padding: 10px; background: rgba(255,255,255,0.03); border-radius: 6px;">
                                <div style="font-size: 10px; color: var(--text-tertiary); text-transform: uppercase;">Plafon Safe-to-Spend</div>
                                <div style="font-size: 24px; font-weight: 800; color: #4ade80; margin-top: 2px;" id="simAmount">Rp 0</div>
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 6px; font-size: 11px;">
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: var(--text-secondary);">Ekspektasi Pemasukan:</span>
                                    <strong style="color: #86efac;" id="simIncome">Rp 0</strong>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: var(--text-secondary);">Komitmen Pokok:</span>
                                    <strong style="color: #facc15;" id="simEssential">Rp 0</strong>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: var(--text-secondary);">Porsi Cicilan:</span>
                                    <strong style="color: #f472b6;" id="simInstallment">Rp 0</strong>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: var(--text-secondary);">Porsi Tabungan:</span>
                                    <strong style="color: #c084fc;" id="simSavings">Rp 0</strong>
                                </div>
                                <div style="display: flex; justify-content: space-between; padding-top: 6px; border-top: 1px solid rgba(255,255,255,0.06);">
                                    <span style="color: #fff;">Saldo Kas Tersedia:</span>
                                    <strong style="color: #38bdf8;" id="simCash">Rp 0</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL 1: Catat Transaksi Baru --}}
<div class="modal-overlay" id="transactionModalOverlay">
    <div class="modal" id="transactionModal">
        <div class="modal-header">
            <h2 class="modal-title" id="transactionModalTitle">Catat Transaksi Kas</h2>
            <button class="modal-close" onclick="closeTransactionModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="transactionForm" onsubmit="submitTransaction(event)">
                <div class="form-row">
                    <div class="form-group">
                        <label>Jenis Transaksi <span class="required">*</span></label>
                        <select class="form-select" id="txTypeSelect" onchange="onTxTypeChange()" required>
                            <option value="expense">Pengeluaran (Expense)</option>
                            <option value="income">Pemasukan (Income)</option>
                            <option value="transfer">Transfer Antar Akun</option>
                            <option value="savings_allocation">Alokasi ke Cadangan Tabungan</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tanggal <span class="required">*</span></label>
                        <input type="date" class="form-input" id="txDateInput" value="{{ now()->toDateString() }}" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Akun Sumber <span class="required">*</span></label>
                        <select class="form-select" id="txAccountSelect" required>
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->name }} (Saldo: Rp {{ number_format($acc->current_balance, 0, ',', '.') }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" id="txDestAccountGroup" style="display: none;">
                        <label>Akun Tujuan Transfer <span class="required">*</span></label>
                        <select class="form-select" id="txDestAccountSelect">
                            <option value="">-- Pilih Akun Tujuan --</option>
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Nominal (Rp) <span class="required">*</span></label>
                    <input type="text" inputmode="numeric" class="form-input rupiah-input" id="txAmountInput" placeholder="Contoh: 50.000" required>
                </div>

                <div class="form-row">
                    <div class="form-group" id="txCategoryGroup">
                        <label>Kategori</label>
                        <select class="form-select" id="txCategorySelect">
                            <option value="">-- Tanpa Kategori --</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->icon }} {{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1.5;">
                        <label>Keterangan Transaksi <span class="required">*</span></label>
                        <input type="text" class="form-input" id="txDescInput" placeholder="Contoh: Makan siang warteg, Bensin, Freelance web" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Catatan Tambahan (Opsional)</label>
                    <input type="text" class="form-input" id="txNotesInput" placeholder="Catatan kecil...">
                </div>

                <div class="modal-actions" style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px;">
                    <button type="button" class="btn btn-outline" onclick="closeTransactionModal()">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmitTx">Simpan Transaksi</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL 2: Tambah Akun Keuangan --}}
<div class="modal-overlay" id="accountModalOverlay">
    <div class="modal" id="accountModal">
        <div class="modal-header">
            <h2 class="modal-title">Tambah Akun Keuangan</h2>
            <button class="modal-close" onclick="closeAccountModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="accountForm" onsubmit="submitAccount(event)">
                <div class="form-group">
                    <label>Nama Akun / Dompet <span class="required">*</span></label>
                    <input type="text" class="form-input" id="accNameInput" placeholder="Contoh: Rekening BCA, Dompet Fisik, GoPay" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Jenis Akun <span class="required">*</span></label>
                        <select class="form-select" id="accTypeSelect" required>
                            <option value="cash">💵 Uang Tunai (Cash)</option>
                            <option value="bank">🏛️ Rekening Bank</option>
                            <option value="ewallet">📱 E-Wallet (GoPay, OVO, Dana)</option>
                            <option value="savings">🔒 Rekening Tabungan Khusus</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Saldo Awal Saat Ini (Rp) <span class="required">*</span></label>
                        <input type="text" inputmode="numeric" class="form-input rupiah-input" id="accOpeningBalanceInput" value="0" placeholder="0" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Catatan Akun</label>
                    <input type="text" class="form-input" id="accNotesInput" placeholder="Catatan tambahan akun...">
                </div>

                <div class="modal-actions" style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px;">
                    <button type="button" class="btn btn-outline" onclick="closeAccountModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Akun</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL 3: Atur Pola Pemasukan Rutin (Weekday Amounts) --}}
<div class="modal-overlay" id="incomeScheduleModalOverlay">
    <div class="modal" id="incomeScheduleModal">
        <div class="modal-header">
            <h2 class="modal-title">Atur Pola Pemasukan Mingguan (Senin–Minggu)</h2>
            <button class="modal-close" onclick="closeIncomeScheduleModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="incomeScheduleForm" onsubmit="submitIncomeSchedule(event)">
                <p style="font-size: 12px; color: var(--text-secondary); margin-bottom: 14px;">
                    Masukkan ekspektasi uang saku atau penghasilan per hari. Hari tanpa pemasukan dapat diisi 0.
                </p>
                @php
                    $wkAmounts = $incomeSchedule->weekday_amounts ?? [];
                @endphp
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label>Senin (Rp)</label>
                        <input type="text" inputmode="numeric" class="form-input rupiah-input" id="schDay1" value="{{ number_format($wkAmounts['1'] ?? 50000, 0, ',', '.') }}">
                    </div>
                    <div class="form-group">
                        <label>Selasa (Rp)</label>
                        <input type="text" inputmode="numeric" class="form-input rupiah-input" id="schDay2" value="{{ number_format($wkAmounts['2'] ?? 30000, 0, ',', '.') }}">
                    </div>
                    <div class="form-group">
                        <label>Rabu (Rp)</label>
                        <input type="text" inputmode="numeric" class="form-input rupiah-input" id="schDay3" value="{{ number_format($wkAmounts['3'] ?? 50000, 0, ',', '.') }}">
                    </div>
                    <div class="form-group">
                        <label>Kamis (Rp)</label>
                        <input type="text" inputmode="numeric" class="form-input rupiah-input" id="schDay4" value="{{ number_format($wkAmounts['4'] ?? 20000, 0, ',', '.') }}">
                    </div>
                    <div class="form-group">
                        <label>Jumat (Rp)</label>
                        <input type="text" inputmode="numeric" class="form-input rupiah-input" id="schDay5" value="{{ number_format($wkAmounts['5'] ?? 50000, 0, ',', '.') }}">
                    </div>
                    <div class="form-group">
                        <label>Sabtu (Rp)</label>
                        <input type="text" inputmode="numeric" class="form-input rupiah-input" id="schDay6" value="{{ number_format($wkAmounts['6'] ?? 30000, 0, ',', '.') }}">
                    </div>
                </div>
                <div class="form-group" style="margin-top: 10px;">
                    <label>Minggu (Rp)</label>
                    <input type="text" inputmode="numeric" class="form-input rupiah-input" id="schDay7" value="{{ number_format($wkAmounts['7'] ?? 0, 0, ',', '.') }}">
                </div>

                <div class="modal-actions" style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px;">
                    <button type="button" class="btn btn-outline" onclick="closeIncomeScheduleModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Pola</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL 4: Tambah Pemasukan Ekstra / Override Tanggal --}}
<div class="modal-overlay" id="overrideModalOverlay">
    <div class="modal" id="overrideModal">
        <div class="modal-header">
            <h2 class="modal-title">Tambah Pemasukan Ekstra / Override Tanggal</h2>
            <button class="modal-close" onclick="closeOverrideModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="overrideForm" onsubmit="submitOverride(event)">
                <div class="form-row">
                    <div class="form-group">
                        <label>Tipe <span class="required">*</span></label>
                        <select class="form-select" id="ovIsExtraSelect" required>
                            <option value="1">🌟 Pemasukan Ekstra / Freelance (Tambahan)</option>
                            <option value="0">✏️ Override Harian (Mengganti nominal hari tersebut)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tanggal <span class="required">*</span></label>
                        <input type="date" class="form-input" id="ovDateInput" value="{{ now()->toDateString() }}" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Judul / Sumber <span class="required">*</span></label>
                    <input type="text" class="form-input" id="ovTitleInput" placeholder="Contoh: Freelance Landing Page, Hadiah Lomba" required>
                </div>

                <div class="form-group">
                    <label>Nominal (Rp) <span class="required">*</span></label>
                    <input type="text" inputmode="numeric" class="form-input rupiah-input" id="ovAmountInput" placeholder="Contoh: 500.000" required>
                </div>

                <div class="form-group">
                    <label>Catatan Tambahan</label>
                    <input type="text" class="form-input" id="ovNotesInput" placeholder="Catatan opsional...">
                </div>

                <div class="modal-actions" style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px;">
                    <button type="button" class="btn btn-outline" onclick="closeOverrideModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Jadwal</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL 5: Tambah Cicilan & Kewajiban Finansial --}}
{{-- MODAL 5: Tambah Cicilan / Tagihan Rutin --}}
<div class="modal-overlay" id="installmentModalOverlay">
    <div class="modal" id="installmentModal">
        <div class="modal-header">
            <h2 class="modal-title">Tambah Cicilan / Tagihan Rutin</h2>
            <button class="modal-close" onclick="closeInstallmentModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="installmentForm" onsubmit="submitInstallment(event)">
                <div class="form-group">
                    <label>Jenis Kewajiban Bulanan <span class="required">*</span></label>
                    <select class="form-select" id="instTypeSelect" onchange="onInstTypeChange()" required>
                        <option value="recurring_bill" selected>🔁 Pengeluaran Rutin Bulanan (WiFi, Kos, Listrik, Gym, Langganan Digital, dll)</option>
                        <option value="debt">💳 Cicilan Pinjaman / Kredit (Laptop, HP, Motor, Pinjaman — memiliki batas lunas)</option>
                    </select>
                </div>

                <div id="instRecurringNotice" style="padding: 10px 12px; background: rgba(168, 85, 247, 0.1); border: 1px solid rgba(168, 85, 247, 0.25); border-radius: var(--radius-sm); margin-bottom: 14px; font-size: 11px; color: #e9d5ff;">
                    💡 <strong>Tagihan Rutin:</strong> Pengeluaran berkelanjutan bulanan tanpa plafon pinjaman. Nominal akan dicadangkan dan ditagihkan rutin setiap bulan sesuai tanggal jatuh tempo.
                </div>

                <div class="form-group">
                    <label>Nama Tagihan / Cicilan <span class="required">*</span></label>
                    <input type="text" class="form-input" id="instNameInput" placeholder="Contoh: WiFi IndiHome, Kos Kamar 12, Membership Gym, Cicilan Laptop" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Kategori <span class="required">*</span></label>
                        <select class="form-select" id="instCategorySelect" required>
                            <optgroup label="Pengeluaran Rutin Bulanan">
                                <option value="wifi_internet">🌐 WiFi & Internet Rumah</option>
                                <option value="kos_sewa">🏠 Kos / Kontrakan / Sewa</option>
                                <option value="listrik_air">⚡ Listrik PLN & Air PDAM</option>
                                <option value="gym_fitness">🏋️ Gym & Kebugaran</option>
                                <option value="langganan_digital">🎬 Langganan Digital (Spotify/Netflix/SaaS)</option>
                                <option value="pulsa_paket">📱 Pulsa & Paket Data</option>
                                <option value="bpjs_asuransi">🏥 BPJS / Asuransi Kesehatan</option>
                                <option value="tagihan_rutin">📦 Tagihan Rutin Lainnya</option>
                            </optgroup>
                            <optgroup label="Cicilan Pinjaman / Kredit">
                                <option value="elektronik">💻 Gadget & Elektronik</option>
                                <option value="kendaraan">🛵 Kendaraan / Motor / Mobil</option>
                                <option value="pinjaman">💳 Pinjaman Bank / KTA / Paylater</option>
                                <option value="lainnya">🏷️ Cicilan Lainnya</option>
                            </optgroup>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tanggal Jatuh Tempo Tiap Bulan (1–31) <span class="required">*</span></label>
                        <input type="number" class="form-input" id="instDueDayInput" min="1" max="31" value="20" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Tagihan Bulanan (Rp) <span class="required">*</span></label>
                        <input type="text" inputmode="numeric" class="form-input rupiah-input" id="instMonthlyAmountInput" placeholder="Contoh: 350.000" required>
                    </div>
                    <div class="form-group" id="instTotalAmountGroup" style="display: none;">
                        <label>Total Plafon / Pinjaman (Rp) <span class="required">*</span></label>
                        <input type="text" inputmode="numeric" class="form-input rupiah-input" id="instTotalAmountInput" placeholder="Contoh: 6.000.000">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Tanggal Mulai Berlaku <span class="required">*</span></label>
                        <input type="date" class="form-input" id="instStartDateInput" value="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="form-group">
                        <label>Tanggal Berakhir (Opsional)</label>
                        <input type="date" class="form-input" id="instEndDateInput">
                    </div>
                </div>

                <div class="form-group">
                    <label>Catatan Tambahan (Opsional)</label>
                    <input type="text" class="form-input" id="instNotesInput" placeholder="ID Pelanggan, nomor meteran, catatan paket...">
                </div>

                <div class="modal-actions" style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px;">
                    <button type="button" class="btn btn-outline" onclick="closeInstallmentModal()">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmitInstallment">Simpan Komitmen</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL 6: Cadangkan Dana Cicilan (Reserve Allocation) --}}
<div class="modal-overlay" id="reserveModalOverlay">
    <div class="modal" id="reserveModal">
        <div class="modal-header">
            <h2 class="modal-title">🔒 Cadangkan Dana Cicilan (Reserve)</h2>
            <button class="modal-close" onclick="closeReserveModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="reserveForm" onsubmit="submitReserve(event)">
                <input type="hidden" id="reserveInstallmentId">

                <div style="padding: 12px 14px; background: rgba(56, 189, 248, 0.1); border: 1px solid rgba(56, 189, 248, 0.25); border-radius: var(--radius-sm); margin-bottom: 16px;">
                    <div style="font-size: 13px; font-weight: 800; color: #38bdf8;" id="reserveTargetTitle">Cicilan: -</div>
                    <div style="font-size: 11px; color: var(--text-secondary); margin-top: 3px;">
                        💡 Tindakan ini mengunci saldo kas Anda agar aman dari belanja bebas, <strong>tanpa mencatat pengeluaran riil</strong>. Uang tetap ada di rekening Anda.
                    </div>
                </div>

                <div class="form-group">
                    <label>Pilih Akun Kas Sumber Dana <span class="required">*</span></label>
                    <select class="form-select" id="reserveAccountSelect" required>
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}">
                                {{ $acc->name }} (Tersedia Bebas: Rp {{ number_format($acc->available_spending, 0, ',', '.') }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label>Nominal yang Ingin Dicadangkan (Rp) <span class="required">*</span></label>
                    <input type="text" inputmode="numeric" class="form-input rupiah-input" id="reserveAmountInput" placeholder="0" required>
                </div>

                <div class="form-group">
                    <label>Catatan Alokasi (Opsional)</label>
                    <input type="text" class="form-input" id="reserveNotesInput" placeholder="Contoh: Sisihan harian 40rb">
                </div>

                <div class="modal-actions" style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px;">
                    <button type="button" class="btn btn-outline" onclick="closeReserveModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Kunci Cadangan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL 7: Bayar Tagihan Cicilan (Expense Real) --}}
<div class="modal-overlay" id="payInstallmentModalOverlay">
    <div class="modal" id="payInstallmentModal">
        <div class="modal-header">
            <h2 class="modal-title">💸 Bayar Tagihan Cicilan</h2>
            <button class="modal-close" onclick="closePayInstallmentModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="payInstallmentForm" onsubmit="submitPayInstallment(event)">
                <input type="hidden" id="payInstallmentId">

                <div style="padding: 12px 14px; background: rgba(244, 114, 182, 0.1); border: 1px solid rgba(244, 114, 182, 0.25); border-radius: var(--radius-sm); margin-bottom: 16px;">
                    <div style="font-size: 13px; font-weight: 800; color: #f472b6;" id="payTargetTitle">Tagihan: -</div>
                    <div style="font-size: 11px; color: var(--text-secondary); margin-top: 3px;">
                        📌 Tindakan ini mencatat pengeluaran riil di buku kas dan <strong>secara otomatis melepaskan cadangan</strong> yang telah disisihkan di rekening tersebut.
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Akun Kas Pembayar <span class="required">*</span></label>
                        <select class="form-select" id="payAccountSelect" required>
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}">
                                    {{ $acc->name }} (Total Saldo: Rp {{ number_format($acc->current_balance, 0, ',', '.') }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tanggal Pembayaran <span class="required">*</span></label>
                        <input type="date" class="form-input" id="payDateInput" value="{{ now()->toDateString() }}" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Nominal Pembayaran (Rp) <span class="required">*</span></label>
                    <input type="text" inputmode="numeric" class="form-input rupiah-input" id="payAmountInput" placeholder="0" required>
                </div>

                <div class="form-group">
                    <label>Catatan / No. Resi Bukti Pembayaran</label>
                    <input type="text" class="form-input" id="payNotesInput" placeholder="Contoh: Transfer m-BCA ref #987213">
                </div>

                <div class="modal-actions" style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px;">
                    <button type="button" class="btn btn-outline" onclick="closePayInstallmentModal()">Batal</button>
                    <button type="submit" class="btn btn-success">Konfirmasi Bayar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL 8: Tambah Target Tabungan Baru --}}
<div class="modal-overlay" id="savingsGoalModalOverlay">
    <div class="modal" id="savingsGoalModal">
        <div class="modal-header">
            <h2 class="modal-title">Tambah Target Tabungan / Impian</h2>
            <button class="modal-close" onclick="closeSavingsGoalModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="savingsGoalForm" onsubmit="submitSavingsGoal(event)">
                <div class="form-group">
                    <label>Nama Target Tabungan <span class="required">*</span></label>
                    <input type="text" class="form-input" id="goalNameInput" placeholder="Contoh: Dana Darurat 3 Bulan, Beli iPhone 16 Pro, Liburan Jepang" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Jenis Target <span class="required">*</span></label>
                        <select class="form-select" id="goalTypeSelect" onchange="onGoalTypeChange()" required>
                            <option value="deadline">⏳ Target Bertenggat Waktu (Deadline)</option>
                            <option value="open_ended">♾️ Tabungan Terbuka (Tanpa Deadline)</option>
                        </select>
                    </div>
                    <div class="form-group" id="goalDateGroup">
                        <label>Tanggal Target (Deadline) <span class="required">*</span></label>
                        <input type="date" class="form-input" id="goalTargetDateInput">
                    </div>
                </div>

                <div class="form-group">
                    <label>Target Nominal yang Ingin Dicapai (Rp) <span class="required">*</span></label>
                    <input type="text" inputmode="numeric" class="form-input rupiah-input" id="goalTargetAmountInput" placeholder="Contoh: 10.000.000" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Icon Emoji</label>
                        <select class="form-select" id="goalIconSelect">
                            <option value="🎯">🎯 Target / Fokus</option>
                            <option value="💻">💻 Laptop / Gadget</option>
                            <option value="📱">📱 Smartphone / HP</option>
                            <option value="🏖️">🏖️ Liburan / Traveling</option>
                            <option value="🛵">🛵 Motor / Mobil</option>
                            <option value="🏠">🏠 Rumah / Properti</option>
                            <option value="🛡️">🛡️ Dana Darurat</option>
                            <option value="📚">📚 Pendidikan / Kursus</option>
                            <option value="💎">💎 Investasi / Emas</option>
                            <option value="🎒">🎒 Barang Hobi</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Warna Aksen</label>
                        <select class="form-select" id="goalColorSelect">
                            <option value="#10b981">🟢 Emerald Green</option>
                            <option value="#06b6d4">🔵 Cyan Blue</option>
                            <option value="#8b5cf6">🟣 Purple Violet</option>
                            <option value="#ec4899">🌸 Pink Rose</option>
                            <option value="#f59e0b">🟡 Amber Gold</option>
                            <option value="#3b82f6">🔷 Sapphire Blue</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Catatan / Alasan Menabung</label>
                    <input type="text" class="form-input" id="goalNotesInput" placeholder="Motivasi atau catatan spesifikasi...">
                </div>

                <div class="modal-actions" style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px;">
                    <button type="button" class="btn btn-outline" onclick="closeSavingsGoalModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Target</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL 9: Alokasikan Tabungan ke Target (Save Funds) --}}
<div class="modal-overlay" id="saveFundsModalOverlay">
    <div class="modal" id="saveFundsModal">
        <div class="modal-header">
            <h2 class="modal-title">💰 + Tabung Dana ke Target</h2>
            <button class="modal-close" onclick="closeSaveFundsModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="saveFundsForm" onsubmit="submitSaveFunds(event)">
                <input type="hidden" id="saveFundsGoalId">

                <div style="padding: 12px 14px; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.25); border-radius: var(--radius-sm); margin-bottom: 16px;">
                    <div style="font-size: 13px; font-weight: 800; color: #10b981;" id="saveFundsTargetTitle">Target: -</div>
                    <div style="font-size: 11px; color: var(--text-secondary); margin-top: 3px;">
                        💡 Dana akan disisihkan ke <strong>Pos Cadangan Tabungan (Savings Reserve)</strong>. Uang tetap ada di rekening kas Anda, namun dilindungi agar tidak terpakai belanja bebas.
                    </div>
                </div>

                <div class="form-group">
                    <label>Pilih Akun Kas Sumber Dana <span class="required">*</span></label>
                    <select class="form-select" id="saveFundsAccountSelect" required>
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}">
                                {{ $acc->name }} (Saldo Bebas: Rp {{ number_format($acc->available_spending, 0, ',', '.') }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Nominal yang Ingin Ditabung (Rp) <span class="required">*</span></label>
                        <input type="text" inputmode="numeric" class="form-input rupiah-input" id="saveFundsAmountInput" placeholder="0" required>
                    </div>
                    <div class="form-group">
                        <label>Tanggal <span class="required">*</span></label>
                        <input type="date" class="form-input" id="saveFundsDateInput" value="{{ now()->toDateString() }}" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Catatan Alokasi (Opsional)</label>
                    <input type="text" class="form-input" id="saveFundsNotesInput" placeholder="Contoh: Sisa uang saku minggu ini">
                </div>

                <div class="modal-actions" style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px;">
                    <button type="button" class="btn btn-outline" onclick="closeSaveFundsModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Kunci Tabungan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL 10: Tarik / Gunakan Dana Tabungan --}}
<div class="modal-overlay" id="withdrawSavingsModalOverlay">
    <div class="modal" id="withdrawSavingsModal">
        <div class="modal-header">
            <h2 class="modal-title">📤 Tarik / Gunakan Dana Tabungan</h2>
            <button class="modal-close" onclick="closeWithdrawSavingsModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="withdrawSavingsForm" onsubmit="submitWithdrawSavings(event)">
                <input type="hidden" id="withdrawGoalId">

                <div style="padding: 12px 14px; background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.25); border-radius: var(--radius-sm); margin-bottom: 16px;">
                    <div style="font-size: 13px; font-weight: 800; color: #fbbf24;" id="withdrawTargetTitle">Target: -</div>
                    <div style="font-size: 11px; color: var(--text-secondary); margin-top: 3px;">
                        Pilih apakah Anda ingin melepaskan dana tabungan kembali ke saldo belanja bebas, atau mencatatnya sebagai pengeluaran riil untuk membeli target impian Anda.
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Akun Kas Rekening <span class="required">*</span></label>
                        <select class="form-select" id="withdrawAccountSelect" required>
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}">
                                    {{ $acc->name }} (Cadangan Tabungan: Rp {{ number_format($acc->savings_reserve, 0, ',', '.') }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nominal yang Ditarik (Rp) <span class="required">*</span></label>
                        <input type="text" inputmode="numeric" class="form-input rupiah-input" id="withdrawAmountInput" placeholder="0" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Tujuan Penarikan <span class="required">*</span></label>
                    <select class="form-select" id="withdrawIsExpenseSelect" required>
                        <option value="0">↩️ Kembalikan ke Saldo Kas Bebas (Batal menabung / darurat)</option>
                        <option value="1">🛍️ Belanjakan untuk Target Ini (Catat Pengeluaran Riil di Buku Kas!)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Catatan Penarikan</label>
                    <input type="text" class="form-input" id="withdrawNotesInput" placeholder="Keterangan penarikan...">
                </div>

                <div class="modal-actions" style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px;">
                    <button type="button" class="btn btn-outline" onclick="closeWithdrawSavingsModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Proses Penarikan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
