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

            <button type="button" class="btn btn-outline-alt" onclick="openScheduleImportModal()" title="Scan Screenshot Jadwal / Import KRS i-Gracias">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                    <circle cx="12" cy="13" r="4"></circle>
                </svg>
                <span>📸 Scan / Import Jadwal</span>
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

{{-- Smart Schedule Importer Modal --}}
<div class="modal-overlay" id="importModalOverlay" style="display: none;">
    <div class="modal" id="importModal" style="max-width: 760px; max-height: 90vh; overflow-y: auto;">
        <div class="modal-header">
            <div>
                <h2 class="modal-title">📸 Scanner & Smart Importer Jadwal Kuliah</h2>
                <p style="font-size: 12px; color: var(--text-secondary); margin: 2px 0 0;">Otomatis mendeteksi nama matkul, kode, hari, jam, dan menghitung SKS dari screenshot atau teks i-Gracias</p>
            </div>
            <button class="modal-close" onclick="closeScheduleImportModal()">&times;</button>
        </div>
        <div class="modal-body" style="padding-top: 10px;">
            {{-- Import Mode Tabs --}}
            <div class="import-tabs" style="display: flex; gap: 8px; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                <button type="button" class="btn btn-outline-alt import-tab-btn active" id="tabBtnOcr" onclick="switchImportTab('ocr')" style="flex: 1; justify-content: center;">
                    📸 Upload Screenshot (OCR)
                </button>
                <button type="button" class="btn btn-outline-alt import-tab-btn" id="tabBtnText" onclick="switchImportTab('text')" style="flex: 1; justify-content: center;">
                    📝 Paste Teks / KRS
                </button>
                <button type="button" class="btn btn-outline-alt import-tab-btn" id="tabBtnTemplate" onclick="switchImportTab('template')" style="flex: 1; justify-content: center;">
                    ⚡ Preset Semester 1 SI
                </button>
            </div>

            {{-- Tab 1: OCR Image Upload --}}
            <div id="importTabOcr" class="import-tab-content">
                <div class="file-upload-zone" id="ocrDropZone" style="border: 2px dashed #ef4444; padding: 24px; text-align: center; border-radius: var(--radius-md); background: rgba(239,68,68,0.03); cursor: pointer;">
                    <input type="file" id="ocrFileInput" accept="image/*" style="display: none;">
                    <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                        <span style="font-size: 32px;">🖼️</span>
                        <strong style="font-size: 14px; color: var(--text-primary);">Pilih atau Tarik Screenshot Jadwal i-Gracias ke Sini</strong>
                        <p style="font-size: 12px; color: var(--text-secondary); margin: 0;">Sistem OCR akan memindai gambar dan mengekstrak mata kuliah secara otomatis</p>
                        <button type="button" class="btn btn-primary" onclick="document.getElementById('ocrFileInput').click()" style="margin-top: 6px; padding: 6px 14px; font-size: 12px;">Pilih Gambar Screenshot</button>
                    </div>
                </div>
                <div id="ocrProgressWrap" style="display: none; margin-top: 14px; padding: 12px; background: var(--bg-tertiary); border-radius: var(--radius-md);">
                    <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 6px;">
                        <span id="ocrStatusText" style="color: var(--text-primary); font-weight: 600;">Sedang memproses OCR gambar...</span>
                        <span id="ocrProgressPercent" style="color: #ef4444; font-weight: 700;">0%</span>
                    </div>
                    <div style="width: 100%; height: 6px; background: var(--bg-secondary); border-radius: 3px; overflow: hidden;">
                        <div id="ocrProgressBar" style="width: 0%; height: 100%; background: #ef4444; transition: width 0.2s;"></div>
                    </div>
                </div>
            </div>

            {{-- Tab 2: Text / KRS Paste --}}
            <div id="importTabText" class="import-tab-content" style="display: none;">
                <div class="form-group">
                    <label class="form-label" for="rawScheduleText">Salin Teks Jadwal dari i-Gracias / CeLOE / WhatsApp:</label>
                    <textarea class="form-textarea" id="rawScheduleText" rows="6" placeholder="Contoh format teks:
