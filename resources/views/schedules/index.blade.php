@extends('layouts.app')

@section('title', 'Jadwal Kuliah')
@section('page-title', 'Jadwal Kuliah Mingguan')

@section('content')
<div class="schedules-page">
    {{-- Header Actions & Stats Bar --}}
    <div class="schedule-header-bar">
        <div class="schedule-stats-wrap">
            <div class="stat-badge stat-sks">
                <span class="stat-icon">🎓</span>
                <div class="stat-info">
                    <span class="stat-val">{{ $totalSks }}</span>
                    <span class="stat-lbl">Total SKS</span>
                </div>
            </div>
            <div class="stat-badge stat-courses">
                <span class="stat-icon">📚</span>
                <div class="stat-info">
                    <span class="stat-val">{{ $totalCourses }}</span>
                    <span class="stat-lbl">Mata Kuliah</span>
                </div>
            </div>
            <div class="stat-badge stat-days">
                <span class="stat-icon">📅</span>
                <div class="stat-info">
                    <span class="stat-val">{{ $activeDays }}</span>
                    <span class="stat-lbl">Hari Aktif</span>
                </div>
            </div>
        </div>

        <div class="schedule-actions-wrap">
            {{-- View Switcher --}}
            <div class="view-switcher" role="group" aria-label="Tampilan Jadwal">
                <button type="button" class="view-toggle-btn active" id="btnMatrixView" onclick="switchScheduleView('matrix')" title="Tampilan Matrix Grid">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    <span>Matrix Grid</span>
                </button>
                <button type="button" class="view-toggle-btn" id="btnAgendaView" onclick="switchScheduleView('agenda')" title="Tampilan Daftar Agenda">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="8" y1="6" x2="21" y2="6"></line>
                        <line x1="8" y1="12" x2="21" y2="12"></line>
                        <line x1="8" y1="18" x2="21" y2="18"></line>
                        <line x1="3" y1="6" x2="3.01" y2="6"></line>
                        <line x1="3" y1="12" x2="3.01" y2="12"></line>
                        <line x1="3" y1="18" x2="3.01" y2="18"></line>
                    </svg>
                    <span>Daftar Hari</span>
                </button>
            </div>

            <button type="button" class="btn btn-outline-alt" onclick="copyScheduleToClipboard()" title="Salin format teks jadwal kuliah untuk WhatsApp">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                </svg>
                <span>Salin Jadwal (WA)</span>
            </button>

            <button type="button" class="btn btn-primary" onclick="openScheduleModal()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>Tambah Mata Kuliah</span>
            </button>
        </div>
    </div>

    {{-- Live Status Hero Banner --}}
    @if($ongoingSchedule)
    <div class="live-status-hero live-ongoing">
        <div class="hero-pulsing-badge">
            <span class="pulse-dot"></span>
            <span>SEDANG BERLANGSUNG</span>
        </div>
        <div class="hero-content">
            <div class="hero-main">
                <h2 class="hero-course-title">{{ $ongoingSchedule->course_name }}</h2>
                <div class="hero-meta">
                    @if($ongoingSchedule->course_code)
                        <span class="meta-tag">{{ $ongoingSchedule->course_code }}</span>
                    @endif
                    @if($ongoingSchedule->class_code)
                        <span class="meta-tag">{{ $ongoingSchedule->class_code }}</span>
                    @endif
                    <span class="meta-tag">{{ $ongoingSchedule->sks }} SKS</span>
                    <span class="meta-time">⏰ {{ $ongoingSchedule->time_range }}</span>
                    @if($ongoingSchedule->room)
                        <span class="meta-room">📍 Ruang {{ $ongoingSchedule->room }}</span>
                    @endif
                    @if($ongoingSchedule->lecturer_name)
                        <span class="meta-lecturer">👨‍🏫 {{ $ongoingSchedule->lecturer_name }}</span>
                    @endif
                </div>
            </div>
            <div class="hero-cta">
                @if($ongoingSchedule->remaining_minutes_now !== null)
                    <div class="hero-countdown-pill">
                        Sisa <strong>{{ $ongoingSchedule->remaining_minutes_now }}</strong> menit lagi
                    </div>
                @endif
                @if($ongoingSchedule->meeting_link)
                    <a href="{{ $ongoingSchedule->meeting_link }}" target="_blank" rel="noopener noreferrer" class="btn btn-hero-link">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M15 10l5-5v14l-5-5"></path>
                            <rect x="2" y="5" width="13" height="14" rx="2"></rect>
                        </svg>
                        <span>Buka Link Kuliah</span>
                    </a>
                @endif
            </div>
        </div>
    </div>
    @elseif($nextSchedule)
    <div class="live-status-hero live-upcoming">
        <div class="hero-pulsing-badge badge-upcoming">
            <span class="upcoming-icon">⏳</span>
            <span>KULIAH BERIKUTNYA HARI INI</span>
        </div>
        <div class="hero-content">
            <div class="hero-main">
                <h2 class="hero-course-title">{{ $nextSchedule->course_name }}</h2>
                <div class="hero-meta">
                    @if($nextSchedule->course_code)
                        <span class="meta-tag">{{ $nextSchedule->course_code }}</span>
                    @endif
                    <span class="meta-tag">{{ $nextSchedule->sks }} SKS</span>
                    <span class="meta-time">⏰ {{ $nextSchedule->time_range }}</span>
                    @if($nextSchedule->room)
                        <span class="meta-room">📍 Ruang {{ $nextSchedule->room }}</span>
                    @endif
                    @if($nextSchedule->lecturer_name)
                        <span class="meta-lecturer">👨‍🏫 {{ $nextSchedule->lecturer_name }}</span>
                    @endif
                </div>
            </div>
            <div class="hero-cta">
                <div class="hero-countdown-pill countdown-upcoming">
                    Mulai dalam <strong>{{ $nextSchedule->minutes_until_start }}</strong> menit
                </div>
                @if($nextSchedule->meeting_link)
                    <a href="{{ $nextSchedule->meeting_link }}" target="_blank" rel="noopener noreferrer" class="btn btn-hero-link">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                            <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                        </svg>
                        <span>Link Pertemuan</span>
                    </a>
                @endif
            </div>
        </div>
    </div>
    @elseif($todaySchedules->count() > 0)
    <div class="live-status-hero live-done">
        <div class="hero-pulsing-badge badge-done">
            <span>✨ SELESAI HARI INI</span>
        </div>
        <div class="hero-content">
            <div class="hero-main">
                <h3 class="hero-done-title">Semua jadwal kuliah hari ini telah selesai! 🎉</h3>
                <p class="hero-done-subtitle">Waktunya istirahat atau cek daftar tugas yang perlu dikerjakan.</p>
            </div>
            <a href="{{ route('tasks.index') }}" class="btn btn-outline-alt">Lihat Tugas ➔</a>
        </div>
    </div>
    @endif

    {{-- Matrix Timetable Grid View --}}
    <div class="schedule-view-container" id="matrixViewContainer">
        @if($totalCourses === 0)
        <div class="empty-state">
            <div class="empty-icon">🗓️</div>
            <h3>Belum Ada Jadwal Kuliah</h3>
            <p>Susun jadwal perkuliahan semester kamu agar terorganisir dan tidak ketinggalan kelas!</p>
            <button type="button" class="btn btn-primary" onclick="openScheduleModal()">+ Tambah Mata Kuliah Pertama</button>
        </div>
        @else
        <div class="timetable-matrix-card">
            {{-- Matrix Columns Header --}}
            <div class="matrix-grid-layout">
                {{-- Top-left empty corner --}}
                <div class="matrix-time-header">Waktu</div>

                {{-- Day Column Headers --}}
                @foreach([1, 2, 3, 4, 5, 6] as $dayNum)
                <div class="matrix-day-header {{ $dayNum === $today ? 'today-col' : '' }}">
                    <span class="day-title">{{ $days[$dayNum] }}</span>
                    @if($dayNum === $today)
                        <span class="today-indicator">HARI INI</span>
                    @endif
                    <span class="day-count">{{ count($schedulesByDay[$dayNum] ?? []) }} Kelas</span>
                </div>
                @endforeach

                {{-- Time Axis & Grid Canvas --}}
                <div class="matrix-time-axis">
                    @for($h = 7; $h <= 18; $h++)
                    <div class="time-slot-label">
                        <span>{{ sprintf('%02d:00', $h) }}</span>
                    </div>
                    @endfor
                </div>

                {{-- Day Columns Grid --}}
                @foreach([1, 2, 3, 4, 5, 6] as $dayNum)
                <div class="matrix-day-col {{ $dayNum === $today ? 'today-col' : '' }}" id="matrixDayCol-{{ $dayNum }}">
                    {{-- Grid Hour Guidelines --}}
                    @for($h = 7; $h <= 18; $h++)
                    <div class="grid-hour-line"></div>
                    @endfor

                    {{-- Current Time Red Line Indicator (only for today) --}}
                    @if($dayNum === $today)
                    <div class="current-time-line" id="currentTimeIndicator">
                        <span class="time-tag" id="currentTimeTag">--:--</span>
                    </div>
                    @endif

                    {{-- Course Cards in this Day --}}
                    @foreach($schedulesByDay[$dayNum] ?? [] as $schedule)
                    @php
                        // Calculate offset from 07:00 (in minutes)
                        $startParts = explode(':', $schedule->start_time);
                        $endParts = explode(':', $schedule->end_time);
                        $startMinutes = ((int)$startParts[0] * 60) + (int)($startParts[1] ?? 0);
                        $endMinutes = ((int)$endParts[0] * 60) + (int)($endParts[1] ?? 0);
                        $gridStart = 7 * 60; // 07:00 in minutes
                        $topPx = max(0, ($startMinutes - $gridStart) * 1.15); // 1.15px per minute (~69px per hour)
                        $heightPx = max(55, ($endMinutes - $startMinutes) * 1.15);
                    @endphp
                    <div class="matrix-course-card theme-{{ $schedule->color_tag ?? 'red' }} {{ $schedule->is_ongoing_now ? 'card-ongoing' : '' }}"
                         style="top: {{ $topPx }}px; height: {{ $heightPx }}px;"
                         onclick="viewScheduleDetail({{ $schedule->id }})"
                         id="matrixCard-{{ $schedule->id }}">
                        <div class="card-header-mini">
                            <span class="card-time">{{ $schedule->time_range }}</span>
                            <span class="card-sks">{{ $schedule->sks }} SKS</span>
                        </div>
                        <h4 class="card-title">{{ $schedule->course_name }}</h4>
                        <div class="card-sub-info">
                            @if($schedule->room)
                                <span class="card-room">📍 {{ $schedule->room }}</span>
                            @endif
                            @if($schedule->class_code)
                                <span class="card-class">🏛️ {{ $schedule->class_code }}</span>
                            @endif
                            @if($schedule->delivery_mode === 'online')
                                <span class="badge-mode-online">🌐 Online</span>
                            @elseif($schedule->delivery_mode === 'hybrid')
                                <span class="badge-mode-hybrid">🔀 Hybrid</span>
                            @endif
                        </div>
                        @if($schedule->lecturer_name && $heightPx > 80)
                        <div class="card-lecturer" title="{{ $schedule->lecturer_name }}">
                            👨‍🏫 {{ Str::limit($schedule->lecturer_name, 22) }}
                        </div>
                        @endif

                        @if($schedule->attachments && $schedule->attachments->count() > 0)
                        <div class="card-att-badge" title="{{ $schedule->attachments->count() }} Lampiran RPS/Silabus">
                            📎 {{ $schedule->attachments->count() }}
                        </div>
                        @endif

                        {{-- Hover Quick Action Menu --}}
                        <div class="card-actions-hover" onclick="event.stopPropagation()">
                            @if($schedule->meeting_link)
                            <a href="{{ $schedule->meeting_link }}" target="_blank" rel="noopener noreferrer" class="hover-btn link-btn" title="Buka Link Kuliah">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M15 10l5-5v14l-5-5"></path>
                                    <rect x="2" y="5" width="13" height="14" rx="2"></rect>
                                </svg>
                            </a>
                            @endif
                            <button type="button" class="hover-btn" onclick="editSchedule({{ $schedule->id }}, {{ json_encode($schedule) }})" title="Edit">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </button>
                            <button type="button" class="hover-btn hover-del" onclick="deleteSchedule({{ $schedule->id }})" title="Hapus">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- Agenda List View (Alternative & Mobile Friendly) --}}
    <div class="schedule-view-container" id="agendaViewContainer" style="display: none;">
        <div class="agenda-days-grid">
            @foreach([1, 2, 3, 4, 5, 6, 7] as $dayNum)
            @php
                $dayList = $schedulesByDay[$dayNum] ?? collect();
            @endphp
            <div class="agenda-day-card {{ $dayNum === $today ? 'agenda-today' : '' }}">
                <div class="agenda-day-header">
                    <div class="agenda-day-title-wrap">
                        <span class="day-icon">
                            @switch($dayNum)
                                @case(1) 🟢 @break
                                @case(2) 🔵 @break
                                @case(3) 🟣 @break
                                @case(4) 🟠 @break
                                @case(5) 🟢 @break
                                @case(6) 🟡 @break
                                @default ⚪
                            @endswitch
                        </span>
                        <h3>{{ $days[$dayNum] }}</h3>
                        @if($dayNum === $today)
                            <span class="today-pill">HARI INI</span>
                        @endif
                    </div>
                    <span class="agenda-day-count">{{ $dayList->count() }} Mata Kuliah</span>
                </div>

                <div class="agenda-day-body">
                    @forelse($dayList as $schedule)
                    <div class="agenda-course-item theme-{{ $schedule->color_tag ?? 'red' }} {{ $schedule->is_ongoing_now ? 'item-ongoing' : '' }}">
                        <div class="item-time-col">
                            <span class="item-start">{{ $schedule->start_time_formatted }}</span>
                            <span class="item-separator">s/d</span>
                            <span class="item-end">{{ $schedule->end_time_formatted }}</span>
                            <span class="item-duration">({{ $schedule->duration_minutes }} mnt)</span>
                        </div>

                        <div class="item-main-col">
                            <div class="item-tags-row">
                                @if($schedule->course_code)
                                    <span class="code-badge">{{ $schedule->course_code }}</span>
                                @endif
                                @if($schedule->class_code)
                                    <span class="class-badge">{{ $schedule->class_code }}</span>
                                @endif
                                <span class="sks-badge">{{ $schedule->sks }} SKS</span>
                                @if($schedule->delivery_mode === 'online')
                                    <span class="mode-badge-online">🌐 Online</span>
                                @elseif($schedule->delivery_mode === 'hybrid')
                                    <span class="mode-badge-hybrid">🔀 Hybrid</span>
                                @else
                                    <span class="mode-badge-offline">🏛️ Offline</span>
                                @endif

                                @if($schedule->is_ongoing_now)
                                    <span class="live-pill">🔴 SEDANG BERLANGSUNG</span>
                                @endif
                            </div>

                            <h4 class="item-title">{{ $schedule->course_name }}</h4>

                            <div class="item-details-row">
                                @if($schedule->room)
                                    <span class="detail-item">📍 Ruang: <strong>{{ $schedule->room }}</strong></span>
                                @endif
                                @if($schedule->lecturer_name)
                                    <span class="detail-item">👨‍🏫 Dosen: <strong>{{ $schedule->lecturer_name }}</strong></span>
                                @endif
                            </div>

                            @if($schedule->notes)
                            <div class="item-notes">
                                💬 {{ $schedule->notes }}
                            </div>
                            @endif

                            {{-- Attachments --}}
                            @if($schedule->attachments && $schedule->attachments->count() > 0)
                            <div class="item-attachments-wrap">
                                <span class="att-heading">📎 Materi & Silabus:</span>
                                @foreach($schedule->attachments as $att)
                                <a href="{{ $att->file_url }}" target="_blank" class="attachment-pill type-{{ $att->file_type ?? 'file' }}" title="{{ $att->file_name }} ({{ $att->formatted_file_size }})">
                                    <span class="att-name">{{ $att->file_name }}</span>
                                    <span class="att-size">({{ $att->formatted_file_size }})</span>
                                </a>
                                @endforeach
                            </div>
                            @endif
                        </div>

                        <div class="item-actions-col">
                            @if($schedule->meeting_link)
                            <a href="{{ $schedule->meeting_link }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-link-action" title="Buka Link Pertemuan Online">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M15 10l5-5v14l-5-5"></path>
                                    <rect x="2" y="5" width="13" height="14" rx="2"></rect>
                                </svg>
                                <span>Link</span>
                            </a>
                            @endif
                            <button type="button" class="btn-icon-alt" onclick="editSchedule({{ $schedule->id }}, {{ json_encode($schedule) }})" title="Edit">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </button>
                            <button type="button" class="btn-icon-alt btn-icon-del" onclick="deleteSchedule({{ $schedule->id }})" title="Hapus">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    @empty
                    <div class="agenda-empty-day">
                        <span>✨ Tidak ada jadwal kuliah di hari ini</span>
                    </div>
                    @endforelse
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Detail Modal --}}
<div class="modal-overlay" id="detailModalOverlay" style="display: none;">
    <div class="modal" id="detailModal" style="max-width: 540px;">
        <div class="modal-header">
            <h2 class="modal-title" id="detailModalTitle">Detail Mata Kuliah</h2>
            <button class="modal-close" onclick="closeDetailModal()">&times;</button>
        </div>
        <div class="modal-body" id="detailModalBody"></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Master schedule data passed from backend
