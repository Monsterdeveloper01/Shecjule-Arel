@extends('layouts.app')

@section('title', 'Acara')
@section('page-title', 'Acara & Jadwal')

@section('content')
<div class="page-header">
    <div class="filter-bar">
        <div class="filter-group">
            <select class="filter-select" id="filterCategory" onchange="applyEventFilter()">
                <option value="">Semua Kategori</option>
                <option value="kuliah" {{ request('category') === 'kuliah' ? 'selected' : '' }}>📚 Kuliah</option>
                <option value="ujian" {{ request('category') === 'ujian' ? 'selected' : '' }}>📝 Ujian</option>
                <option value="seminar" {{ request('category') === 'seminar' ? 'selected' : '' }}>🎤 Seminar</option>
                <option value="organisasi" {{ request('category') === 'organisasi' ? 'selected' : '' }}>👥 Organisasi</option>
                <option value="pribadi" {{ request('category') === 'pribadi' ? 'selected' : '' }}>🏠 Pribadi</option>
            </select>
        </div>
        <button class="btn-primary" onclick="openEventModal()" id="addEventBtn">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Tambah Acara
        </button>
    </div>
</div>

<div class="events-container" id="eventsContainer">
    @forelse($events as $event)
    <div class="event-card glass-card" data-event-id="{{ $event->id }}" id="event-{{ $event->id }}">
        <div class="event-card-accent category-{{ $event->category }}"></div>
        <div class="event-card-body">
            <div class="event-card-top">
                <span class="event-category-badge category-{{ $event->category }}">
                    @switch($event->category)
                        @case('kuliah') 📚 Kuliah @break
                        @case('ujian') 📝 Ujian @break
                        @case('seminar') 🎤 Seminar @break
                        @case('organisasi') 👥 Organisasi @break
                        @case('pribadi') 🏠 Pribadi @break
                    @endswitch
                </span>
                <div class="event-actions">
                    <button class="action-btn" onclick="editEvent({{ $event->id }}, {{ json_encode($event) }})" aria-label="Edit">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </button>
                    <button class="action-btn action-delete" onclick="deleteEvent({{ $event->id }})" aria-label="Hapus">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <h3 class="event-title">{{ $event->title }}</h3>
            @if($event->description)
            <p class="event-desc">{{ Str::limit($event->description, 120) }}</p>
            @endif
            <div class="event-meta">
                <span class="event-datetime">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                    </svg>
                    {{ $event->start_date->format('d M Y, H:i') }}
                    @if($event->end_date)
                        — {{ $event->end_date->format('H:i') }}
                    @endif
                </span>
                @if($event->location)
                <span class="event-location">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                        <circle cx="12" cy="10" r="3"></circle>
                    </svg>
                    {{ $event->location }}
                </span>
                @endif
                @if($event->attachments && $event->attachments->count() > 0)
                <div class="attachments-wrap">
                    @foreach($event->attachments as $att)
                    <a href="{{ $att->file_url }}" target="_blank" class="attachment-pill type-{{ $att->file_type ?? 'file' }}" title="{{ $att->file_name }} ({{ $att->formatted_file_size }})">
                        <span class="att-icon">
                            @switch($att->file_type)
                                @case('pdf') 📄 @break
                                @case('word') 📝 @break
                                @case('excel') 📊 @break
                                @case('powerpoint') 📽️ @break
                                @case('image') 🖼️ @break
                                @case('archive') 📦 @break
                                @case('text') 📃 @break
                                @default 📎
                            @endswitch
                        </span>
                        <span class="att-name">{{ $att->file_name }}</span>
                        <span class="att-size">({{ $att->formatted_file_size }})</span>
                        <svg class="att-dl" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                    </a>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>
    @empty
    <div class="empty-state">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="16" y1="2" x2="16" y2="6"></line>
            <line x1="8" y1="2" x2="8" y2="6"></line>
            <line x1="3" y1="10" x2="21" y2="10"></line>
        </svg>
        <h3>Belum ada acara</h3>
        <p>Klik tombol "Tambah Acara" untuk mencatat jadwal kamu</p>
    </div>
    @endforelse
</div>
@endsection

@push('scripts')
<script>
function applyEventFilter() {
    const category = document.getElementById('filterCategory').value;
    const params = new URLSearchParams();
    if (category) params.set('category', category);
    window.location.href = '{{ route("events.index") }}?' + params.toString();
}
</script>
@endpush