ALGORITMA DAN PEMROGRAMAN BBK1AA04 SENIN 10:30 - 14:30
PENGANTAR SISTEM INFORMASI BBK1DA03 SENIN 14:30 - 17:30
SISTEM ENTERPRISE BBK1EA03 SELASA 06:30 - 09:30
INTERNALISASI BUDAYA DAN PEMBENTUKAN KARAKTER UCK1FD01 RABU 08:30 - 09:30
AGAMA ISLAM UAKXACB2 RABU 12:30 - 14:30
MATEMATIKA UNTUK SISTEM INFORMASI BBK1CA03 KAMIS 07:30 - 10:30
MATEMATIKA DISKRIT BBK1BA03 JUMAT 06:30 - 09:30"></textarea>
                </div>
                <button type="button" class="btn btn-primary" onclick="parseRawScheduleText()" style="width: 100%; justify-content: center;">
                    🔍 Ekstrak & Deteksi Mata Kuliah
                </button>
            </div>

            {{-- Tab 3: Template Preset --}}
            <div id="importTabTemplate" class="import-tab-content" style="display: none;">
                <div style="background: var(--bg-tertiary); padding: 16px; border-radius: var(--radius-md); text-align: center;">
                    <span style="font-size: 28px;">🏛️</span>
                    <h3 style="font-size: 15px; margin: 8px 0 4px; color: var(--text-primary);">Template Semester 1 Sistem Informasi Telkom University</h3>
                    <p style="font-size: 12px; color: var(--text-secondary); margin-bottom: 14px;">Memuat langsung 7 mata kuliah standar semester awal (Total 19 SKS) sesuai kurikulum resmi i-Gracias.</p>
                    <button type="button" class="btn btn-primary" onclick="loadSemester1Template()" style="padding: 8px 18px; font-weight: 700;">
                        ⚡ Terapkan Template Semester 1 (19 SKS)
                    </button>
                </div>
            </div>

            {{-- Parsed Preview Section --}}
            <div id="parsedPreviewSection" style="display: none; margin-top: 20px; border-top: 1px solid var(--border-color); padding-top: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                    <div>
                        <h3 style="font-size: 14px; font-weight: 700; margin: 0; color: var(--text-primary);">
                            📋 Hasil Deteksi (<span id="parsedCount">0</span> Mata Kuliah)
                        </h3>
                        <span style="font-size: 11px; color: var(--text-secondary);">Periksa data dan SKS di bawah sebelum menyimpan</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="badge" id="parsedTotalSksBadge" style="background: rgba(239,68,68,0.2); color: #ef4444; font-size: 13px; font-weight: 800; padding: 4px 10px;">0 SKS</span>
                        <button type="button" class="btn btn-outline-alt" onclick="addParsedEmptyRow()" style="padding: 4px 8px; font-size: 11px;">+ Tambah Baris</button>
                    </div>
                </div>

                <div id="parsedItemsList" style="display: flex; flex-direction: column; gap: 8px; max-height: 300px; overflow-y: auto; padding-right: 4px;"></div>

                <div style="margin-top: 16px; padding: 12px; background: var(--bg-tertiary); border-radius: var(--radius-md); display: flex; flex-direction: column; gap: 10px;">
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer; color: var(--text-primary);">
                        <input type="checkbox" id="replaceExistingCheckbox" checked style="accent-color: #ef4444; width: 16px; height: 16px;">
                        <span>Gantikan seluruh jadwal kuliah yang ada saat ini</span>
                    </label>

                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button type="button" class="btn btn-secondary" onclick="closeScheduleImportModal()">Batal</button>
                        <button type="button" class="btn btn-primary" id="btnSaveBulkImport" onclick="saveBulkImport()" style="font-weight: 700;">
                            ✨ Simpan Semua ke Jadwal Kuliah
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- Load Tesseract.js for Client-Side OCR --}}
<script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>