window.scheduleData = @json($schedules);

function switchScheduleView(view) {
    const matrixContainer = document.getElementById('matrixViewContainer');
    const agendaContainer = document.getElementById('agendaViewContainer');
    const btnMatrix = document.getElementById('btnMatrixView');
    const btnAgenda = document.getElementById('btnAgendaView');

    if (view === 'matrix') {
        if (matrixContainer) matrixContainer.style.display = 'block';
        if (agendaContainer) agendaContainer.style.display = 'none';
        btnMatrix.classList.add('active');
        btnAgenda.classList.remove('active');
        localStorage.setItem('schedule_preferred_view', 'matrix');
    } else {
        if (matrixContainer) matrixContainer.style.display = 'none';
        if (agendaContainer) agendaContainer.style.display = 'block';
        btnMatrix.classList.remove('active');
        btnAgenda.classList.add('active');
        localStorage.setItem('schedule_preferred_view', 'agenda');
    }
}

// Restore user view preference
document.addEventListener('DOMContentLoaded', () => {
    const pref = localStorage.getItem('schedule_preferred_view');
    if (pref === 'agenda') {
        switchScheduleView('agenda');
    }
    updateCurrentTimeIndicator();
    setInterval(updateCurrentTimeIndicator, 60000); // refresh every minute
});

