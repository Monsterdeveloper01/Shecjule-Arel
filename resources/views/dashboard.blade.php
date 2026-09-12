@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="dashboard-grid">
    {{-- Stats Cards --}}
    <div class="stats-row">
        <div class="stat-card stat-pending">
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
            </div>
            <div class="stat-info">
                <span class="stat-number">{{ $stats['pending'] }}</span>
                <span class="stat-label">Pending</span>
            </div>
        </div>
        <div class="stat-card stat-progress">
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"></path>
                </svg>
            </div>
            <div class="stat-info">
                <span class="stat-number">{{ $stats['in_progress'] }}</span>
                <span class="stat-label">In Progress</span>
            </div>
        </div>
        <div class="stat-card stat-completed">
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </div>
            <div class="stat-info">
                <span class="stat-number">{{ $stats['completed_week'] }}</span>
                <span class="stat-label">Selesai Minggu Ini</span>
            </div>
        </div>
        <div class="stat-card stat-overdue">
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                    <line x1="12" y1="9" x2="12" y2="13"></line>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
            </div>
            <div class="stat-info">
                <span class="stat-number">{{ $stats['overdue'] }}</span>
                <span class="stat-label">Overdue</span>
            </div>
        </div>
    </div>

    {{-- TODAY INTELLIGENCE HUB --}}
    @if(isset($todayIntelligence))
    <div class="intelligence-card">
        <div class="intel-header">
            <div>
                <div class="intel-title-row">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--accent);">
                        <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"></path>
                    </svg>
                    <h3 class="intel-title">Today Intelligence Hub</h3>
                    <span class="intel-badge-overload overload-{{ strtolower($todayIntelligence['overload']->level) }}">
                        Status Beban: {{ $todayIntelligence['overload']->level }}
                    </span>
                </div>
                <p style="margin: 4px 0 0 0; font-size: 12.5px; color: var(--text-secondary);">
                    {{ $todayIntelligence['overload']->headline }} — {{ $todayIntelligence['overload']->recommendation }}
                </p>
            </div>
            <div>
                <span style="font-size: 11px; color: var(--text-tertiary); text-transform: uppercase; letter-spacing: 0.5px;">Total Waktu Luang Hari Ini:</span>
                <div style="font-size: 14px; font-weight: 700; color: #34d399; font-variant-numeric: tabular-nums;">
                    {{ $todayIntelligence['totalFreeHoursToday'] }} Jam Tersedia
                </div>
            </div>
        </div>

        {{-- AI Daily Briefing --}}
        @if(!empty($todayIntelligence['aiInsight']))
        <div class="ai-daily-briefing-box">
            <div class="ai-briefing-header">
                <div class="ai-briefing-title">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="ai-sparkle-icon">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
                    </svg>
                    <span>AI Daily Briefing</span>
                </div>
                <button type="button" class="ai-open-assistant-btn" onclick="openAiModal()">
                    Tanya AI ➔
                </button>
            </div>
            <p class="ai-briefing-body">
                {!! nl2br(e($todayIntelligence['aiInsight'])) !!}
            </p>
        </div>
        @endif

        <div class="intel-grid">
            {{-- 1. Next Up --}}
            <div class="intel-section-box">
                <div class="intel-box-label">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    <span>Agenda Berikutnya</span>
                </div>
                @if($todayIntelligence['nextUp'])
                    <div style="display: flex; flex-direction: column; gap: 2px;">
                        <strong style="font-size: 13px; color: var(--text-primary);">{{ $todayIntelligence['nextUp']['title'] }}</strong>
                        <span style="font-size: 11.5px; color: var(--text-secondary);">
                            {{ $todayIntelligence['nextUp']['time'] }}
                            @if($todayIntelligence['nextUp']['location'])
                                · {{ $todayIntelligence['nextUp']['location'] }}
                            @endif
                        </span>
                        <span style="font-size: 11px; color: #f59e0b; font-weight: 600; margin-top: 2px;">
                            {{ $todayIntelligence['nextUp']['countdown'] }}
                        </span>
                    </div>
                @else
                    <span style="font-size: 12px; color: var(--text-tertiary);">Tidak ada agenda tersisa hari ini.</span>
                @endif
            </div>

            {{-- 2. Smart Recommendation --}}
            <div class="intel-section-box">
                <div class="intel-box-label">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><circle cx="12" cy="12" r="6"></circle><circle cx="12" cy="12" r="2"></circle></svg>
                    <span>Rekomendasi Pengerjaan Tugas</span>
                </div>
                @if($todayIntelligence['recommendation'])
                    @php $rec = $todayIntelligence['recommendation']; @endphp
                    <div style="display: flex; flex-direction: column; gap: 3px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 6px;">
                            <strong style="font-size: 13px; color: var(--text-primary);">{{ $rec->task->title }}</strong>
                            <span class="risk-badge risk-{{ strtolower($rec->task->risk_level ?? 'aman') }}">{{ $rec->task->risk_level }}</span>
                        </div>
                        <span style="font-size: 11px; color: #34d399; font-weight: 500;">
                            Slot: {{ $rec->slot->formattedRange }} ({{ $rec->slot->formattedDuration }})
                        </span>
                        <span style="font-size: 11px; color: var(--text-secondary);">
                            {{ $rec->reason }}
                        </span>
                        <button class="recommendation-action-btn" onclick="editTask({{ $rec->task->id }}, {{ json_encode($rec->task) }})">
                            Mulai Kerjakan ({{ $rec->recommendedDurationMinutes }} mnt) ➔
                        </button>
                    </div>
                @else
                    <span style="font-size: 12px; color: var(--text-tertiary);">Semua tugas hari ini terkendali dengan baik.</span>
                @endif
            </div>

            {{-- 3. Free Slots --}}
            <div class="intel-section-box">
                <div class="intel-box-label">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    <span>Celah Waktu Kosong Hari Ini</span>
                </div>
                @if($todayIntelligence['freeSlots']->count() > 0)
                    <div class="free-slot-pills">
                        @foreach($todayIntelligence['freeSlots']->take(4) as $fSlot)
                            <span class="free-slot-pill {{ $fSlot->isAvailableNow ? 'slot-now' : '' }}" title="{{ $fSlot->formattedRange }}">
                                @if($fSlot->isAvailableNow) Sekarang: @endif
                                {{ $fSlot->formattedRange }} ({{ $fSlot->formattedDuration }})
                            </span>
                        @endforeach
                    </div>
                @else
                    <span style="font-size: 12px; color: var(--text-tertiary);">Jadwal hari ini padat penuh.</span>
                @endif
            </div>
        </div>
    </div>
    @endif

    <div class="dashboard-main">
        {{-- Calendar --}}
        <div class="calendar-card glass-card">
            <div class="calendar-header">
                <button class="cal-nav-btn" id="calPrev" aria-label="Bulan sebelumnya">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </button>
                <h2 class="cal-month-title" id="calMonthTitle"></h2>
                <button class="cal-nav-btn" id="calNext" aria-label="Bulan selanjutnya">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </button>
            </div>
            <div class="calendar-weekdays">
                <span>Min</span><span>Sen</span><span>Sel</span><span>Rab</span><span>Kam</span><span>Jum</span><span>Sab</span>
            </div>
            <div class="calendar-grid" id="calendarGrid"></div>
        </div>

        {{-- Today Sidebar --}}
        <div class="today-sidebar">
            {{-- Today's greeting --}}
            <div class="greeting-card">
                <h2 class="greeting-text">Hai, {{ session('user_name', 'User') }}!</h2>
                <p class="greeting-date">{{ now()->translatedFormat('l, d F Y') }}</p>
            </div>

            {{-- Personal OS Quick Widget --}}
            <div class="dashboard-widget-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                    <strong style="font-size: 13px; color: var(--text-primary); font-weight: 600;">Personal OS Hub</strong>
                    <a href="{{ route('productivity.index') }}" style="font-size: 11px; color: var(--text-secondary); text-decoration: none; font-weight: 600;">Buka OS ➔</a>
                </div>
                <p style="font-size: 11.5px; color: var(--text-secondary); margin: 0 0 10px 0; line-height: 1.4;">
                    Lacak rutinitas harian, status rest day gym, dan sesi fokus kerja mendalam.
                </p>
                <div style="display: flex; gap: 8px;">
                    <a href="{{ route('productivity.index') }}" class="btn btn-primary btn-sm" style="flex: 1; text-align: center; text-decoration: none; justify-content: center; font-size: 11px; padding: 6px 12px;">
                        Mulai Sesi Fokus
                    </a>
                </div>
            </div>

            {{-- Personal Finance Today & Safe-to-Spend Widget --}}
            @if(isset($financeOverview))
            <div class="dashboard-widget-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <strong style="font-size: 13px; color: var(--text-primary); font-weight: 600;">Finance & Safe-to-Spend</strong>
                    <a href="{{ route('finance.index') }}" style="font-size: 11px; color: var(--text-secondary); text-decoration: none; font-weight: 600;">Buku Kas ➔</a>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 10px; padding: 8px 10px; background: var(--bg-secondary); border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                    <div>
                        <span style="font-size: 10px; color: var(--text-tertiary); text-transform: uppercase; letter-spacing: 0.5px;">Safe to Spend Hari Ini:</span>
                        <div style="font-size: 16px; font-weight: 700; color: #34d399; margin-top: 2px; font-variant-numeric: tabular-nums;">
                            Rp {{ number_format($financeOverview['safe_to_spend_remaining'], 0, ',', '.') }}
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <span style="font-size: 9.5px; font-weight: 600; padding: 2px 6px; border-radius: 4px; background: {{ $financeOverview['status_color'] }}18; color: {{ $financeOverview['status_color'] }};">
                            {{ $financeOverview['status_label'] }}
                        </span>
                        <div style="font-size: 10.5px; color: var(--text-secondary); margin-top: 3px; font-variant-numeric: tabular-nums;">
                            Bebas: Rp {{ number_format($financeOverview['available_balance'], 0, ',', '.') }}
                        </div>
                    </div>
                </div>

                @if(!empty($financeOverview['upcoming_obligations']))
                    <div style="margin-bottom: 8px;">
                        <span style="font-size: 10px; color: var(--text-tertiary); text-transform: uppercase; letter-spacing: 0.5px;">Kewajiban 7 Hari ke Depan:</span>
                        <div style="display: flex; flex-direction: column; gap: 4px; margin-top: 4px;">
                            @foreach($financeOverview['upcoming_obligations'] as $upg)
                                <div style="font-size: 11px; display: flex; justify-content: space-between; color: var(--text-secondary); background: var(--bg-secondary); padding: 5px 8px; border-radius: 4px; border: 1px solid var(--border-color);">
                                    <span>{{ $upg['name'] }} ({{ $upg['days_remaining'] == 0 ? 'Hari ini' : 'H-' . $upg['days_remaining'] }})</span>
                                    <strong style="color: var(--text-primary); font-variant-numeric: tabular-nums;">Rp {{ number_format($upg['amount'], 0, ',', '.') }}</strong>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div style="display: flex; gap: 6px;">
                    <a href="{{ route('finance.index') }}" class="btn btn-outline btn-sm" style="flex: 1; text-align: center; text-decoration: none; justify-content: center; font-size: 11px; padding: 5px 8px;">
                        Buka Keuangan
                    </a>
                </div>
            </div>
            @endif

            {{-- Overdue Alert --}}
            @if($overdueTasks->count() > 0)
            <div class="alert-card alert-overdue glass-card">
                <h3 class="section-title section-title-danger">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                    Overdue ({{ $overdueTasks->count() }})
                </h3>
                <div class="task-list-mini">
                    @foreach($overdueTasks->take(3) as $task)
                    <div class="task-item-mini task-overdue" id="task-{{ $task->id }}">
                        <span class="priority-dot priority-{{ $task->priority }}"></span>
                        <div class="task-mini-info">
                            <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                <span class="task-mini-title">{{ $task->title }}</span>
                                <span class="priority-engine-badge level-{{ strtolower($task->priority_level ?? 'kritis') }}" title="{{ $task->priorityResult->reason }}">
                                    {{ $task->priority_level ?? 'KRITIS' }} {{ $task->priority_score ?? 100 }}
                                </span>
                            </div>
                            <span class="task-mini-deadline">{{ $task->deadline->diffForHumans() }} · <span style="color: #f87171;">{{ $task->priorityResult->reason }}</span></span>
                        </div>
                        <div class="mini-actions">
                            <button class="action-btn-sm" onclick="editTask({{ $task->id }}, {{ json_encode($task) }})" title="Edit Tugas" aria-label="Edit">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </button>
                            <button class="action-btn-sm action-delete" onclick="deleteTask({{ $task->id }})" title="Hapus Tugas" aria-label="Hapus">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Deadline Risk Alert Banner (V2.2) --}}
            @if($highRiskTasks->count() > 0)
            <div class="alert-card alert-risk glass-card">
                <div class="alert-risk-header">
                    <h3 class="section-title section-title-danger" style="margin-bottom: 0;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="12 2 2 22 22 22 12 2"></polygon>
                            <line x1="12" y1="9" x2="12" y2="13"></line>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                        Risiko Deadline Tinggi ({{ $highRiskTasks->count() }})
                    </h3>
                    <span style="font-size: 11px; color: #f87171; font-weight: 700;">Waktu Mepet</span>
                </div>
                <div style="display: flex; flex-direction: column; gap: 6px; margin-top: 8px;">
                    @foreach($highRiskTasks->take(3) as $rtask)
                    <div class="risk-item-row">
                        <div class="risk-item-info">
                            <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                <strong style="font-size: 12px; color: var(--text-primary);">{{ $rtask->title }}</strong>
                                <span class="risk-badge risk-{{ strtolower($rtask->risk_level ?? 'tinggi') }}">
                                    ⚠️ {{ $rtask->risk_level }}
                                </span>
                            </div>
                            <span style="font-size: 11px; color: #fca5a5;">
                                ⏱️ {{ $rtask->riskResult->reason }}
                            </span>
                        </div>
                        <button class="action-btn-sm" onclick="editTask({{ $rtask->id }}, {{ json_encode($rtask) }})" title="Atur Tugas">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                        </button>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Today Course Schedules --}}
            <div class="today-section schedule-today-card">
                <div class="section-title-wrap" style="display: flex; justify-content: space-between; align-items: center;">
                    <h3 class="section-title" style="margin-bottom: 0;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                        </svg>
                        Kuliah Hari Ini ({{ $todaySchedules->count() }})
                    </h3>
                    <a href="{{ route('schedules.index') }}" class="section-link-more" style="font-size: 11px; color: var(--text-secondary); text-decoration: none; font-weight: 500;">Lihat Matrix ➔</a>
                </div>

                @if($todaySchedules->count() > 0)
                <div class="schedule-list-mini" style="margin-top: 10px; display: flex; flex-direction: column; gap: 6px;">
                    @foreach($todaySchedules as $cs)
                    <div class="schedule-item-mini theme-{{ $cs->color_tag ?? 'red' }} {{ $cs->is_ongoing_now ? 'mini-ongoing' : '' }}" style="background: var(--bg-secondary); padding: 8px 10px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); border-left: 3px solid var(--accent); display: flex; justify-content: space-between; align-items: center; gap: 8px;">
                        <div style="display: flex; flex-direction: column; gap: 2px; min-width: 0;">
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <strong style="font-size: 12.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 160px; color: var(--text-primary);">{{ $cs->course_name }}</strong>
                                @if($cs->is_ongoing_now)
                                    <span style="background: var(--accent); color: white; font-size: 9px; font-weight: 600; padding: 1px 5px; border-radius: 3px;">LIVE</span>
                                @endif
                            </div>
                            <div style="font-size: 11px; color: var(--text-tertiary); display: flex; gap: 8px;">
                                <span>{{ $cs->start_time_formatted }} - {{ $cs->end_time_formatted }}</span>
                                @if($cs->room)
                                    <span>· R. {{ $cs->room }}</span>
                                @endif
                            </div>
                        </div>
                        @if($cs->meeting_link)
                            <a href="{{ $cs->meeting_link }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-link-action" style="padding: 3px 8px; font-size: 11px;" title="Buka Link Kuliah">Link</a>
                        @endif
                    </div>
                    @endforeach
                </div>
                @else
                <p class="empty-mini" style="margin-top: 10px; font-size: 12px; color: var(--text-tertiary);">Tidak ada jadwal kuliah hari ini</p>
                @endif
            </div>

            {{-- Today Tasks --}}
            <div class="today-section glass-card">
                <h3 class="section-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 11l3 3L22 4"></path>
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                    </svg>
                    Tugas Hari Ini
                </h3>
                @if($todayTasks->count() > 0)
                <div class="task-list-mini">
                    @foreach($todayTasks as $task)
                    <div class="task-item-mini" id="task-{{ $task->id }}">
                        <span class="priority-dot priority-{{ $task->priority }}"></span>
                        <div class="task-mini-info">
                            <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                <span class="task-mini-title {{ $task->status === 'completed' ? 'completed' : '' }}">{{ $task->title }}</span>
                                @if($task->status !== 'completed' && $task->priority_level)
                                    <span class="priority-engine-badge level-{{ strtolower($task->priority_level) }}" title="{{ $task->priorityResult->reason }}">
                                        {{ $task->priority_level }} {{ $task->priority_score }}
                                    </span>
                                @endif
                                @if($task->status !== 'completed' && $task->risk_level && in_array($task->risk_level, ['KRITIS', 'TINGGI', 'SEDANG']))
                                    <span class="risk-badge risk-{{ strtolower($task->risk_level) }}" title="Risiko: {{ $task->riskResult->reason }}">
                                        ⚠️ {{ $task->risk_level }}
                                    </span>
                                @endif
                            </div>
                            <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                @if($task->subject)
                                <span class="task-mini-subject">{{ $task->subject }}</span>
                                @endif
                                @if($task->status !== 'completed' && $task->priorityResult->reason)
                                <span style="font-size: 11px; color: var(--text-tertiary);">💡 {{ $task->priorityResult->reason }}</span>
                                @endif
                                @if($task->attachments && $task->attachments->count() > 0)
                                    @foreach($task->attachments as $att)
                                    <a href="{{ $att->file_url }}" target="_blank" title="{{ $att->file_name }} ({{ $att->formatted_file_size }})" style="font-size: 11px; text-decoration: none; color: var(--text-secondary); background: var(--bg-tertiary); padding: 1px 6px; border-radius: 10px;">📎 {{ Str::limit($att->file_name, 15) }}</a>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                        <div class="mini-actions">
                            <button class="task-toggle-btn" data-task-id="{{ $task->id }}" data-status="{{ $task->status }}" title="Ubah Status" aria-label="Toggle status">
                                @if($task->status === 'completed')
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                @else
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle></svg>
                                @endif
                            </button>
                            <button class="action-btn-sm" onclick="editTask({{ $task->id }}, {{ json_encode($task) }})" title="Edit Tugas" aria-label="Edit">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </button>
                            <button class="action-btn-sm action-delete" onclick="deleteTask({{ $task->id }})" title="Hapus Tugas" aria-label="Hapus">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="empty-state-mini">Tidak ada tugas hari ini 🎉</p>
                @endif
            </div>

            {{-- Today Events --}}
            <div class="today-section glass-card">
                <h3 class="section-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                    </svg>
                    Acara Hari Ini
                </h3>
                @if($todayEvents->count() > 0)
                <div class="event-list-mini">
                    @foreach($todayEvents as $event)
                    <div class="event-item-mini" id="event-{{ $event->id }}">
                        <span class="event-category-dot category-{{ $event->category }}"></span>
                        <div class="event-mini-info">
                            <span class="event-mini-title">{{ $event->title }}</span>
                            <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                <span class="event-mini-time">{{ $event->start_date->format('H:i') }}@if($event->location) · {{ $event->location }}@endif</span>
                                @if($event->attachments && $event->attachments->count() > 0)
                                    @foreach($event->attachments as $att)
                                    <a href="{{ $att->file_url }}" target="_blank" title="{{ $att->file_name }} ({{ $att->formatted_file_size }})" style="font-size: 11px; text-decoration: none; color: var(--text-secondary); background: var(--bg-tertiary); padding: 1px 6px; border-radius: 10px;">📎 {{ Str::limit($att->file_name, 15) }}</a>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                        <div class="mini-actions">
                            <button class="action-btn-sm" onclick="editEvent({{ $event->id }}, {{ json_encode($event) }})" title="Edit Acara" aria-label="Edit">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </button>
                            <button class="action-btn-sm action-delete" onclick="deleteEvent({{ $event->id }})" title="Hapus Acara" aria-label="Hapus">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="empty-state-mini">Tidak ada acara hari ini</p>
                @endif
            </div>

            {{-- Upcoming Tasks --}}
            @if($upcomingTasks->count() > 0)
            <div class="today-section glass-card">
                <h3 class="section-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    Akan Datang
                </h3>
                <div class="task-list-mini">
                    @foreach($upcomingTasks as $task)
                    <div class="task-item-mini" id="task-{{ $task->id }}">
                        <span class="priority-dot priority-{{ $task->priority }}"></span>
                        <div class="task-mini-info">
                            <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                <span class="task-mini-title">{{ $task->title }}</span>
                                @if($task->priority_level)
                                <span class="priority-engine-badge level-{{ strtolower($task->priority_level) }}" title="{{ $task->priorityResult->reason }}">
                                    {{ $task->priority_level }} {{ $task->priority_score }}
                                </span>
                                @endif
                                @if($task->risk_level && in_array($task->risk_level, ['KRITIS', 'TINGGI', 'SEDANG']))
                                <span class="risk-badge risk-{{ strtolower($task->risk_level) }}" title="Risiko: {{ $task->riskResult->reason }}">
                                    ⚠️ {{ $task->risk_level }}
                                </span>
                                @endif
                            </div>
                            <span class="task-mini-deadline">{{ $task->deadline->diffForHumans() }} · <span style="color: var(--text-tertiary);">{{ $task->priorityResult->reason }}</span></span>
                        </div>
                        <div class="mini-actions">
                            <button class="action-btn-sm" onclick="editTask({{ $task->id }}, {{ json_encode($task) }})" title="Edit Tugas" aria-label="Edit">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </button>
                            <button class="action-btn-sm action-delete" onclick="deleteTask({{ $task->id }})" title="Hapus Tugas" aria-label="Hapus">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Day Detail Panel (shown when clicking a calendar date) --}}
<div class="day-detail-panel" id="dayDetailPanel">
    <div class="day-detail-header">
        <h3 id="dayDetailTitle"></h3>
        <button class="day-detail-close" id="dayDetailClose" aria-label="Close">&times;</button>
    </div>
    <div class="day-detail-content" id="dayDetailContent"></div>
</div>
@endsection