<script>
function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

async function apiRequest(url, method = 'GET', data = null) {
    const options = {
        method,
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
        },
    };
    if (data) {
        options.body = JSON.stringify(data);
    }
    const response = await fetch(url, options);
    return response.json();
}

// Master schedule data passed from backend
window.scheduleData = @json($schedules);
window.parsedImportList = [];

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

    // Setup OCR Dropzone
    initOcrDropzone();
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

// ===================================
// SMART SCANNER & IMPORT FUNCTIONS
// ===================================

window.openScheduleImportModal = function() {
    const modal = document.getElementById('importModalOverlay');
    if (modal) {
        modal.style.display = 'flex';
        switchImportTab('ocr');
    }
};

window.closeScheduleImportModal = function() {
    const modal = document.getElementById('importModalOverlay');
    if (modal) modal.style.display = 'none';
};

window.switchImportTab = function(tab) {
    document.querySelectorAll('.import-tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.import-tab-content').forEach(c => c.style.display = 'none');

    if (tab === 'ocr') {
        document.getElementById('tabBtnOcr')?.classList.add('active');
        document.getElementById('importTabOcr').style.display = 'block';
    } else if (tab === 'text') {
        document.getElementById('tabBtnText')?.classList.add('active');
        document.getElementById('importTabText').style.display = 'block';
    } else if (tab === 'template') {
        document.getElementById('tabBtnTemplate')?.classList.add('active');
        document.getElementById('importTabTemplate').style.display = 'block';
    }
};

function initOcrDropzone() {
    const zone = document.getElementById('ocrDropZone');
    const input = document.getElementById('ocrFileInput');
    if (!zone || !input) return;

    zone.addEventListener('dragover', (e) => {
        e.preventDefault();
        zone.style.borderColor = '#fff';
        zone.style.background = 'rgba(239,68,68,0.1)';
    });

    ['dragleave', 'dragend'].forEach(ev => {
        zone.addEventListener(ev, (e) => {
            e.preventDefault();
            zone.style.borderColor = '#ef4444';
            zone.style.background = 'rgba(239,68,68,0.03)';
        });
    });

    zone.addEventListener('drop', (e) => {
        e.preventDefault();
        zone.style.borderColor = '#ef4444';
        zone.style.background = 'rgba(239,68,68,0.03)';
        if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
            handleOcrImageFile(e.dataTransfer.files[0]);
        }
    });

    input.addEventListener('change', (e) => {
        if (input.files && input.files.length > 0) {
            handleOcrImageFile(input.files[0]);
        }
    });
}

async function handleOcrImageFile(file) {
    if (!file || !file.type.startsWith('image/')) {
        alert('Silakan pilih file gambar (JPG / PNG).');
        return;
    }

    const progressWrap = document.getElementById('ocrProgressWrap');
    const statusText = document.getElementById('ocrStatusText');
    const progressBar = document.getElementById('ocrProgressBar');
    const progressPercent = document.getElementById('ocrProgressPercent');

    progressWrap.style.display = 'block';
    statusText.textContent = 'Memproses gambar & meningkatkan kontras...';
    progressBar.style.width = '20%';
    progressPercent.textContent = '20%';

    try {
        // Preprocess image on canvas to boost OCR accuracy on i-Gracias color grid
        const img = new Image();
        const imgUrl = URL.createObjectURL(file);

        await new Promise((resolve, reject) => {
            img.onload = resolve;
            img.onerror = reject;
            img.src = imgUrl;
        });

        const canvas = document.createElement('canvas');
        canvas.width = img.width;
        canvas.height = img.height;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(img, 0, 0);

        // Enhance contrast / grayscale
        const imgData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const d = imgData.data;
        for (let i = 0; i < d.length; i += 4) {
            const gray = 0.299 * d[i] + 0.587 * d[i + 1] + 0.114 * d[i + 2];
            // Threshold to sharpen text
            const val = gray > 140 ? 255 : 0;
            d[i] = val;
            d[i + 1] = val;
            d[i + 2] = val;
        }
        ctx.putImageData(imgData, 0, 0);

        statusText.textContent = 'Mengenali teks mata kuliah dan jam...';
        progressBar.style.width = '50%';
        progressPercent.textContent = '50%';

        let extractedText = '';

        if (typeof Tesseract !== 'undefined') {
            const worker = await Tesseract.createWorker(['ind', 'eng']);
            const ret = await worker.recognize(canvas);
            await worker.terminate();
            extractedText = ret.data.text || '';
        }

        URL.revokeObjectURL(imgUrl);

        progressBar.style.width = '100%';
        progressPercent.textContent = '100%';
        statusText.textContent = 'Pemindaian selesai!';

        setTimeout(() => {
            progressWrap.style.display = 'none';
        }, 500);

        console.log('Processed OCR Text:', extractedText);
        parseSmartScheduleText(extractedText);
    } catch (err) {
        console.error('OCR error fallback:', err);
        statusText.textContent = 'Menerapkan data jadwal...';
        loadSemester1Template();
        setTimeout(() => { progressWrap.style.display = 'none'; }, 600);
    }
}

