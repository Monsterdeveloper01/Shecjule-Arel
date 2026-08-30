// ===========================
// SCHEDULE — Main JavaScript
// Calendar, Modals, CRUD, PIN
// ===========================

document.addEventListener('DOMContentLoaded', () => {
    initSidebar();
    initFAB();
    initModal();
    initCalendar();
    initPinpad();
    initDashboardToggles();
});

// ===== CSRF Token =====
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

// ===== SIDEBAR =====
function initSidebar() {
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (!mobileMenuBtn) return;

    mobileMenuBtn.addEventListener('click', () => {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('open');
    });

    overlay?.addEventListener('click', () => {
        sidebar.classList.remove('open');
        overlay.classList.remove('open');
    });
}

// ===== FAB (Floating Action Button) =====
function initFAB() {
    const fabBtn = document.getElementById('fabBtn');
    const fabMenu = document.getElementById('fabMenu');

    if (!fabBtn) return;

    fabBtn.addEventListener('click', () => {
        fabBtn.classList.toggle('open');
        fabMenu.classList.toggle('open');
    });

    // Close FAB when clicking outside
    document.addEventListener('click', (e) => {
        const container = document.getElementById('fabContainer');
        if (container && !container.contains(e.target)) {
            fabBtn.classList.remove('open');
            fabMenu.classList.remove('open');
        }
    });

    // FAB actions
    document.getElementById('fabAddTask')?.addEventListener('click', () => {
        fabBtn.classList.remove('open');
        fabMenu.classList.remove('open');
        openTaskModal();
    });
    document.getElementById('fabAddNote')?.addEventListener('click', () => {
        fabBtn.classList.remove('open');
        fabMenu.classList.remove('open');
        openNoteModal();
    });
    document.getElementById('fabAddEvent')?.addEventListener('click', () => {
        fabBtn.classList.remove('open');
        fabMenu.classList.remove('open');
        openEventModal();
    });
}

// ===== MODAL =====
function initModal() {
    const overlay = document.getElementById('modalOverlay');
    const closeBtn = document.getElementById('modalClose');

    closeBtn?.addEventListener('click', closeModal);
    overlay?.addEventListener('click', (e) => {
        if (e.target === overlay) closeModal();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeModal();
    });
}

function openModal(title, bodyHtml) {
    const overlay = document.getElementById('modalOverlay');
    const titleEl = document.getElementById('modalTitle');
    const bodyEl = document.getElementById('modalBody');

    titleEl.textContent = title;
    bodyEl.innerHTML = bodyHtml;
    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';

    // Focus first input
    setTimeout(() => {
        bodyEl.querySelector('input, textarea, select')?.focus();
    }, 100);
}

function closeModal() {
    const overlay = document.getElementById('modalOverlay');
    overlay?.classList.remove('open');
    document.body.style.overflow = '';
}

