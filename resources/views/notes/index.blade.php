@extends('layouts.app')

@section('title', 'Catatan')
@section('page-title', 'Catatan')

@section('content')
<div class="page-header">
    <div class="filter-bar">
        <div class="filter-group"></div>
        <button class="btn-primary" onclick="openNoteModal()" id="addNoteBtn">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Tambah Catatan
        </button>
    </div>
</div>

<div class="notes-grid" id="notesGrid">
    @forelse($notes as $note)
    <div class="note-card glass-card" style="--note-accent: {{ $note->color ?? '#6366f1' }}" data-note-id="{{ $note->id }}" id="note-{{ $note->id }}">
        @if($note->is_pinned)
        <div class="note-pin-badge">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2">
                <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2z"></path>
            </svg>
        </div>
        @endif
        <div class="note-card-header">
            <h3 class="note-title">{{ $note->title }}</h3>
            <div class="note-actions">
                <button class="action-btn" onclick="togglePin({{ $note->id }})" aria-label="{{ $note->is_pinned ? 'Unpin' : 'Pin' }}">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="{{ $note->is_pinned ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2">
                        <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2z"></path>
                    </svg>
                </button>
                <button class="action-btn" onclick="editNote({{ $note->id }}, {{ json_encode($note) }})" aria-label="Edit">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                </button>
                <button class="action-btn action-delete" onclick="deleteNote({{ $note->id }})" aria-label="Hapus">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                </button>
            </div>
        </div>
        @if($note->content)
        <p class="note-content">{{ Str::limit($note->content, 200) }}</p>
        @endif
        @if($note->attachments && $note->attachments->count() > 0)
        <div class="attachments-wrap" style="margin-bottom: 12px;">
            @foreach($note->attachments as $att)
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
        <div class="note-footer">
            @if($note->note_date)
            <span class="note-date">{{ $note->note_date->format('d M Y') }}</span>
            @endif
            <span class="note-updated">{{ $note->updated_at->diffForHumans() }}</span>
        </div>
        <div class="note-accent-bar" style="background: {{ $note->color ?? '#6366f1' }}"></div>
    </div>
    @empty
    <div class="empty-state" style="grid-column: 1 / -1">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
            <polyline points="14 2 14 8 20 8"></polyline>
        </svg>
        <h3>Belum ada catatan</h3>
        <p>Klik tombol "Tambah Catatan" untuk mulai mencatat</p>
    </div>
    @endforelse
</div>
@endsection