// Update the real-time indicator line in today's column
function updateCurrentTimeIndicator() {
    const indicator = document.getElementById('currentTimeIndicator');
    const timeTag = document.getElementById('currentTimeTag');
    if (!indicator || !timeTag) return;

    const now = new Date();
    const hours = now.getHours();
    const minutes = now.getMinutes();

    timeTag.textContent = String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0');

    // Only display line if within 07:00 to 19:00
    if (hours >= 7 && hours <= 19) {
        const totalMinutes = (hours * 60) + minutes;
        const gridStart = 7 * 60; // 07:00
        const topPx = (totalMinutes - gridStart) * 1.15;
        indicator.style.top = topPx + 'px';
        indicator.style.display = 'flex';
    } else {
        indicator.style.display = 'none';
    }
}

// Copy Schedule text formatted for WhatsApp
function copyScheduleToClipboard() {
    if (!window.scheduleData || window.scheduleData.length === 0) {
        showToast('Belum ada jadwal kuliah untuk disalin!', 'error');
        return;
    }

    const dayNames = {1: 'SENIN', 2: 'SELASA', 3: 'RABU', 4: 'KAMIS', 5: 'JUMAT', 6: 'SABTU', 7: 'MINGGU'};
    let text = "📚 *JADWAL KULIAH MINGGUAN*\n";
    text += "━━━━━━━━━━━━━━━━━━━━━━\n\n";

    for (let day = 1; day <= 7; day++) {
        const dayItems = window.scheduleData.filter(s => s.day_of_week === day);
        if (dayItems.length > 0) {
            text += `📅 *${dayNames[day]}*\n`;
            dayItems.forEach(s => {
                text += `▪ *${s.course_name}* (${s.sks} SKS)\n`;
                text += `   ⏰ ${s.start_time_formatted || s.start_time.substring(0,5)} - ${s.end_time_formatted || s.end_time.substring(0,5)}\n`;
                if (s.room) text += `   📍 Ruang: ${s.room}\n`;
                if (s.lecturer_name) text += `   👨‍🏫 Dosen: ${s.lecturer_name}\n`;
                if (s.class_code) text += `   🏛️ Kelas: ${s.class_code}\n`;
                if (s.delivery_mode && s.delivery_mode !== 'offline') text += `   🌐 Mode: ${s.delivery_mode.toUpperCase()}\n`;
                text += "\n";
            });
        }
    }

    text += "━━━━━━━━━━━━━━━━━━━━━━\n";
    text += "_Generated via Schedule Planner_ 🚀";

    navigator.clipboard.writeText(text).then(() => {
        showToast('✅ Jadwal kuliah berhasil disalin ke clipboard!', 'success');
    }).catch(() => {
        showToast('Gagal menyalin teks ke clipboard', 'error');
    });
}