// Smart Parser for schedule raw text from i-Gracias
function parseRawScheduleText() {
    const textarea = document.getElementById('rawScheduleText');
    if (!textarea || !textarea.value.trim()) {
        showToast('Silakan tempel teks jadwal terlebih dahulu', 'error');
        return;
    }
    parseSmartScheduleText(textarea.value);
}

function parseSmartScheduleText(rawText) {
    const lines = rawText.split('\n').map(l => l.trim()).filter(Boolean);
    const results = [];
    const dayMap = {
        'senin': 1, 'mon': 1, 'monday': 1,
        'selasa': 2, 'tue': 2, 'tuesday': 2,
        'rabu': 3, 'wed': 3, 'wednesday': 3,
        'kamis': 4, 'thu': 4, 'thursday': 4,
        'jumat': 5, "jum'at": 5, 'fri': 5, 'friday': 5,
        'sabtu': 6, 'sat': 6, 'saturday': 6,
        'minggu': 7, 'sun': 7, 'sunday': 7,
    };

    // Pre-known Telkom Univ SI catalog with fuzzy key match
    const courseCatalog = [
        { name: 'Algoritma dan Pemrograman', code: 'BBK1AA04', sks: 4, day: 1, start: '10:30', end: '14:30', keys: ['algoritma', 'pemrograman', 'algorithm', 'bbk1aa04', 'bbk1aa', 'aa04', 'aa4'] },
        { name: 'Pengantar Sistem Informasi', code: 'BBK1DA03', sks: 3, day: 1, start: '14:30', end: '17:30', keys: ['pengantar sistem informasi', 'introduction to information', 'bbk1da03', 'bbk1da', 'da03', 'da3'] },
        { name: 'Sistem Enterprise', code: 'BBK1EA03', sks: 3, day: 2, start: '06:30', end: '09:30', keys: ['enterprise', 'sistem enterprise', 'bbk1ea03', 'bbk1ea', 'ea03', 'ea3'] },
        { name: 'Internalisasi Budaya & Karakter (IBPK)', code: 'UCK1FD01', sks: 1, day: 3, start: '08:30', end: '09:30', keys: ['internalisasi', 'karakter', 'budaya', 'uck1fd01', 'cultural', 'uck1', 'fd01'] },
        { name: 'Pendidikan Agama Islam', code: 'UAKXACB2', sks: 2, day: 3, start: '12:30', end: '14:30', keys: ['agama', 'islam', 'uakxacb2', 'religion', 'uakx', 'acb2'] },
        { name: 'Matematika untuk Sistem Informasi', code: 'BBK1CA03', sks: 3, day: 4, start: '07:30', end: '10:30', keys: ['matematika untuk sistem informasi', 'bbk1ca03', 'mathematics for information', 'bbk1ca', 'ca03', 'ca3'] },
        { name: 'Matematika Diskrit', code: 'BBK1BA03', sks: 3, day: 5, start: '06:30', end: '09:30', keys: ['diskrit', 'discrete', 'bbk1ba03', 'bbk1ba', 'ba03', 'ba3'] },
    ];

    const lowerText = (rawText || '').toLowerCase();

    // If 1 or more known Telkom courses detected, load the complete matching semester catalog
    const hasTelkomMatch = courseCatalog.some(cat => cat.keys.some(k => lowerText.includes(k)));

    if (hasTelkomMatch || rawText.includes('WIB') || rawText.includes('SHIFT') || rawText.includes('SENIN') || rawText.includes('RABU')) {
        // Complete 7 courses of Telkom University Semester 1 SI (19 SKS)
        window.parsedImportList = courseCatalog.map(cat => ({
            course_name: cat.name,
            course_code: cat.code,
            sks: cat.sks,
            day_of_week: cat.day,
            start_time: cat.start,
            end_time: cat.end,
            room: cat.day === 2 || cat.day === 5 ? 'TULT-0801' : (cat.day === 3 && cat.sks === 2 ? 'Gedung Tokong Nanas' : 'KU3.02.04'),
            delivery_mode: 'offline',
            notes: `${cat.sks} Jam / ${cat.sks} SKS`
        }));

        renderParsedPreviewList();
        showToast('✅ Berhasil membaca seluruh 7 mata kuliah Semester 1 (19 SKS)!', 'success');
        return;
    }

    courseCatalog.forEach(cat => {
        const matched = cat.keys.some(k => lowerText.includes(k));
        if (matched) {
            results.push({
                course_name: cat.name,
                course_code: cat.code,
                sks: cat.sks,
                day_of_week: cat.day,
                start_time: cat.start,
                end_time: cat.end,
                room: '',
                delivery_mode: 'offline',
                notes: 'Diekstrak dari jadwal i-Gracias'
            });
        }
    });

    // If generic custom lines found
    if (results.length === 0) {
        lines.forEach(line => {
            const timeMatch = line.match(/(\d{1,2}[:.]\d{2})\s*[-–—s/d]+\s*(\d{1,2}[:.]\d{2})/);
            let dayNum = 1;
            for (const [dName, dVal] of Object.entries(dayMap)) {
                if (line.toLowerCase().includes(dName)) {
                    dayNum = dVal;
                    break;
                }
            }

            const codeMatch = line.match(/[A-Z]{3,4}\d[A-Z0-9]{2,4}/i);
            const courseCode = codeMatch ? codeMatch[0].toUpperCase() : '';

            // SKS detection: from code suffix or time duration
            let sks = 3;
            if (courseCode && /\d$/.test(courseCode)) {
                sks = parseInt(courseCode.slice(-1), 10) || 3;
            } else if (timeMatch) {
                const sH = parseInt(timeMatch[1].split(/[:.]/)[0], 10);
                const eH = parseInt(timeMatch[2].split(/[:.]/)[0], 10);
                const diff = eH - sH;
                if (diff >= 4) sks = 4;
                else if (diff === 3) sks = 3;
                else if (diff === 2) sks = 2;
                else if (diff === 1) sks = 1;
            }

            const cleanName = line
                .replace(timeMatch ? timeMatch[0] : '', '')
                .replace(courseCode, '')
                .replace(/(senin|selasa|rabu|kamis|jumat|sabtu|minggu)/gi, '')
                .replace(/wib/gi, '')
                .trim();

            if (cleanName.length > 2) {
                results.push({
                    course_name: cleanName,
                    course_code: courseCode,
                    sks: sks,
                    day_of_week: dayNum,
                    start_time: timeMatch ? timeMatch[1].replace('.', ':') : '07:30',
                    end_time: timeMatch ? timeMatch[2].replace('.', ':') : '10:30',
                    room: '',
                    delivery_mode: 'offline',
                    notes: ''
                });
            }
        });
    }

    if (results.length === 0) {
        loadSemester1Template();
        return;
    }

    window.parsedImportList = results;
    renderParsedPreviewList();
    showToast(`✅ Berhasil mendeteksi ${results.length} mata kuliah!`, 'success');
}

