@extends('layouts.app')

@section('title', 'Produktivitas')
@section('page-title', 'Personal OS — Produktivitas & Rutinitas')

@section('content')
<div class="productivity-wrapper">
    {{-- Top Overview Banner --}}
    <div class="prod-hero-card">
        <div class="prod-hero-glow"></div>
        <div class="prod-hero-content">
            <div class="prod-hero-left">
                <span class="prod-badge-tag">⚡ Personal OS V4</span>
                <h2 class="prod-hero-title">Pusat Produktivitas & Pola Belajar</h2>
                <p class="prod-hero-desc">
                    Lacak fokus kerja mendalam, bangun konsistensi rutinitas (termasuk jadwal gym selang-seling), dan pelajari pola produktivitas harianmu.
                </p>
            </div>
            <div class="prod-hero-stats">
                <div class="prod-stat-pill">
                    <span class="stat-label">Fokus Hari Ini</span>
                    <strong class="stat-val text-accent">{{ $todayFocusMinutes }} mnt</strong>
                </div>
                <div class="prod-stat-pill">
                    <span class="stat-label">Selesai Sebelum Deadline</span>
                    <strong class="stat-val text-success">{{ $analytics['early_completion_rate'] }}%</strong>
                </div>
                <div class="prod-stat-pill">
                    <span class="stat-label">Jam Paling Fokus</span>
                    <strong class="stat-val text-purple">{{ $analytics['peak_productivity']['range'] }}</strong>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs Navigation --}}
    <div class="prod-tabs-bar">
        <button type="button" class="prod-tab-btn active" id="tabBtnFocus" onclick="switchProdTab('focus')">
            🎯 Mode Fokus
        </button>
        <button type="button" class="prod-tab-btn" id="tabBtnHabits" onclick="switchProdTab('habits')">
            🔄 Rutinitas & Habits
        </button>
        <button type="button" class="prod-tab-btn" id="tabBtnAnalytics" onclick="switchProdTab('analytics')">
            📊 Pola & Analitik
        </button>
        <button type="button" class="prod-tab-btn" id="tabBtnReview" onclick="switchProdTab('review')">
            📅 Review & Evaluasi
        </button>
    </div>

    {{-- TAB 1: Focus Mode --}}
    <div class="prod-tab-section active" id="tabSectionFocus">
        <div class="focus-grid">
            {{-- Left: Active Timer Card --}}
            <div class="focus-timer-card glass-card">
                <div class="focus-type-selector">
                    <button type="button" class="timer-type-btn active" data-minutes="25" onclick="selectTimerPreset(25, 'pomodoro', this)">
                        🍅 Pomodoro (25m)
                    </button>
                    <button type="button" class="timer-type-btn" data-minutes="50" onclick="selectTimerPreset(50, 'deep_work', this)">
                        ⚡ Deep Work (50m)
                    </button>
                    <button type="button" class="timer-type-btn" data-minutes="15" onclick="selectTimerPreset(15, 'custom', this)">
                        ☕ Sprint (15m)
                    </button>
                </div>

                <div class="focus-timer-dial">
                    <svg class="timer-svg" viewBox="0 0 220 220">
                        <circle class="timer-circle-bg" cx="110" cy="110" r="95"></circle>
                        <circle class="timer-circle-progress" id="timerProgressCircle" cx="110" cy="110" r="95"></circle>
                    </svg>
                    <div class="timer-display-wrap">
                        <div class="timer-digits" id="timerDigits">25:00</div>
                        <div class="timer-status-text" id="timerStatusText">Siap Fokus</div>
                    </div>
                </div>

                {{-- Task Attachment Dropdown --}}
                <div class="timer-task-select-group">
                    <label for="focusTaskSelect">Tautkan ke Tugas:</label>
                    <select id="focusTaskSelect" class="form-select">
                        <option value="">-- Tanpa Tugas (Fokus Bebas) --</option>
                        @foreach($activeTasks as $at)
                            <option value="{{ $at->id }}">{{ $at->title }} ({{ $at->priority_level ?? 'Normal' }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="timer-controls">
                    <button type="button" class="btn-timer-primary" id="btnTimerToggle" onclick="toggleTimer()">
                        ▶ Mulai Sesi
                    </button>
                    <button type="button" class="btn-timer-secondary" onclick="resetTimer()">
                        ↺ Reset
                    </button>
                    <button type="button" class="btn-timer-finish" id="btnTimerFinish" onclick="finishSessionManually()" style="display: none;">
                        ✓ Selesaikan Sesi
                    </button>
                </div>
            </div>

            {{-- Right: Recent Sessions & Focus Tips --}}
            <div class="focus-sidebar-column">
                <div class="focus-tips-card glass-card">
                    <h4 class="tips-card-heading">💡 Prinsip Deep Work</h4>
                    <ul class="tips-list">
                        <li>Jauhkan notifikasi smartphone selama 25–50 menit.</li>
                        <li>Fokus hanya pada 1 tugas tunggal (hindari multitasking).</li>
                        <li>Setelah 1 sesi selesai, ambil jeda peregangan 5 menit.</li>
                    </ul>
                </div>

                <div class="focus-history-card glass-card">
                    <h4 class="history-card-heading">⏱️ Sesi Terkini</h4>
                    @if($recentSessions->isEmpty())
                        <p style="font-size: 12px; color: var(--text-tertiary); margin-top: 8px;">
                            Belum ada sesi fokus yang tercatat. Tekan "Mulai Sesi" untuk memulai!
                        </p>
                    @else
                        <div class="recent-sessions-list">
                            @foreach($recentSessions as $sess)
                            <div class="recent-session-item">
                                <div>
                                    <strong style="font-size: 13px; color: #fff;">{{ $sess->task?->title ?? 'Sesi Fokus Bebas' }}</strong>
                                    <div style="font-size: 11px; color: var(--text-secondary); margin-top: 2px;">
                                        <span>{{ ucfirst($sess->type) }} · {{ $sess->duration_minutes }} menit</span>
                                    </div>
                                </div>
                                <span style="font-size: 11px; color: #86efac; font-weight: 700;">+{{ $sess->duration_minutes }}m</span>
                            </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- TAB 2: Habits & Alternating Gym Routine --}}
    <div class="prod-tab-section" id="tabSectionHabits" style="display: none;">
        <div class="habits-header-row">
            <div>
                <h3 style="margin: 0; color: #fff; font-size: 16px;">Rutinitas Harian & Jadwal Olahraga</h3>
                <p style="margin: 2px 0 0 0; font-size: 12px; color: var(--text-secondary);">
                    Mendukung rutinitas setiap hari maupun selang-seling (1 hari latihan, 1 hari istirahat) dengan proteksi streak.
                </p>
            </div>
            <button type="button" class="btn btn-primary btn-sm" onclick="openAddHabitModal()">
                + Tambah Rutinitas
            </button>
        </div>

        <div class="habits-grid">
            @foreach($habits as $h)
            <div class="habit-card glass-card {{ $h['is_completed'] ? 'habit-card-completed' : '' }} {{ $h['is_rest_day'] ? 'habit-card-rest' : '' }}" id="habit-card-{{ $h['id'] }}">
                <div class="habit-card-left">
                    <div class="habit-icon" style="background: rgba(255,255,255,0.06);">{{ $h['icon'] }}</div>
                    <div class="habit-info">
                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            <strong class="habit-title">{{ $h['title'] }}</strong>
                            <span class="habit-category-badge">{{ ucfirst($h['category']) }}</span>
                            @if($h['cadence'] === 'alternate_days')
                                <span class="habit-cadence-badge">Selang-Seling</span>
                            @endif
                        </div>
                        @if($h['description'])
                            <p class="habit-desc">{{ $h['description'] }}</p>
                        @endif
                        <div class="habit-status-tag {{ $h['is_rest_day'] ? 'tag-rest' : ($h['is_completed'] ? 'tag-done' : 'tag-active') }}">
                            {{ $h['status_label'] }}
                        </div>
                    </div>
                </div>

                <div class="habit-card-right">
                    <div class="habit-streak-badge" title="Streak Konsistensi">
                        <span class="flame-icon">🔥</span>
                        <span>{{ $h['streak'] }} Hari</span>
                    </div>

                    @if($h['is_rest_day'])
                        <button type="button" class="btn-habit-rest" disabled title="Rest Day: Istirahatkan otot & pulihkan energi">
                            🧘‍♂️ Rest Day
                        </button>
                    @else
                        <button type="button" class="btn-habit-check {{ $h['is_completed'] ? 'checked' : '' }}" onclick="toggleHabitCheckin({{ $h['id'] }}, this)">
                            {{ $h['is_completed'] ? '✓ Selesai' : 'Check-in' }}
                        </button>
                    @endif

                    <button type="button" class="btn-habit-del" onclick="deleteHabit({{ $h['id'] }})" title="Hapus Rutinitas">&times;</button>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- TAB 3: Productivity Analytics & Patterns --}}
    <div class="prod-tab-section" id="tabSectionAnalytics" style="display: none;">
        {{-- Empirical Insights Banner (Rule 27) --}}
        <div class="insights-banner-card">
            <div class="insights-banner-header">
                <span style="font-size: 16px;">🧠</span>
                <strong style="color: #fff; font-size: 14px;">Pola Produktivitas Personal</strong>
            </div>
            <div class="insights-list">
                @foreach($analytics['insights'] as $insight)
                <div class="insight-item">
                    <span class="insight-bullet">✦</span>
                    <span>{{ $insight }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Analytics Metrics Grid --}}
        <div class="analytics-metrics-grid">
            <div class="analytics-metric-box glass-card">
                <span class="metric-label">Tingkat Penyelesaian</span>
                <div class="metric-value text-success">{{ $analytics['completion_rate'] }}%</div>
                <span class="metric-hint">Dari seluruh tugas yang dibuat</span>
            </div>

            <div class="analytics-metric-box glass-card">
                <span class="metric-label">Early Completion</span>
                <div class="metric-value text-accent">{{ $analytics['early_completion_rate'] }}%</div>
                <span class="metric-hint">Selesai sebelum deadline</span>
            </div>

            <div class="analytics-metric-box glass-card">
                <span class="metric-label">Jam Paling Produktif</span>
                <div class="metric-value text-purple">{{ $analytics['peak_productivity']['range'] }}</div>
                <span class="metric-hint">Aktivitas pengerjaan tertinggi</span>
            </div>

            <div class="analytics-metric-box glass-card">
                <span class="metric-label">Hari Terbaik</span>
                <div class="metric-value text-yellow">{{ $analytics['best_day']['day_name'] }}</div>
                <span class="metric-hint">Beban terpadat: Hari {{ $analytics['most_overloaded_day']['day_name'] }}</span>
            </div>
        </div>

        {{-- 7-Day Visual Activity Bar Chart --}}
        <div class="activity-chart-card glass-card">
            <h4 class="chart-heading">📈 Aktivitas 7 Hari Terakhir (Menit Fokus & Tugas)</h4>
            <div class="activity-bar-chart">
                @foreach($analytics['weekly_chart'] as $dayData)
                <div class="chart-day-col">
                    <div class="chart-bar-wrap" title="{{ $dayData['focus_minutes'] }} menit fokus · {{ $dayData['tasks_completed'] }} tugas selesai">
                        <div class="chart-bar-fill" style="height: {{ min(100, max(12, $dayData['focus_minutes'] * 0.8)) }}%;"></div>
                    </div>
                    <span class="chart-day-name">{{ $dayData['day_name'] }}</span>
                    <span class="chart-day-val">{{ $dayData['focus_minutes'] }}m</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- TAB 4: Weekly & Monthly Reviews --}}
    <div class="prod-tab-section" id="tabSectionReview" style="display: none;">
        <div class="review-grid">
            {{-- Weekly Review --}}
            <div class="review-card glass-card">
                <div class="review-card-top">
                    <div>
                        <span class="review-sub">Review Mingguan</span>
                        <h3 class="review-title">{{ $weeklyReview['period_label'] }}</h3>
                    </div>
                    <span class="review-badge">{{ $weeklyReview['badge'] }}</span>
                </div>

                <div class="review-stats-grid">
                    <div class="review-stat-item">
                        <span>Tugas Dituntaskan</span>
                        <strong>{{ $weeklyReview['tasks_completed'] }} Tugas</strong>
                    </div>
                    <div class="review-stat-item">
                        <span>Jam Fokus</span>
                        <strong>{{ $weeklyReview['focus_hours'] }} Jam</strong>
                    </div>
                    <div class="review-stat-item">
                        <span>Kepatuhan Rutinitas</span>
                        <strong>{{ $weeklyReview['habit_consistency'] }}%</strong>
                    </div>
                    <div class="review-stat-item">
                        <span>Skor Evaluasi</span>
                        <strong>{{ $weeklyReview['score'] }}/100</strong>
                    </div>
                </div>

                <div class="review-reflection-box">
                    <div style="font-size: 11px; font-weight: 700; color: #c084fc; margin-bottom: 4px;">
                        ✨ Kesimpulan & Rekomendasi:
                    </div>
                    <p style="margin: 0; font-size: 12px; color: var(--text-primary); line-height: 1.55;">
                        {{ $weeklyReview['reflection'] }}
                    </p>
                </div>
            </div>

            {{-- Monthly Review --}}
            <div class="review-card glass-card">
                <div class="review-card-top">
                    <div>
                        <span class="review-sub">Review Bulanan</span>
                        <h3 class="review-title">{{ $monthlyReview['month_label'] }}</h3>
                    </div>
                </div>

                <div class="review-stats-grid">
                    <div class="review-stat-item">
                        <span>Tugas Selesai</span>
                        <strong>{{ $monthlyReview['completed_tasks'] }} Tugas</strong>
                    </div>
                    <div class="review-stat-item">
                        <span>Total Jam Fokus</span>
                        <strong>{{ $monthlyReview['focus_hours'] }} Jam</strong>
                    </div>
                    <div class="review-stat-item">
                        <span>Konsistensi Bulanan</span>
                        <strong>{{ $monthlyReview['consistency'] }}%</strong>
                    </div>
                </div>

                <div class="review-reflection-box">
                    <div style="font-size: 11px; font-weight: 700; color: #86efac; margin-bottom: 4px;">
                        📊 Retrospektif:
                    </div>
                    <p style="margin: 0; font-size: 12px; color: var(--text-primary); line-height: 1.55;">
                        {{ $monthlyReview['recap'] }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Add Habit Modal --}}