function viewScheduleDetail(id) {
    const s = window.scheduleData.find(item => item.id === id);
    if (!s) return;

    const modal = document.getElementById('detailModalOverlay');
    const title = document.getElementById('detailModalTitle');
    const body = document.getElementById('detailModalBody');

    title.textContent = s.course_name;

    const dayNames = {1: 'Senin', 2: 'Selasa', 3: 'Rabu', 4: 'Kamis', 5: 'Jumat', 6: 'Sabtu', 7: 'Minggu'};

    let attachmentsHtml = '';
    if (s.attachments && s.attachments.length > 0) {
        attachmentsHtml = `
            <div style="margin-top: 16px;">
                <label style="font-size: 12px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase;">Lampiran / RPS / Silabus:</label>
                <div class="attachments-wrap" style="margin-top: 8px;">
                    ${s.attachments.map(att => `
                        <a href="${att.file_url}" target="_blank" class="attachment-pill type-${att.file_type || 'file'}" title="${att.file_name} (${att.formatted_file_size})">
                            <span class="att-name">${att.file_name}</span>
                            <span class="att-size">(${att.formatted_file_size})</span>
                        </a>
                    `).join('')}
                </div>
            </div>
        `;
    }

    body.innerHTML = `
        <div style="display: flex; flex-direction: column; gap: 14px;">
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                ${s.course_code ? `<span class="badge" style="background: var(--bg-tertiary); color: var(--text-primary);">${s.course_code}</span>` : ''}
                ${s.class_code ? `<span class="badge" style="background: var(--bg-tertiary); color: var(--text-primary);">${s.class_code}</span>` : ''}
                <span class="badge" style="background: rgba(239,68,68,0.15); color: #ef4444; font-weight: 700;">${s.sks} SKS</span>
                <span class="badge" style="background: var(--bg-tertiary);">${s.delivery_mode ? s.delivery_mode.toUpperCase() : 'OFFLINE'}</span>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; background: var(--bg-primary); padding: 14px; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                <div>
                    <span style="font-size: 11px; color: var(--text-secondary);">HARI & WAKTU</span>
                    <p style="margin: 2px 0 0; font-weight: 600; font-size: 14px;">📅 ${dayNames[s.day_of_week] || s.day_of_week}, ${s.start_time_formatted || s.start_time.substring(0,5)} - ${s.end_time_formatted || s.end_time.substring(0,5)}</p>
                </div>
                <div>
                    <span style="font-size: 11px; color: var(--text-secondary);">RUANGAN</span>
                    <p style="margin: 2px 0 0; font-weight: 600; font-size: 14px;">📍 ${s.room || 'Belum ditentukan'}</p>
                </div>
                <div style="grid-column: span 2;">
                    <span style="font-size: 11px; color: var(--text-secondary);">DOSEN PENGAMPU</span>
                    <p style="margin: 2px 0 0; font-weight: 600; font-size: 14px;">👨‍🏫 ${s.lecturer_name || '-'}</p>
                </div>
            </div>

            ${s.notes ? `
                <div style="background: var(--bg-tertiary); padding: 12px; border-radius: var(--radius-md); font-size: 13px; color: var(--text-secondary);">
                    <strong>Catatan:</strong> ${s.notes}
                </div>
            ` : ''}

            ${s.meeting_link ? `
                <div>
                    <a href="${s.meeting_link}" target="_blank" rel="noopener noreferrer" class="btn btn-primary" style="width: 100%; justify-content: center;">
                        🌐 Buka Link Pertemuan Kuliah (Zoom/Meet/LMS)
                    </a>
                </div>
            ` : ''}

            ${attachmentsHtml}

            <div style="display: flex; gap: 10px; margin-top: 10px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeDetailModal()">Tutup</button>
                <button type="button" class="btn btn-primary" onclick="closeDetailModal(); editSchedule(${s.id}, ${JSON.stringify(s).replace(/"/g, '&quot;')})">Edit Jadwal</button>
            </div>
        </div>
    `;

    modal.style.display = 'flex';
}

function closeDetailModal() {
    const modal = document.getElementById('detailModalOverlay');
    if (modal) modal.style.display = 'none';
}
</script>
@endpush