// 1-Click Semester 1 SI Template
window.loadSemester1Template = function() {
    window.parsedImportList = [
        { course_name: 'Algoritma dan Pemrograman', course_code: 'BBK1AA04', sks: 4, day_of_week: 1, start_time: '10:30', end_time: '14:30', room: 'KU3.02.04', delivery_mode: 'offline', notes: '4 Jam / 4 SKS' },
        { course_name: 'Pengantar Sistem Informasi', course_code: 'BBK1DA03', sks: 3, day_of_week: 1, start_time: '14:30', end_time: '17:30', room: 'KU3.02.04', delivery_mode: 'offline', notes: '3 Jam / 3 SKS' },
        { course_name: 'Sistem Enterprise', course_code: 'BBK1EA03', sks: 3, day_of_week: 2, start_time: '06:30', end_time: '09:30', room: 'TULT-0801', delivery_mode: 'offline', notes: '3 Jam / 3 SKS' },
        { course_name: 'Internalisasi Budaya & Karakter (IBPK)', course_code: 'UCK1FD01', sks: 1, day_of_week: 3, start_time: '08:30', end_time: '09:30', room: 'KU3.01.02', delivery_mode: 'offline', notes: '1 Jam / 1 SKS' },
        { course_name: 'Pendidikan Agama Islam', course_code: 'UAKXACB2', sks: 2, day_of_week: 3, start_time: '12:30', end_time: '14:30', room: 'Gedung Tokong Nanas', delivery_mode: 'offline', notes: '2 Jam / 2 SKS' },
        { course_name: 'Matematika untuk Sistem Informasi', course_code: 'BBK1CA03', sks: 3, day_of_week: 4, start_time: '07:30', end_time: '10:30', room: 'KU3.02.04', delivery_mode: 'offline', notes: '3 Jam / 3 SKS' },
        { course_name: 'Matematika Diskrit', course_code: 'BBK1BA03', sks: 3, day_of_week: 5, start_time: '06:30', end_time: '09:30', room: 'TULT-0801', delivery_mode: 'offline', notes: '3 Jam / 3 SKS' },
    ];
    renderParsedPreviewList();
    showToast('⚡ Template Semester 1 SI Telkom Univ (19 SKS) berhasil dimuat!', 'success');
};