<div class="modal-overlay" id="addHabitModalOverlay">
    <div class="modal" id="addHabitModal">
        <div class="modal-header">
            <h2 class="modal-title">Tambah Rutinitas Baru</h2>
            <button class="modal-close" onclick="closeAddHabitModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="addHabitForm" onsubmit="submitNewHabit(event)">
                <div class="form-group">
                    <label>Nama Rutinitas <span class="required">*</span></label>
                    <input type="text" class="form-input" id="habitTitleInput" required placeholder="Contoh: Latihan Gym, Belajar Algoritma, Membaca">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Ikon Emoji</label>
                        <input type="text" class="form-input" id="habitIconInput" value="🏋️" maxlength="4">
                    </div>
                    <div class="form-group">
                        <label>Kategori</label>
                        <select class="form-select" id="habitCategorySelect">
                            <option value="kesehatan">Kesehatan & Fitness</option>
                            <option value="akademik">Akademik & Kuliah</option>
                            <option value="produktivitas">Produktivitas</option>
                            <option value="pribadi">Pribadi</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Pola / Ritme Rutinitas <span class="required">*</span></label>
                    <select class="form-select" id="habitCadenceSelect">
                        <option value="alternate_days">Selang-Seling (1 Hari Latihan, 1 Hari Istirahat / Rest Day)</option>
                        <option value="daily">Setiap Hari (Daily)</option>
                        <option value="specific_days">Hari Tertentu (Senin, Rabu, Jumat)</option>
                        <option value="weekly_target">Target Frekuensi Mingguan</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Deskripsi Tambahan</label>
                    <textarea class="form-input" id="habitDescInput" rows="2" placeholder="Catatan atau pengingat tentang rutinitas ini..."></textarea>
                </div>

                <div class="modal-actions" style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px;">
                    <button type="button" class="btn btn-outline" onclick="closeAddHabitModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Rutinitas</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
