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
            <div class="greeting-card glass-card">
                <h2 class="greeting-text">Hai, {{ session('user_name', 'User') }}! 👋</h2>
                <p class="greeting-date">{{ now()->translatedFormat('l, d F Y') }}</p>
            </div>

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
                    <div class="task-item-mini task-overdue">
                        <span class="priority-dot priority-{{ $task->priority }}"></span>
                        <div class="task-mini-info">
                            <span class="task-mini-title">{{ $task->title }}</span>
                            <span class="task-mini-deadline">{{ $task->deadline->diffForHumans() }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

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
                    <div class="task-item-mini">
                        <span class="priority-dot priority-{{ $task->priority }}"></span>
                        <div class="task-mini-info">
                            <span class="task-mini-title {{ $task->status === 'completed' ? 'completed' : '' }}">{{ $task->title }}</span>
                            @if($task->subject)
                            <span class="task-mini-subject">{{ $task->subject }}</span>
                            @endif
                        </div>
                        <button class="task-toggle-btn" data-task-id="{{ $task->id }}" data-status="{{ $task->status }}" aria-label="Toggle status">
                            @if($task->status === 'completed')
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                            @else
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle></svg>
                            @endif
                        </button>
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
                    <div class="event-item-mini">
                        <span class="event-category-dot category-{{ $event->category }}"></span>
                        <div class="event-mini-info">
                            <span class="event-mini-title">{{ $event->title }}</span>
                            <span class="event-mini-time">{{ $event->start_date->format('H:i') }}@if($event->location) · {{ $event->location }}@endif</span>
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
                    <div class="task-item-mini">
                        <span class="priority-dot priority-{{ $task->priority }}"></span>
                        <div class="task-mini-info">
                            <span class="task-mini-title">{{ $task->title }}</span>
                            <span class="task-mini-deadline">{{ $task->deadline->diffForHumans() }}</span>
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