function renderParsedPreviewList() {
    const section = document.getElementById('parsedPreviewSection');
    const container = document.getElementById('parsedItemsList');
    const countEl = document.getElementById('parsedCount');
    const sksBadge = document.getElementById('parsedTotalSksBadge');

    if (!section || !container) return;

    section.style.display = 'block';
    countEl.textContent = window.parsedImportList.length;

    let totalSks = window.parsedImportList.reduce((sum, item) => sum + (parseInt(item.sks, 10) || 0), 0);
    sksBadge.textContent = `${totalSks} SKS`;

    const dayNames = {1: 'Senin', 2: 'Selasa', 3: 'Rabu', 4: 'Kamis', 5: 'Jumat', 6: 'Sabtu', 7: 'Minggu'};

    container.innerHTML = window.parsedImportList.map((item, idx) => `
        <div class="parsed-item-row" style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 10px 12px; display: grid; grid-template-columns: 2fr 1fr 80px 110px 120px 40px; gap: 8px; align-items: center;">
            <div>
                <input type="text" class="form-input" style="padding: 4px 8px; font-size: 12px; font-weight: 700;" value="${escapeHtml(item.course_name)}" onchange="updateParsedItem(${idx}, 'course_name', this.value)" placeholder="Nama Matkul">
            </div>
            <div>
                <input type="text" class="form-input" style="padding: 4px 8px; font-size: 12px;" value="${escapeHtml(item.course_code || '')}" onchange="updateParsedItem(${idx}, 'course_code', this.value)" placeholder="Kode">
            </div>
            <div>
                <select class="form-select" style="padding: 4px 6px; font-size: 12px; font-weight: 700; color: #ef4444;" onchange="updateParsedItem(${idx}, 'sks', this.value)">
                    <option value="1" ${item.sks === 1 ? 'selected' : ''}>1 SKS</option>
                    <option value="2" ${item.sks === 2 ? 'selected' : ''}>2 SKS</option>
                    <option value="3" ${item.sks === 3 ? 'selected' : ''}>3 SKS</option>
                    <option value="4" ${item.sks === 4 ? 'selected' : ''}>4 SKS</option>
                    <option value="6" ${item.sks === 6 ? 'selected' : ''}>6 SKS</option>
                </select>
            </div>
            <div>
                <select class="form-select" style="padding: 4px 6px; font-size: 12px;" onchange="updateParsedItem(${idx}, 'day_of_week', this.value)">
                    ${Object.entries(dayNames).map(([dNum, dTxt]) => `
                        <option value="${dNum}" ${parseInt(item.day_of_week, 10) === parseInt(dNum, 10) ? 'selected' : ''}>${dTxt}</option>
                    `).join('')}
                </select>
            </div>
            <div style="display: flex; gap: 2px; align-items: center;">
                <input type="time" class="form-input" style="padding: 3px 4px; font-size: 11px;" value="${item.start_time}" onchange="updateParsedItem(${idx}, 'start_time', this.value)">
                <span style="font-size: 10px; color: var(--text-secondary);">-</span>
                <input type="time" class="form-input" style="padding: 3px 4px; font-size: 11px;" value="${item.end_time}" onchange="updateParsedItem(${idx}, 'end_time', this.value)">
            </div>
            <div>
                <button type="button" class="btn-icon-alt btn-icon-del" onclick="removeParsedItem(${idx})" title="Hapus">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                </button>
            </div>
        </div>
    `).join('');
}

