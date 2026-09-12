# PERSONAL PLANNER — FINANCE MODULE

## Goal
Tambahkan modul Finance ke aplikasi Personal Planner berbasis Laravel + React/Inertia + MySQL + PWA.

Modul ini bukan sekadar expense tracker. Tujuannya adalah menghitung cashflow pribadi, uang jajan harian/bulanan, income tambahan, installment/cicilan, kebutuhan minimum harian, safe-to-spend, dan target tabungan.

## Core Flow

INCOME + EXTRA INCOME
- INSTALLMENTS
- ESSENTIAL DAILY BUDGET
- SAVINGS ALLOCATION
= AVAILABLE / SAFE TO SPEND

Jangan mencampurkan balance, spending, installment reserve, dan savings reserve.

---

# 1. FINANCE DASHBOARD

Buat `/finance`, mobile-first.

Tampilkan:
- Current Balance
- Expected Income This Month
- Extra Income
- Monthly Installments
- Daily Essential Budget
- Savings Goal
- Today's Income
- Today's Safe to Spend
- Today's Recommended Savings
- Upcoming installment
- Savings progress
- Financial alerts

Contoh:
Income Rp2.000.000
Installments Rp650.000
Essential Budget Rp900.000
Planned Savings Rp300.000
Projected Buffer Rp150.000

---

# 2. INCOME SYSTEM

Support:

### Monthly Income
Contoh: uang bulanan Rp1.500.000.

### Daily Income
Nominal dapat berbeda berdasarkan hari:
- Monday Rp50.000
- Tuesday Rp30.000
- Wednesday Rp50.000
- Thursday Rp20.000
- Friday Rp50.000
- Saturday Rp30.000
- Sunday Rp0

### Specific Date Income
Contoh:
12 Sep +Rp100.000
20 Sep +Rp250.000

### Extra Income
One-time income:
Freelance Website +Rp500.000, 18 Sep.

Buat recurring pattern dan date-specific override. Override tidak boleh mengubah pattern permanen tanpa user action.

Estimasi monthly income harus memakai kalender aktual, bukan sekadar weekly income × 4.

---

# 3. ALLOWANCE / DAILY MONEY

User dapat menentukan:
- daily minimum food
- transport
- snack
- other essentials

Contoh:
Food Rp20.000
Transport Rp10.000
Other Rp5.000
Minimum Rp35.000/day.

Hitung juga nominal yang masih dapat ditabung setiap hari.

---

# 4. INSTALLMENT SYSTEM

Entity installment minimal:
- name
- total_amount
- monthly_amount
- due_day
- start_date
- end_date / remaining_months
- category
- notes
- status

Contoh:
Laptop: Rp500.000/month, due 24
HP: Rp300.000/month, due 17

Tampilkan:
- monthly obligation
- next due date
- remaining installments
- remaining obligation
- amount allocated this month
- amount still needed
- required daily allocation

### Daily installment allocation
Contoh:
Installment Rp600.000
Already allocated Rp200.000
Remaining Rp400.000
10 days to due date
Required = Rp40.000/day.

Gunakan actual date arithmetic dan timezone. Konfigurasi apakah hari ini termasuk perhitungan.

Multiple installments harus dihitung terpisah berdasarkan due date dan kemudian dijumlahkan jika perlu.

---

# 5. SAVINGS GOALS

User dapat membuat target.

Contoh:
Target Rp3.000.000
Current saved Rp750.000
Deadline 30 November

Hitung:
- remaining
- progress %
- required daily saving
- required weekly saving
- required monthly saving
- projected completion date

Contoh:
Remaining Rp2.250.000
90 days
Required = Rp25.000/day.

Jika kemampuan saving saat ini hanya Rp15.000/day:
WARNING: Goal is underfunded.
Shortfall Rp10.000/day.

Berikan opsi:
- reduce flexible spending
- add extra income
- extend deadline
- reduce target