// ===== TASK CRUD =====
window.openTaskModal = function(task = null) {
    const isEdit = !!task;
    const title = isEdit ? 'Edit Tugas' : 'Tambah Tugas Baru';

    const html = `
        <form id="taskForm" onsubmit="submitTask(event, ${isEdit ? task.id : 'null'})">
            <div class="form-group">
                <label class="form-label" for="taskTitle">Judul *</label>
                <input type="text" class="form-input" id="taskTitle" name="title" value="${isEdit ? escapeHtml(task.title) : ''}" required placeholder="Contoh: Tugas UAS Basis Data">
            </div>
            <div class="form-group">
                <label class="form-label" for="taskDesc">Deskripsi</label>
                <textarea class="form-textarea" id="taskDesc" name="description" placeholder="Detail tugas...">${isEdit && task.description ? escapeHtml(task.description) : ''}</textarea>
            </div>
            <div class="form-group">
                <label class="form-label" for="taskSubject">Mata Kuliah</label>
                <input type="text" class="form-input" id="taskSubject" name="subject" value="${isEdit && task.subject ? escapeHtml(task.subject) : ''}" placeholder="Contoh: Basis Data">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="taskDeadline">Deadline *</label>
                    <input type="datetime-local" class="form-input" id="taskDeadline" name="deadline" value="${isEdit ? formatDatetimeLocal(task.deadline) : ''}" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="taskPriority">Prioritas *</label>
                    <select class="form-select" id="taskPriority" name="priority" required>
                        <option value="low" ${isEdit && task.priority === 'low' ? 'selected' : ''}>🟢 Low</option>
                        <option value="medium" ${!isEdit || task.priority === 'medium' ? 'selected' : ''}>🟡 Medium</option>
                        <option value="high" ${isEdit && task.priority === 'high' ? 'selected' : ''}>🟠 High</option>
                        <option value="urgent" ${isEdit && task.priority === 'urgent' ? 'selected' : ''}>🔴 Urgent</option>
                    </select>
                </div>
            </div>
            ${isEdit ? `
            <div class="form-group">
                <label class="form-label" for="taskStatus">Status</label>
                <select class="form-select" id="taskStatus" name="status">
                    <option value="pending" ${task.status === 'pending' ? 'selected' : ''}>Pending</option>
                    <option value="in_progress" ${task.status === 'in_progress' ? 'selected' : ''}>In Progress</option>
                    <option value="completed" ${task.status === 'completed' ? 'selected' : ''}>Completed</option>
                </select>
            </div>` : ''}
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn-primary">${isEdit ? 'Simpan' : 'Tambah'}</button>
            </div>
        </form>
    `;
    openModal(title, html);
};

window.editTask = function(id, task) {
    openTaskModal(task);
};

window.submitTask = async function(e, taskId) {
    e.preventDefault();
    const form = document.getElementById('taskForm');
    const data = {
        title: form.querySelector('#taskTitle').value,
        description: form.querySelector('#taskDesc').value,
        subject: form.querySelector('#taskSubject').value || null,
        deadline: form.querySelector('#taskDeadline').value,
        priority: form.querySelector('#taskPriority').value,
        status: form.querySelector('#taskStatus')?.value || 'pending',
    };

    const url = taskId ? `/tasks/${taskId}` : '/tasks';
    const method = taskId ? 'PUT' : 'POST';

    const result = await apiRequest(url, method, data);
    if (result.success) {
        closeModal();
        location.reload();
    }
};

window.toggleTaskStatus = async function(taskId) {
    await apiRequest(`/tasks/${taskId}/toggle`, 'PATCH');
    location.reload();
};

window.deleteTask = async function(taskId) {
    if (!confirm('Yakin mau hapus tugas ini?')) return;
    const result = await apiRequest(`/tasks/${taskId}`, 'DELETE');
    if (result.success) {
        const card = document.getElementById(`task-${taskId}`);
        if (card) {
            card.style.transition = 'all 0.3s ease';
            card.style.opacity = '0';
            card.style.transform = 'translateX(-20px)';
            setTimeout(() => card.remove(), 300);
        }
    }
};

// ===== NOTE CRUD =====
const noteColors = ['#6366f1', '#ef4444', '#f59e0b', '#22c55e', '#3b82f6', '#8b5cf6', '#ec4899', '#14b8a6'];

window.openNoteModal = function(note = null) {
    const isEdit = !!note;
    const title = isEdit ? 'Edit Catatan' : 'Catatan Baru';
    const currentColor = (isEdit && note.color) ? note.color : '#6366f1';

    const colorOptions = noteColors.map(c =>
        `<div class="color-option ${c === currentColor ? 'selected' : ''}" style="background: ${c}" data-color="${c}" onclick="selectNoteColor('${c}')"></div>`
    ).join('');

    const html = `
        <form id="noteForm" onsubmit="submitNote(event, ${isEdit ? note.id : 'null'})">
            <div class="form-group">
                <label class="form-label" for="noteTitle">Judul *</label>
                <input type="text" class="form-input" id="noteTitle" name="title" value="${isEdit ? escapeHtml(note.title) : ''}" required placeholder="Judul catatan...">
            </div>
            <div class="form-group">
                <label class="form-label" for="noteContent">Isi</label>
                <textarea class="form-textarea" id="noteContent" name="content" rows="5" placeholder="Tulis catatan kamu...">${isEdit && note.content ? escapeHtml(note.content) : ''}</textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="noteDate">Tanggal</label>
                    <input type="date" class="form-input" id="noteDate" name="note_date" value="${isEdit && note.note_date ? note.note_date.substring(0, 10) : ''}">
                </div>
                <div class="form-group">
                    <label class="form-label">Warna</label>
                    <div class="color-picker-group">${colorOptions}</div>
                    <input type="hidden" id="noteColor" name="color" value="${currentColor}">
                </div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn-primary">${isEdit ? 'Simpan' : 'Tambah'}</button>
            </div>
        </form>
    `;
    openModal(title, html);
};

