@extends('layouts.app')

@section('title', 'Tugas')
@section('page-title', 'Tugas & Deadline')

@section('content')
<div class="page-header">
    <div class="filter-bar">
        <div class="filter-group">
            <select class="filter-select" id="filterPriority" onchange="applyFilters()">
                <option value="">Semua Prioritas User</option>
                <option value="urgent" {{ request('priority') === 'urgent' ? 'selected' : '' }}>🔴 Urgent</option>
                <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>🟠 High</option>
                <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>🟡 Medium</option>
                <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>🟢 Low</option>
            </select>
            <select class="filter-select" id="filterPriorityLevel" onchange="applyFilters()">
                <option value="">Semua Level Prioritas</option>
                <option value="KRITIS" {{ request('priority_level') === 'KRITIS' ? 'selected' : '' }}>🔴 Kritis</option>
                <option value="TINGGI" {{ request('priority_level') === 'TINGGI' ? 'selected' : '' }}>🟠 Tinggi</option>
                <option value="SEDANG" {{ request('priority_level') === 'SEDANG' ? 'selected' : '' }}>🟡 Sedang</option>
                <option value="RENDAH" {{ request('priority_level') === 'RENDAH' ? 'selected' : '' }}>🟢 Rendah</option>
            </select>
            <select class="filter-select" id="filterRiskLevel" onchange="applyFilters()">
                <option value="">Semua Tingkat Risiko</option>
                <option value="KRITIS" {{ request('risk_level') === 'KRITIS' ? 'selected' : '' }}>🔴 Risiko Kritis</option>
                <option value="TINGGI" {{ request('risk_level') === 'TINGGI' ? 'selected' : '' }}>🟠 Risiko Tinggi</option>
                <option value="SEDANG" {{ request('risk_level') === 'SEDANG' ? 'selected' : '' }}>🟡 Risiko Sedang</option>
                <option value="RENDAH" {{ request('risk_level') === 'RENDAH' ? 'selected' : '' }}>🟢 Risiko Rendah</option>
                <option value="AMAN" {{ request('risk_level') === 'AMAN' ? 'selected' : '' }}>🟢 Risiko Aman</option>
            </select>
            <select class="filter-select" id="filterStatus" onchange="applyFilters()">
                <option value="">Semua Status</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
            </select>
            <select class="filter-select" id="filterSort" onchange="applyFilters()">
                <option value="priority_score" {{ request('sort') !== 'deadline' && request('sort') !== 'risk' ? 'selected' : '' }}>⚡ Urut: Skor Prioritas</option>
                <option value="risk" {{ request('sort') === 'risk' ? 'selected' : '' }}>⚠️ Urut: Risiko Tertinggi</option>
                <option value="deadline" {{ request('sort') === 'deadline' ? 'selected' : '' }}>📅 Urut: Deadline Terdekat</option>
            </select>
            @if($subjects->count() > 0)
            <select class="filter-select" id="filterSubject" onchange="applyFilters()">
                <option value="">Semua Mata Kuliah</option>
                @foreach($subjects as $subject)
                <option value="{{ $subject }}" {{ request('subject') === $subject ? 'selected' : '' }}>{{ $subject }}</option>
                @endforeach
            </select>
            @endif
        </div>
        <button class="btn-primary" onclick="openTaskModal()" id="addTaskBtn">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Tambah Tugas
        </button>
    </div>
</div>