Jangan otomatis mengubah target.

---

# 6. SAFE TO SPEND

Ini fitur utama.

Formula konseptual:
Current Balance
+ Expected Remaining Income
- Upcoming Installments
- Essential Budget
- Required Savings
- Other committed allocations
= Safe / Flexible Spending

Contoh:
Balance Rp500.000
Expected income Rp1.000.000
Installments Rp400.000
Essential Rp600.000
Required savings Rp200.000
Safe flexible spending Rp300.000.

Jangan menyebut seluruh balance sebagai uang bebas digunakan.

Untuk TODAY:
Today's income
- essential
- installment allocation
- savings allocation
= safe flexible spending.

---

# 7. VARIABLE DAILY INCOME

WAJIB mendukung nominal berbeda per hari.

Contoh:
Monday Rp50k
Tuesday Rp30k
Wednesday Rp50k
Thursday Rp20k
Friday Rp50k
Saturday Rp30k
Sunday Rp0.

Hitung:
- expected weekly income
- expected monthly income
- expected savings
- installment coverage.

Support Actual Income vs Expected Income.

Contoh:
Expected Rp50k
Actual Rp30k
Variance -Rp20k.

Actual tidak boleh mengubah recurring pattern permanen tanpa confirmation.

---

# 8. MONTHLY FORECAST

Tampilkan:
Income
Extra Income
Installments
Essential Spending
Planned Savings
Projected Buffer

Status:
- HEALTHY
- ATTENTION
- TIGHT
- DEFICIT

Status harus berdasarkan calculation transparan.

---

# 9. TRANSACTIONS

Jika belum ada ledger, buat transaction system.

Types:
- income
- expense
- transfer
- savings allocation
- installment payment

Fields:
- amount
- date
- type
- category
- description
- related installment/goal jika ada

Savings allocation yang hanya memindahkan uang ke savings bucket adalah transfer/internal allocation, bukan expense konsumsi.

Gunakan integer minor units atau DECIMAL sesuai convention project. Hindari floating point untuk currency.

---

# 10. BALANCE BUCKETS

Pisahkan:
- Total Balance
- Available Spending
- Installment Reserve
- Savings Reserve

Contoh:
Total Rp2.000.000
Installment Reserve Rp500.000
Savings Reserve Rp700.000
Available Spending Rp800.000.

---

# 11. CALENDAR + TODAY INTEGRATION

Finance harus terintegrasi dengan calendar existing.

Contoh:
17 Sep — HP Installment Rp300.000
24 Sep — Laptop Installment Rp500.000
18 Sep — Freelance Income Rp500.000

Today juga menampilkan:
- Today's Income
- Essential Budget
- Installment Allocation
- Savings
- Safe to Spend
- Upcoming obligations
- Savings goal status.

Jangan membuat calendar system kedua.

---

# 12. FINANCIAL INSIGHTS

Insight hanya boleh berdasarkan data nyata.

Contoh:
- Average daily income this month is Rp42.000.
- Essential spending is approximately 58% of expected income.
- You need approximately Rp40.000/day for installment obligations.
- You are Rp150.000 ahead of savings plan.

Jika data belum cukup, jangan membuat insight seolah faktual.

---

# 13. RECOMMENDATION ENGINE

Sistem boleh menyarankan:
- nominal saving harian
- nominal installment reserve harian
- safe spending
- target affordability
- cara mengejar target

Gunakan istilah estimate/recommended/planned/projected.

Jangan menyajikan rekomendasi sebagai nasihat finansial pasti.

---

# 14. DATABASE GUIDANCE

Inspect existing schema first.

Possible entities:
- finance_accounts
- finance_transactions
- finance_income_sources
- finance_income_schedules
- finance_installments
- finance_installment_allocations
- finance_budgets
- finance_savings_goals
- finance_savings_allocations
- finance_categories

Jangan membuat tabel yang sudah memiliki equivalent.