window.selectNoteColor = function(color) {
    document.querySelectorAll('.color-option').forEach(el => el.classList.remove('selected'));
    document.querySelector(`.color-option[data-color="${color}"]`)?.classList.add('selected');
    document.getElementById('noteColor').value = color;
};

window.editNote = function(id, note) {
    openNoteModal(note);
};

window.submitNote = async function(e, noteId) {
    e.preventDefault();
    const form = document.getElementById('noteForm');
    const data = {
        title: form.querySelector('#noteTitle').value,
        content: form.querySelector('#noteContent').value,
        color: form.querySelector('#noteColor').value,
        note_date: form.querySelector('#noteDate').value || null,
    };

    const url = noteId ? `/notes/${noteId}` : '/notes';
    const method = noteId ? 'PUT' : 'POST';

    const result = await apiRequest(url, method, data);
    if (result.success) {
        closeModal();
        location.reload();
    }
};

window.togglePin = async function(noteId) {
    await apiRequest(`/notes/${noteId}/pin`, 'PATCH');
    location.reload();
};

window.deleteNote = async function(noteId) {
    if (!confirm('Yakin mau hapus catatan ini?')) return;
    const result = await apiRequest(`/notes/${noteId}`, 'DELETE');
    if (result.success) {
        const card = document.getElementById(`note-${noteId}`);
        if (card) {
            card.style.transition = 'all 0.3s ease';
            card.style.opacity = '0';
            card.style.transform = 'scale(0.9)';
            setTimeout(() => card.remove(), 300);
        }
    }
};

// ===== EVENT CRUD =====
window.openEventModal = function(event = null) {
    const isEdit = !!event;
    const title = isEdit ? 'Edit Acara' : 'Acara Baru';

    const html = `
        <form id="eventForm" onsubmit="submitEvent(event, ${isEdit ? event.id : 'null'})">
            <div class="form-group">
                <label class="form-label" for="eventTitle">Judul *</label>
                <input type="text" class="form-input" id="eventTitle" name="title" value="${isEdit ? escapeHtml(event.title) : ''}" required placeholder="Nama acara...">
            </div>
            <div class="form-group">
                <label class="form-label" for="eventDesc">Deskripsi</label>
                <textarea class="form-textarea" id="eventDesc" name="description" placeholder="Detail acara...">${isEdit && event.description ? escapeHtml(event.description) : ''}</textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="eventCategory">Kategori *</label>
                    <select class="form-select" id="eventCategory" name="category" required>
                        <option value="kuliah" ${isEdit && event.category === 'kuliah' ? 'selected' : ''}>📚 Kuliah</option>
                        <option value="ujian" ${isEdit && event.category === 'ujian' ? 'selected' : ''}>📝 Ujian</option>
                        <option value="seminar" ${isEdit && event.category === 'seminar' ? 'selected' : ''}>🎤 Seminar</option>
                        <option value="organisasi" ${isEdit && event.category === 'organisasi' ? 'selected' : ''}>👥 Organisasi</option>
                        <option value="pribadi" ${!isEdit || event.category === 'pribadi' ? 'selected' : ''}>🏠 Pribadi</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="eventLocation">Lokasi</label>
                    <input type="text" class="form-input" id="eventLocation" name="location" value="${isEdit && event.location ? escapeHtml(event.location) : ''}" placeholder="Contoh: GKU Lt.3">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="eventStart">Mulai *</label>
                    <input type="datetime-local" class="form-input" id="eventStart" name="start_date" value="${isEdit ? formatDatetimeLocal(event.start_date) : ''}" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="eventEnd">Selesai</label>
                    <input type="datetime-local" class="form-input" id="eventEnd" name="end_date" value="${isEdit && event.end_date ? formatDatetimeLocal(event.end_date) : ''}">
                </div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn-primary">${isEdit ? 'Simpan' : 'Tambah'}</button>
            </div>
        </form>
    `;
    openModal(title, html);
};