<div class="tasks-container" id="tasksContainer">
    @forelse($tasks as $task)
    <div class="task-card glass-card {{ $task->isOverdue() ? 'task-card-overdue' : '' }} {{ $task->status === 'completed' ? 'task-card-completed' : '' }}" data-task-id="{{ $task->id }}" id="task-{{ $task->id }}">
        <div class="task-card-left">
            <button class="task-status-btn status-{{ $task->status }}" onclick="toggleTaskStatus({{ $task->id }})" aria-label="Toggle status">
                @if($task->status === 'completed')
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                @elseif($task->status === 'in_progress')
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"></path></svg>
                @else
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle></svg>
                @endif
            </button>
        </div>
        <div class="task-card-body">
            <div class="task-card-top">
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <h3 class="task-title {{ $task->status === 'completed' ? 'completed' : '' }}">{{ $task->title }}</h3>
                    @if($task->status !== 'completed' && $task->priority_level)
                        <span class="priority-engine-badge level-{{ strtolower($task->priority_level) }}" title="Skor {{ $task->priority_score }}/100: {{ $task->priorityResult->reason }}">
                            ⚡ {{ $task->priority_level }} ({{ $task->priority_score }})
                        </span>
                    @endif
                    @if($task->status !== 'completed' && $task->risk_level)
                        <span class="risk-badge risk-{{ strtolower($task->risk_level) }}" title="Risiko Keterlambatan: {{ $task->riskResult->reason }}">
                            ⚠️ {{ $task->risk_level }} ({{ $task->risk_score }})
                        </span>
                    @endif
                </div>
                <span class="priority-badge priority-{{ $task->priority }}" title="User Importance">{{ ucfirst($task->priority) }}</span>
            </div>
            @if($task->description)
            <p class="task-desc">{{ Str::limit($task->description, 100) }}</p>
            @endif

            @if($task->status !== 'completed')
            <div style="margin: 8px 0; padding: 8px 12px; background: rgba(255,255,255,0.02); border-radius: var(--radius-sm); border: 1px solid var(--border-color); display: flex; flex-direction: column; gap: 6px;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 6px;">
                    <span style="font-size: 11px; color: var(--text-secondary); display: flex; align-items: center; gap: 4px;">
                        <span>Prioritas:</span>
                        <strong class="text-level-{{ strtolower($task->priority_level ?? 'rendah') }}">{{ $task->priority_level ?? 'RENDAH' }} ({{ $task->priority_score ?? 0 }}/100)</strong>
                        <span style="margin: 0 4px; opacity: 0.4;">|</span>
                        <span>Risiko:</span>
                        <strong class="text-level-{{ strtolower($task->risk_level ?? 'aman') }}">{{ $task->risk_level ?? 'AMAN' }} ({{ $task->risk_score ?? 0 }}/100)</strong>
                    </span>
                    <span style="font-size: 11px; color: var(--text-tertiary);">
                        ⏱️ Sisa Kerja: ~{{ round($task->remaining_duration / 60, 1) }}j
                        @if($task->deadline && $task->riskResult->availableHours > 0 && $task->riskResult->availableHours < 900)
                            · Luang: ~{{ $task->riskResult->availableHours }}j
                        @endif
                    </span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div style="flex: 1; height: 6px; background: rgba(255,255,255,0.08); border-radius: 3px; overflow: hidden;" title="Skor Prioritas: {{ $task->priority_score ?? 0 }}%">
                        <div class="priority-progress-bar-fill level-{{ strtolower($task->priority_level ?? 'rendah') }}" style="width: {{ $task->priority_score ?? 0 }}%; height: 100%; border-radius: 3px;"></div>
                    </div>
                    <span style="font-size: 11px; color: var(--text-secondary); min-width: 80px; text-align: right;">Progress: {{ $task->progress ?? 0 }}%</span>
                </div>
                <div style="font-size: 11px; color: var(--text-secondary); display: flex; flex-direction: column; gap: 2px;">
                    <div>💡 {{ $task->priorityResult->reason }}</div>
                    @if($task->risk_level && in_array($task->risk_level, ['KRITIS', 'TINGGI', 'SEDANG']))
                    <div style="color: #fca5a5;">⚠️ {{ $task->riskResult->reason }}</div>
                    @endif
                </div>
            </div>
            @endif

            <div class="task-meta">
                @if($task->subject)
                <span class="task-subject-badge">{{ $task->subject }}</span>
                @endif
                <span class="task-deadline {{ $task->isOverdue() ? 'deadline-overdue' : '' }}">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    {{ $task->deadline->format('d M Y, H:i') }}
                    @if($task->isOverdue())
                        <span class="overdue-label">Overdue!</span>
                    @endif
                </span>
                @if($task->attachments && $task->attachments->count() > 0)
                <div class="attachments-wrap">
                    @foreach($task->attachments as $att)
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
        <div class="task-card-actions">
            <button class="action-btn" onclick="editTask({{ $task->id }}, {{ json_encode($task) }})" aria-label="Edit">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
            </button>
            <button class="action-btn action-delete" onclick="deleteTask({{ $task->id }})" aria-label="Hapus">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
            </button>
        </div>
    </div>
    @empty
    <div class="empty-state">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
            <path d="M9 11l3 3L22 4"></path>
            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
        </svg>
        <h3>Belum ada tugas</h3>
        <p>Klik tombol "Tambah Tugas" untuk mulai mencatat deadline kamu</p>
    </div>
    @endforelse
</div>
@endsection

@push('scripts')
<script>
function applyFilters() {
    const priority = document.getElementById('filterPriority').value;
    const priorityLevel = document.getElementById('filterPriorityLevel').value;
    const riskLevelEl = document.getElementById('filterRiskLevel');
    const riskLevel = riskLevelEl ? riskLevelEl.value : '';
    const status = document.getElementById('filterStatus').value;
    const sort = document.getElementById('filterSort').value;
    const subjectEl = document.getElementById('filterSubject');
    const subject = subjectEl ? subjectEl.value : '';

    const params = new URLSearchParams();
    if (priority) params.set('priority', priority);
    if (priorityLevel) params.set('priority_level', priorityLevel);
    if (riskLevel) params.set('risk_level', riskLevel);
    if (status) params.set('status', status);
    if (sort) params.set('sort', sort);
    if (subject) params.set('subject', subject);

    window.location.href = '{{ route("tasks.index") }}?' + params.toString();
}
</script>
@endpush