Jika project convention memakai plain SQL, gunakan plain SQL dan jangan membuat Laravel migration untuk perubahan schema.

Gunakan foreign key dan index yang relevan.

---

# 15. SERVICE ARCHITECTURE

Jangan menaruh semua calculation di controller atau React.

Gunakan service/domain layer sesuai architecture existing.

Possible services:
- FinanceCalculationService
- IncomeForecastService
- InstallmentService
- SavingsGoalService
- BudgetService
- SafeToSpendService
- FinancialInsightService

Controller melakukan orchestration.
React melakukan presentation/state.

---

# 16. EDGE CASES

Test:
- income 0
- no installment
- multiple installments
- overdue installment
- installment due today/tomorrow
- completed installment
- savings goal without deadline
- completed savings goal
- negative cashflow
- variable daily income
- 28/29/30/31-day months
- leap year
- recurring schedule starts mid-month
- specific-date override
- actual vs expected income
- insufficient money for essentials
- extra income
- duplicate/reversed transactions
- timezone boundary.

---

# 17. SECURITY

Finance adalah data private.

Pastikan:
- authorization
- user ownership
- no cross-user access
- validation
- mass assignment protection
- secure API endpoints.

---

# 18. UX

Mobile-first.

3 aksi harus sangat cepat:
1. Add Income
2. Add Expense
3. Check Safe to Spend

Prioritaskan:
TODAY
SAFE TO SPEND
UPCOMING OBLIGATIONS
SAVINGS GOALS
MONTHLY FORECAST.

Jangan membuat dashboard penuh angka tanpa konteks.

---

# 19. IMPLEMENTATION PHASES

## Finance 1.1 — Foundation
- transactions
- income
- expense
- balance
- categories

## Finance 1.2 — Allowance
- daily income
- weekday pattern
- monthly calculation
- essential daily budget
- variable income

## Finance 1.3 — Installments
- installment CRUD
- monthly obligations
- due dates
- allocation
- required daily allocation
- overdue status

## Finance 1.4 — Savings
- savings goals
- daily/weekly/monthly target
- affordability check
- progress

## Finance 1.5 — Intelligence
- safe-to-spend
- monthly forecast
- financial status
- insights
- Today integration
- Calendar integration

---

# 20. ACCEPTANCE CRITERIA

Feature selesai jika:
- expected income akurat
- weekday income bisa berbeda
- specific date override bekerja
- extra income bekerja
- multiple installments bekerja
- daily installment allocation akurat
- essential daily budget bekerja
- safe-to-spend bekerja
- savings target bekerja
- daily/weekly/monthly savings requirement bekerja
- variable income memengaruhi forecast
- actual vs expected dapat dibandingkan
- calendar integration bekerja
- Today integration bekerja
- currency calculation akurat
- authorization aman
- mobile UX usable
- edge cases diuji.

---

# 21. AGENT WORKFLOW

Sebelum coding:
1. Inspect complete existing project.
2. Identify existing finance-related tables.
3. Identify authentication/user model.
4. Identify calendar/task architecture.
5. Identify Today dashboard.
6. Identify notification architecture.
7. Identify existing UI conventions.

Kemudian:
1. Propose schema changes.
2. Propose services.
3. Propose routes/API.
4. Propose components.
5. Implement phase by phase.
6. Test calculations.
7. Test authorization.
8. Test mobile UX.
9. Verify V1–V4 tetap berjalan.

DO NOT:
- rewrite application
- duplicate calendar/task system
- put financial calculations in React
- use AI blindly for currency calculations
- use approximate date arithmetic
- expose private finance data.

Long-term structure:

PERSONAL PLANNER
├── Schedule
├── Tasks
├── Events
├── AI
├── Productivity
└── Finance
    ├── Cashflow
    ├── Allowance
    ├── Installments
    ├── Budget
    ├── Savings
    ├── Forecast
    └── Financial Intelligence