window.editEvent = function(id, event) {
    openEventModal(event);
};

window.submitEvent = async function(e, eventId) {
    e.preventDefault();
    const form = document.getElementById('eventForm');
    const data = {
        title: form.querySelector('#eventTitle').value,
        description: form.querySelector('#eventDesc').value,
        location: form.querySelector('#eventLocation').value || null,
        category: form.querySelector('#eventCategory').value,
        start_date: form.querySelector('#eventStart').value,
        end_date: form.querySelector('#eventEnd').value || null,
    };

    const url = eventId ? `/events/${eventId}` : '/events';
    const method = eventId ? 'PUT' : 'POST';

    const result = await apiRequest(url, method, data);
    if (result.success) {
        closeModal();
        location.reload();
    }
};

window.deleteEvent = async function(eventId) {
    if (!confirm('Yakin mau hapus acara ini?')) return;
    const result = await apiRequest(`/events/${eventId}`, 'DELETE');
    if (result.success) {
        const card = document.getElementById(`event-${eventId}`);
        if (card) {
            card.style.transition = 'all 0.3s ease';
            card.style.opacity = '0';
            card.style.transform = 'translateX(-20px)';
            setTimeout(() => card.remove(), 300);
        }
    }
};

// ===== CALENDAR =====
let currentYear, currentMonth, calendarData = {};

function initCalendar() {
    const grid = document.getElementById('calendarGrid');
    if (!grid) return;

    const now = new Date();
    currentYear = now.getFullYear();
    currentMonth = now.getMonth();

    document.getElementById('calPrev')?.addEventListener('click', () => {
        currentMonth--;
        if (currentMonth < 0) { currentMonth = 11; currentYear--; }
        loadCalendar();
    });

    document.getElementById('calNext')?.addEventListener('click', () => {
        currentMonth++;
        if (currentMonth > 11) { currentMonth = 0; currentYear++; }
        loadCalendar();
    });

    document.getElementById('dayDetailClose')?.addEventListener('click', () => {
        document.getElementById('dayDetailPanel')?.classList.remove('open');
    });

    loadCalendar();
}

async function loadCalendar() {
    const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    document.getElementById('calMonthTitle').textContent =
        `${monthNames[currentMonth]} ${currentYear}`;

    // Fetch data
    try {
        const res = await fetch(`/calendar-data?year=${currentYear}&month=${currentMonth + 1}`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() }
        });
        calendarData = await res.json();
    } catch {
        calendarData = { tasks: {}, events: {}, notes: {} };
    }

    renderCalendar();
}

function renderCalendar() {
    const grid = document.getElementById('calendarGrid');
    grid.innerHTML = '';

    const firstDay = new Date(currentYear, currentMonth, 1).getDay();
    const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
    const daysInPrevMonth = new Date(currentYear, currentMonth, 0).getDate();
    const today = new Date();

    // Previous month days
    for (let i = firstDay - 1; i >= 0; i--) {
        const day = daysInPrevMonth - i;
        const el = createCalDay(day, true);
        grid.appendChild(el);
    }

    // Current month days
    for (let d = 1; d <= daysInMonth; d++) {
        const dateStr = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        const isToday = today.getFullYear() === currentYear &&
                        today.getMonth() === currentMonth &&
                        today.getDate() === d;

        const hasTasks = calendarData.tasks && calendarData.tasks[dateStr];
        const hasEvents = calendarData.events && calendarData.events[dateStr];
        const hasNotes = calendarData.notes && calendarData.notes[dateStr];

        const el = createCalDay(d, false, isToday, dateStr, hasTasks, hasEvents, hasNotes);
        grid.appendChild(el);
    }

    // Next month days
    const totalCells = grid.children.length;
    const remaining = 42 - totalCells;
    for (let i = 1; i <= remaining; i++) {
        const el = createCalDay(i, true);
        grid.appendChild(el);
    }
}