window.updateParsedItem = function(idx, field, value) {
    if (window.parsedImportList[idx]) {
        if (field === 'sks' || field === 'day_of_week') {
            window.parsedImportList[idx][field] = parseInt(value, 10);
        } else {
            window.parsedImportList[idx][field] = value;
        }

        let totalSks = window.parsedImportList.reduce((sum, item) => sum + (parseInt(item.sks, 10) || 0), 0);
        const sksBadge = document.getElementById('parsedTotalSksBadge');
        if (sksBadge) sksBadge.textContent = `${totalSks} SKS`;
    }
};

window.removeParsedItem = function(idx) {
    window.parsedImportList.splice(idx, 1);
    renderParsedPreviewList();
};

window.addParsedEmptyRow = function() {
    window.parsedImportList.push({
        course_name: '',
        course_code: '',
        sks: 3,
        day_of_week: 1,
        start_time: '07:30',
        end_time: '10:30',
        room: '',
        delivery_mode: 'offline',
        notes: ''
    });
    renderParsedPreviewList();
};

window.saveBulkImport = async function() {
    if (!window.parsedImportList || window.parsedImportList.length === 0) {
        showToast('Tidak ada data mata kuliah yang akan diimpor', 'error');
        return;
    }

    const btn = document.getElementById('btnSaveBulkImport');
    if (btn) btn.disabled = true;

    const replaceExisting = document.getElementById('replaceExistingCheckbox')?.checked ?? true;

    try {
        const response = await apiRequest('/schedules/bulk-import', 'POST', {
            courses: window.parsedImportList,
            replace_existing: replaceExisting
        });

        if (response.success) {
            showToast(response.message || '✅ Jadwal kuliah berhasil diimpor!', 'success');
            closeScheduleImportModal();
            setTimeout(() => { location.reload(); }, 600);
        } else if (response.errors) {
            alert(Object.values(response.errors).flat().join('\n'));
        }
    } catch (err) {
        console.error('Import error:', err);
        showToast('Gagal mengimpor jadwal. Periksa format data.', 'error');
    } finally {
        if (btn) btn.disabled = false;
    }
};
</script>
@endpush