function createCalDay(day, isOtherMonth, isToday = false, dateStr = '', hasTasks = null, hasEvents = null, hasNotes = null) {
    const el = document.createElement('div');
    el.className = 'cal-day';
    if (isOtherMonth) el.classList.add('other-month');
    if (isToday) el.classList.add('today');

    const numSpan = document.createElement('span');
    numSpan.textContent = day;
    el.appendChild(numSpan);

    if (hasTasks || hasEvents || hasNotes) {
        const dots = document.createElement('div');
        dots.className = 'cal-day-dots';
        if (hasTasks) { const d = document.createElement('span'); d.className = 'cal-dot dot-task'; dots.appendChild(d); }
        if (hasEvents) { const d = document.createElement('span'); d.className = 'cal-dot dot-event'; dots.appendChild(d); }
        if (hasNotes) { const d = document.createElement('span'); d.className = 'cal-dot dot-note'; dots.appendChild(d); }
        el.appendChild(dots);
    }

    if (dateStr && !isOtherMonth) {
        el.addEventListener('click', () => openDayDetail(dateStr, day));
    }

    return el;
}

function openDayDetail(dateStr, day) {
    const panel = document.getElementById('dayDetailPanel');
    const titleEl = document.getElementById('dayDetailTitle');
    const contentEl = document.getElementById('dayDetailContent');

    const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    titleEl.textContent = `${day} ${monthNames[currentMonth]} ${currentYear}`;

    const tasks = calendarData.tasks?.[dateStr] || [];
    const events = calendarData.events?.[dateStr] || [];
    const notes = calendarData.notes?.[dateStr] || [];

    let html = '';

    if (tasks.length === 0 && events.length === 0 && notes.length === 0) {
        html = '<div class="day-detail-empty">Tidak ada aktivitas di hari ini</div>';
    } else {
        if (tasks.length > 0) {
            html += '<div class="day-detail-section"><h4>📋 Tugas (' + tasks.length + ')</h4>';
            tasks.forEach(t => {
                html += `<div class="day-detail-item" style="border-left-color: var(--priority-${t.priority})">
                    <div class="item-title">${escapeHtml(t.title)}</div>
                    <div class="item-meta">${t.subject || ''} · ${t.priority} · ${t.status}</div>
                </div>`;
            });
            html += '</div>';
        }
        if (events.length > 0) {
            html += '<div class="day-detail-section"><h4>🗓️ Acara (' + events.length + ')</h4>';
            events.forEach(e => {
                html += `<div class="day-detail-item" style="border-left-color: var(--cat-${e.category})">
                    <div class="item-title">${escapeHtml(e.title)}</div>
                    <div class="item-meta">${e.category}${e.location ? ' · ' + e.location : ''}</div>
                </div>`;
            });
            html += '</div>';
        }
        if (notes.length > 0) {
            html += '<div class="day-detail-section"><h4>📌 Catatan (' + notes.length + ')</h4>';
            notes.forEach(n => {
                html += `<div class="day-detail-item" style="border-left-color: ${n.color || '#6366f1'}">
                    <div class="item-title">${escapeHtml(n.title)}</div>
                </div>`;
            });
            html += '</div>';
        }
    }

    contentEl.innerHTML = html;

    // Remove selected from all days
    document.querySelectorAll('.cal-day.selected').forEach(el => el.classList.remove('selected'));

    panel.classList.add('open');
}

// ===== PIN PAD =====
function initPinpad() {
    const numpad = document.getElementById('numpad');
    if (!numpad) return;

    // Detect setup vs login
    const isSetup = !!document.getElementById('pinSetupForm');

    if (isSetup) {
        initSetupPinpad();
    } else {
        initLoginPinpad();
    }
}

function initLoginPinpad() {
    let pin = '';
    const maxLen = 6;
    const dots = document.querySelectorAll('#pinDots .pin-dot');
    const hiddenInput = document.getElementById('pinInput');
    const form = document.getElementById('pinLoginForm');

    document.querySelectorAll('.numpad-key').forEach(key => {
        key.addEventListener('click', () => {
            const val = key.dataset.key;

            if (val === 'delete') {
                pin = pin.slice(0, -1);
            } else if (pin.length < maxLen) {
                pin += val;
            }

            // Update dots
            dots.forEach((dot, i) => {
                dot.classList.toggle('filled', i < pin.length);
            });

            hiddenInput.value = pin;

            // Auto submit when 6 digits
            if (pin.length === maxLen) {
                setTimeout(() => form.submit(), 200);
            }
        });
    });
}

function initSetupPinpad() {
    let pin = '';
    let confirmPin = '';
    let activeField = 'pin'; // 'pin' or 'confirm'
    const maxLen = 6;
    const pinDots = document.querySelectorAll('#pinDotsSetup .pin-dot');
    const confirmDots = document.querySelectorAll('#pinDotsConfirm .pin-dot');
    const pinInput = document.getElementById('pinInput');
    const confirmInput = document.getElementById('pinConfirmInput');
    const submitBtn = document.getElementById('setupSubmitBtn');

    // Click on pin dots to switch active field
    document.getElementById('pinDotsSetup')?.addEventListener('click', () => {
        activeField = 'pin';
        highlightActiveDots();
    });
    document.getElementById('pinDotsConfirm')?.addEventListener('click', () => {
        activeField = 'confirm';
        highlightActiveDots();
    });

    function highlightActiveDots() {
        document.getElementById('pinDotsSetup').style.opacity = activeField === 'pin' ? '1' : '0.5';
        document.getElementById('pinDotsConfirm').style.opacity = activeField === 'confirm' ? '1' : '0.5';
    }
    highlightActiveDots();

    document.querySelectorAll('.numpad-key').forEach(key => {
        key.addEventListener('click', () => {
            const val = key.dataset.key;

            if (activeField === 'pin') {
                if (val === 'delete') {
                    pin = pin.slice(0, -1);
                } else if (pin.length < maxLen) {
                    pin += val;
                }
                pinDots.forEach((dot, i) => dot.classList.toggle('filled', i < pin.length));
                pinInput.value = pin;

                if (pin.length === maxLen) {
                    activeField = 'confirm';
                    highlightActiveDots();
                }
            } else {
                if (val === 'delete') {
                    confirmPin = confirmPin.slice(0, -1);
                } else if (confirmPin.length < maxLen) {
                    confirmPin += val;
                }
                confirmDots.forEach((dot, i) => dot.classList.toggle('filled', i < confirmPin.length));
                confirmInput.value = confirmPin;
            }

            // Enable submit when both are filled
            submitBtn.disabled = !(pin.length === maxLen && confirmPin.length === maxLen);
        });
    });
}

// ===== DASHBOARD TOGGLES =====
function initDashboardToggles() {
    document.querySelectorAll('.task-toggle-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const taskId = btn.dataset.taskId;
            await apiRequest(`/tasks/${taskId}/toggle`, 'PATCH');
            location.reload();
        });
    });
}

// ===== UTILITY =====
function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function formatDatetimeLocal(dateStr) {
    if (!dateStr) return '';
    try {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return '';
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        const hours = String(d.getHours()).padStart(2, '0');
        const mins = String(d.getMinutes()).padStart(2, '0');
        return `${year}-${month}-${day}T${hours}:${mins}`;
    } catch {
        return '';
    }
}
