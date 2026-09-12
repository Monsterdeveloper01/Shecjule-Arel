// ===========================
// SCHEDULE — Main JavaScript
// Calendar, Modals, CRUD, PIN, Multi-File Drag & Drop
// ===========================

document.addEventListener('DOMContentLoaded', () => {
    initSidebar();
    initFAB();
    initModal();
    initCalendar();
    initPinpad();
    initDashboardToggles();
    initPushNotifications();
});

// ===== Global State for Multi-File Upload Queue =====
window.activeUploadFiles = [];

// ===== CSRF Token & API =====
function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}
window.getCsrfToken = getCsrfToken;

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
window.apiRequest = apiRequest;

async function apiFormRequest(url, formData) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
        },
        body: formData,
    });
    return response.json();
}
window.apiFormRequest = apiFormRequest;

// ===== FILE HELPERS =====
function getFileIcon(type) {
    switch (type) {
        case 'pdf': return '📄';
        case 'word': return '📝';
        case 'excel': return '📊';
        case 'powerpoint': return '📽️';
        case 'image': return '🖼️';
        case 'archive': return '📦';
        case 'text': return '📃';
        default: return '📎';
    }
}

function getExtFromFilename(filename) {
    if (!filename) return 'file';
    const ext = filename.split('.').pop().toLowerCase();
    switch (ext) {
        case 'pdf': return 'pdf';
        case 'doc': case 'docx': case 'odt': case 'rtf': return 'word';
        case 'xls': case 'xlsx': case 'csv': case 'ods': return 'excel';
        case 'ppt': case 'pptx': case 'odp': return 'powerpoint';
        case 'jpg': case 'jpeg': case 'png': case 'gif': case 'webp': case 'svg': return 'image';
        case 'zip': case 'rar': case '7z': case 'tar': case 'gz': return 'archive';
        case 'txt': case 'md': return 'text';
        default: return 'file';
    }
}

function formatBytes(bytes) {
    if (!bytes || bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

// ===== DRAG & DROP MULTI-FILE QUEUE =====
function addFilesToQueue(fileList, targetListId) {
    if (!fileList || fileList.length === 0) return;

    Array.from(fileList).forEach(file => {
        const exists = window.activeUploadFiles.some(f => f.name === file.name && f.size === file.size);
        if (!exists) {
            window.activeUploadFiles.push(file);
        }
    });

    renderSelectedFilesList(targetListId);
}

function renderSelectedFilesList(targetListId) {
    const container = document.getElementById(targetListId);
    if (!container) return;

    container.innerHTML = '';

    if (window.activeUploadFiles.length === 0) {
        container.style.display = 'none';
        return;
    }

    window.activeUploadFiles.forEach((file, index) => {
        const extCategory = getExtFromFilename(file.name);
        const item = document.createElement('div');
        item.className = 'file-selected-item';
        item.innerHTML = `
            <div style="display: flex; align-items: center; gap: 6px; min-width: 0; flex: 1;">
                <span>${getFileIcon(extCategory)}</span>
                <span class="name" title="${escapeHtml(file.name)}">${escapeHtml(file.name)}</span>
                <span class="size">(${formatBytes(file.size)})</span>
            </div>
            <button type="button" class="remove-btn" onclick="removeQueuedFile(${index}, '${targetListId}')" title="Hapus file ini">&times;</button>
        `;
        container.appendChild(item);
    });

    container.style.display = 'flex';
}

window.removeQueuedFile = function(index, targetListId) {
    window.activeUploadFiles.splice(index, 1);
    renderSelectedFilesList(targetListId);
};

function setupDropZone(zoneId, inputId, targetListId) {
    const zone = document.getElementById(zoneId);
    const input = document.getElementById(inputId);
    if (!zone) return;

    ['dragenter', 'dragover'].forEach(eventName => {
        zone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            zone.classList.add('drag-over');
        }, false);
    });

    ['dragleave', 'dragend'].forEach(eventName => {
        zone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            zone.classList.remove('drag-over');
        }, false);
    });

    zone.addEventListener('drop', (e) => {
        e.preventDefault();
        e.stopPropagation();
        zone.classList.remove('drag-over');
        if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
            addFilesToQueue(e.dataTransfer.files, targetListId);
        }
    }, false);

    if (input) {
        input.addEventListener('change', (e) => {
            if (input.files && input.files.length > 0) {
                addFilesToQueue(input.files, targetListId);
                input.value = ''; // Clear to allow selecting the same file again if re-added
            }
        });
    }
}

window.toggleDeleteAttachment = function(id, btn) {
    const item = document.getElementById(`existing-att-${id}`);
    const inputContainer = document.getElementById('deletedAttachmentsInputs');
    if (!item || !inputContainer) return;

    let hiddenInput = document.getElementById(`deleted-att-input-${id}`);

    if (hiddenInput) {
        hiddenInput.remove();
        item.classList.remove('marked-deleted');
        btn.textContent = 'Hapus';
        btn.style.background = 'rgba(239, 68, 68, 0.1)';
    } else {
        hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'deleted_attachment_ids[]';
        hiddenInput.value = id;
        hiddenInput.id = `deleted-att-input-${id}`;
        inputContainer.appendChild(hiddenInput);

        item.classList.add('marked-deleted');
        btn.textContent = 'Batal Hapus';
        btn.style.background = 'rgba(34, 197, 94, 0.2)';
    }
};

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

    document.addEventListener('click', (e) => {
        const container = document.getElementById('fabContainer');
        if (container && !container.contains(e.target)) {
            fabBtn.classList.remove('open');
            fabMenu.classList.remove('open');
        }
    });

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
    document.getElementById('fabAddSchedule')?.addEventListener('click', () => {
        fabBtn.classList.remove('open');
        fabMenu.classList.remove('open');
        openScheduleModal();
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

    // Global listener: clicking on backdrop of any modal overlay closes it
    document.addEventListener('click', (e) => {
        if (e.target && e.target.classList && e.target.classList.contains('modal-overlay')) {
            e.target.classList.remove('open', 'active');
            document.body.style.overflow = '';
        }
    });

    // Global listener: Escape key closes all open modals
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeModal();
            document.querySelectorAll('.modal-overlay.open, .modal-overlay.active').forEach(m => {
                m.classList.remove('open', 'active');
            });
            document.body.style.overflow = '';
        }
    });
}

window.openModal = function(title, bodyHtml) {
    const overlay = document.getElementById('modalOverlay');
    const titleEl = document.getElementById('modalTitle');
    const bodyEl = document.getElementById('modalBody');

    window.activeUploadFiles = []; // Reset queue for fresh modal

    titleEl.textContent = title;
    bodyEl.innerHTML = bodyHtml;
    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';

    setTimeout(() => {
        bodyEl.querySelector('input:not([type="hidden"]), textarea, select')?.focus();
    }, 100);
};

window.closeModal = function() {
    const overlay = document.getElementById('modalOverlay');
    overlay?.classList.remove('open');
    document.body.style.overflow = '';
    window.activeUploadFiles = [];
};

// ===== HELPER: BUILD EXISTING ATTACHMENTS HTML =====
function buildExistingAttachmentsHtml(attachments = []) {
    if (!attachments || attachments.length === 0) return '';

    let itemsHtml = '';
    attachments.forEach(att => {
        itemsHtml += `
            <div class="existing-attachment-item" id="existing-att-${att.id}">
                <a href="${att.file_url}" target="_blank" class="file-link" title="${escapeHtml(att.file_name)}">
                    <span>${getFileIcon(att.file_type)}</span>
                    <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${escapeHtml(att.file_name)}</span>
                    <span style="font-size: 10px; color: var(--text-tertiary);">(${att.formatted_file_size || ''})</span>
                </a>
                <button type="button" class="btn-remove-file" onclick="toggleDeleteAttachment(${att.id}, this)">Hapus</button>
            </div>
        `;
    });

    return `
        <label class="form-label" style="font-size: 12px; color: var(--text-secondary); margin-top: 4px;">File Terlampir (${attachments.length}):</label>
        <div class="existing-attachments-list">
            ${itemsHtml}
        </div>
        <div id="deletedAttachmentsInputs"></div>
    `;
}

// ===== TASK CRUD =====
window.openTaskModal = function(task = null) {
    const isEdit = !!task;
    const title = isEdit ? 'Edit Tugas' : 'Tambah Tugas Baru';
    const attachments = task?.attachments || [];
    const existingFilesHtml = isEdit ? buildExistingAttachmentsHtml(attachments) : '';

    const html = `
        <form id="taskForm" onsubmit="submitTask(event, ${isEdit ? task.id : 'null'})" enctype="multipart/form-data">
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
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="taskDuration">Estimasi Durasi (Opsional)</label>
                    <select class="form-select" id="taskDuration" name="estimated_duration">
                        <option value="30" ${task?.estimated_duration == 30 ? 'selected' : ''}>⏱️ 30 Menit</option>
                        <option value="60" ${task?.estimated_duration == 60 ? 'selected' : ''}>⏱️ 1 Jam</option>
                        <option value="120" ${(!task || !task.estimated_duration || task.estimated_duration == 120) ? 'selected' : ''}>⏱️ 2 Jam (Default)</option>
                        <option value="180" ${task?.estimated_duration == 180 ? 'selected' : ''}>⏱️ 3 Jam</option>
                        <option value="240" ${task?.estimated_duration == 240 ? 'selected' : ''}>⏱️ 4 Jam</option>
                        <option value="360" ${task?.estimated_duration == 360 ? 'selected' : ''}>⏱️ 6 Jam</option>
                        <option value="480" ${task?.estimated_duration == 480 ? 'selected' : ''}>⏱️ 8 Jam</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="taskProgress">Progress Saat Ini: <strong id="progressValue" style="color: #818cf8;">${task?.progress ?? 0}%</strong></label>
                    <input type="range" class="form-input" id="taskProgress" name="progress" min="0" max="100" step="5" value="${task?.progress ?? 0}" oninput="document.getElementById('progressValue').innerText = this.value + '%'" style="cursor: pointer; padding: 4px; accent-color: #6366f1; margin-top: 6px;">
                </div>
            </div>
            ${isEdit && task.priority_level ? `
            <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 8px 12px; margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between;">
                <span style="font-size: 12px; color: var(--text-secondary);">⚡ Skor Prioritas Terhitung:</span>
                <span class="priority-engine-badge level-${(task.priority_level || 'rendah').toLowerCase()}">
                    ${task.priority_level} (${task.priority_score || 0}/100)
                </span>
            </div>
            ` : ''}
            ${isEdit ? `
            <div class="form-group">
                <label class="form-label" for="taskStatus">Status</label>
                <select class="form-select" id="taskStatus" name="status">
                    <option value="pending" ${task.status === 'pending' ? 'selected' : ''}>Pending</option>
                    <option value="in_progress" ${task.status === 'in_progress' ? 'selected' : ''}>In Progress</option>
                    <option value="completed" ${task.status === 'completed' ? 'selected' : ''}>Completed</option>
                </select>
            </div>` : ''}
            
            <div class="form-group">
                <label class="form-label">Lampiran File (Tarik & lepas banyak file sekaligus atau klik untuk memilih)</label>
                ${existingFilesHtml}
                <div class="file-upload-zone" id="taskDropZone">
                    <input type="file" id="taskFileInput" multiple>
                    <div class="file-upload-label">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                        <span>${isEdit && attachments.length > 0 ? '+ Tambah / Tarik file lampiran ke sini' : 'Tarik & Lepas beberapa file ke sini atau Klik untuk memilih'}</span>
                    </div>
                </div>
                <div class="file-selected-list" id="taskSelectedList" style="display: none;"></div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn-primary" id="taskSubmitBtn">${isEdit ? 'Simpan' : 'Tambah'}</button>
            </div>
        </form>
    `;
    openModal(title, html);
    setupDropZone('taskDropZone', 'taskFileInput', 'taskSelectedList');
};

window.editTask = function(id, task) {
    openTaskModal(task);
};

window.submitTask = async function(e, taskId) {
    e.preventDefault();
    const form = document.getElementById('taskForm');
    const submitBtn = document.getElementById('taskSubmitBtn');
    if (submitBtn) submitBtn.disabled = true;

    const formData = new FormData(form);

    // Append all queued files
    window.activeUploadFiles.forEach(file => {
        formData.append('files[]', file);
    });

    if (taskId) {
        formData.append('_method', 'PUT');
    }

    const url = taskId ? `/tasks/${taskId}` : '/tasks';

    try {
        const result = await apiFormRequest(url, formData);
        if (result.success) {
            closeModal();
            location.reload();
        } else if (result.errors) {
            alert(Object.values(result.errors).flat().join('\n'));
        }
    } catch (err) {
        alert('Gagal menyimpan tugas. Periksa ukuran file atau koneksi.');
    } finally {
        if (submitBtn) submitBtn.disabled = false;
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
    const attachments = note?.attachments || [];
    const existingFilesHtml = isEdit ? buildExistingAttachmentsHtml(attachments) : '';

    const colorOptions = noteColors.map(c =>
        `<div class="color-option ${c === currentColor ? 'selected' : ''}" style="background: ${c}" data-color="${c}" onclick="selectNoteColor('${c}')"></div>`
    ).join('');

    const html = `
        <form id="noteForm" onsubmit="submitNote(event, ${isEdit ? note.id : 'null'})" enctype="multipart/form-data">
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

            <div class="form-group">
                <label class="form-label">Lampiran File (Tarik & lepas banyak file sekaligus atau klik untuk memilih)</label>
                ${existingFilesHtml}
                <div class="file-upload-zone" id="noteDropZone">
                    <input type="file" id="noteFileInput" multiple>
                    <div class="file-upload-label">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                        <span>${isEdit && attachments.length > 0 ? '+ Tambah / Tarik file lampiran ke sini' : 'Tarik & Lepas beberapa file ke sini atau Klik untuk memilih'}</span>
                    </div>
                </div>
                <div class="file-selected-list" id="noteSelectedList" style="display: none;"></div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn-primary" id="noteSubmitBtn">${isEdit ? 'Simpan' : 'Tambah'}</button>
            </div>
        </form>
    `;
    openModal(title, html);
    setupDropZone('noteDropZone', 'noteFileInput', 'noteSelectedList');
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
    const submitBtn = document.getElementById('noteSubmitBtn');
    if (submitBtn) submitBtn.disabled = true;

    const formData = new FormData(form);

    // Append all queued files
    window.activeUploadFiles.forEach(file => {
        formData.append('files[]', file);
    });

    if (noteId) {
        formData.append('_method', 'PUT');
    }

    const url = noteId ? `/notes/${noteId}` : '/notes';

    try {
        const result = await apiFormRequest(url, formData);
        if (result.success) {
            closeModal();
            location.reload();
        } else if (result.errors) {
            alert(Object.values(result.errors).flat().join('\n'));
        }
    } catch (err) {
        alert('Gagal menyimpan catatan. Periksa ukuran file atau koneksi.');
    } finally {
        if (submitBtn) submitBtn.disabled = false;
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
    const attachments = event?.attachments || [];
    const existingFilesHtml = isEdit ? buildExistingAttachmentsHtml(attachments) : '';

    const html = `
        <form id="eventForm" onsubmit="submitEvent(event, ${isEdit ? event.id : 'null'})" enctype="multipart/form-data">
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

            <div class="form-group">
                <label class="form-label">Lampiran File (Tarik & lepas banyak file sekaligus atau klik untuk memilih)</label>
                ${existingFilesHtml}
                <div class="file-upload-zone" id="eventDropZone">
                    <input type="file" id="eventFileInput" multiple>
                    <div class="file-upload-label">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                        <span>${isEdit && attachments.length > 0 ? '+ Tambah / Tarik file lampiran ke sini' : 'Tarik & Lepas beberapa file ke sini atau Klik untuk memilih'}</span>
                    </div>
                </div>
                <div class="file-selected-list" id="eventSelectedList" style="display: none;"></div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn-primary" id="eventSubmitBtn">${isEdit ? 'Simpan' : 'Tambah'}</button>
            </div>
        </form>
    `;
    openModal(title, html);
    setupDropZone('eventDropZone', 'eventFileInput', 'eventSelectedList');
};

window.editEvent = function(id, event) {
    openEventModal(event);
};

window.submitEvent = async function(e, eventId) {
    e.preventDefault();
    const form = document.getElementById('eventForm');
    const submitBtn = document.getElementById('eventSubmitBtn');
    if (submitBtn) submitBtn.disabled = true;

    const formData = new FormData(form);

    // Append all queued files
    window.activeUploadFiles.forEach(file => {
        formData.append('files[]', file);
    });

    if (eventId) {
        formData.append('_method', 'PUT');
    }

    const url = eventId ? `/events/${eventId}` : '/events';

    try {
        const result = await apiFormRequest(url, formData);
        if (result.success) {
            closeModal();
            location.reload();
        } else if (result.errors) {
            alert(Object.values(result.errors).flat().join('\n'));
        }
    } catch (err) {
        alert('Gagal menyimpan acara. Periksa ukuran file atau koneksi.');
    } finally {
        if (submitBtn) submitBtn.disabled = false;
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

// ===== CALENDAR & HOVER TOOLTIP =====
let currentYear, currentMonth, calendarData = {};

function getOrCreateTooltip() {
    let tooltip = document.getElementById('calTooltip');
    if (!tooltip) {
        tooltip = document.createElement('div');
        tooltip.id = 'calTooltip';
        tooltip.className = 'cal-tooltip';
        document.body.appendChild(tooltip);
    }
    return tooltip;
}

function showCalTooltip(e, dateStr, day, hasTasks, hasEvents, hasNotes, hasFinances = false) {
    if (!hasTasks && !hasEvents && !hasNotes && !hasFinances) return;

    const tooltip = getOrCreateTooltip();
    const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    const tasks = calendarData.tasks?.[dateStr] || [];
    const events = calendarData.events?.[dateStr] || [];
    const notes = calendarData.notes?.[dateStr] || [];
    const finances = calendarData.finances?.[dateStr] || [];

    let html = `<div class="cal-tooltip-date">${day} ${monthNames[currentMonth]} ${currentYear}</div>`;
    html += '<div class="cal-tooltip-list">';

    if (tasks.length > 0) {
        tasks.forEach(t => {
            const pDot = t.priority === 'urgent' ? '🔴' : (t.priority === 'high' ? '🟠' : (t.priority === 'medium' ? '🟡' : '🟢'));
            const statusIcon = t.status === 'completed' ? '✓ ' : '';
            const attCount = t.attachments?.length || 0;
            const fileIcon = attCount > 0 ? ` 📎 (${attCount})` : '';
            html += `<div class="cal-tooltip-row">
                <span>${pDot}</span>
                <span class="tooltip-title ${t.status === 'completed' ? 'completed' : ''}">${statusIcon}${escapeHtml(t.title)}${fileIcon}</span>
                ${t.subject ? `<span class="tooltip-meta">${escapeHtml(t.subject)}</span>` : ''}
            </div>`;
        });
    }

    if (events.length > 0) {
        events.forEach(ev => {
            const catIcon = ev.category === 'kuliah' ? '📚' : (ev.category === 'ujian' ? '📝' : (ev.category === 'seminar' ? '🎤' : (ev.category === 'organisasi' ? '👥' : '🏠')));
            const attCount = ev.attachments?.length || 0;
            const fileIcon = attCount > 0 ? ` 📎 (${attCount})` : '';
            html += `<div class="cal-tooltip-row">
                <span>${catIcon}</span>
                <span class="tooltip-title">${escapeHtml(ev.title)}${fileIcon}</span>
                ${ev.location ? `<span class="tooltip-meta">${escapeHtml(ev.location)}</span>` : ''}
            </div>`;
        });
    }

    if (notes.length > 0) {
        notes.forEach(n => {
            const pinIcon = n.is_pinned ? '📌' : '🗒️';
            const attCount = n.attachments?.length || 0;
            const fileIcon = attCount > 0 ? ` 📎 (${attCount})` : '';
            html += `<div class="cal-tooltip-row">
                <span>${pinIcon}</span>
                <span class="tooltip-title">${escapeHtml(n.title)}${fileIcon}</span>
            </div>`;
        });
    }

    if (finances.length > 0) {
        finances.forEach(f => {
            const icon = f.icon || (f.type === 'income' ? '💵' : (f.type === 'installment' ? '💳' : '🎯'));
            const amtFormatted = f.amount ? ` - Rp ${new Intl.NumberFormat('id-ID').format(f.amount)}` : '';
            html += `<div class="cal-tooltip-row">
                <span>${icon}</span>
                <span class="tooltip-title" style="color: #fef08a;">${escapeHtml(f.title)}${amtFormatted}</span>
                <span class="tooltip-meta" style="color: ${f.color || '#eab308'}">${escapeHtml(f.badge || f.type)}</span>
            </div>`;
        });
    }

    html += '</div>';
    tooltip.innerHTML = html;
    positionCalTooltip(e);
    tooltip.classList.add('show');
}

function positionCalTooltip(e) {
    const tooltip = document.getElementById('calTooltip');
    if (!tooltip || !tooltip.classList.contains('show')) return;

    const xOffset = 16;
    const yOffset = 16;
    let x = e.clientX + xOffset;
    let y = e.clientY + yOffset;

    const tooltipRect = tooltip.getBoundingClientRect();
    if (x + tooltipRect.width > window.innerWidth - 10) {
        x = e.clientX - tooltipRect.width - xOffset;
    }
    if (y + tooltipRect.height > window.innerHeight - 10) {
        y = e.clientY - tooltipRect.height - yOffset;
    }

    tooltip.style.left = `${Math.max(10, x)}px`;
    tooltip.style.top = `${Math.max(10, y)}px`;
}

function hideCalTooltip() {
    const tooltip = document.getElementById('calTooltip');
    if (tooltip) {
        tooltip.classList.remove('show');
    }
}

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

    const monthTitle = document.getElementById('calMonthTitle');
    if (monthTitle) {
        monthTitle.textContent = `${monthNames[currentMonth]} ${currentYear}`;
    }

    try {
        const res = await fetch(`/calendar-data?year=${currentYear}&month=${currentMonth + 1}`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() }
        });
        calendarData = await res.json();
    } catch {
        calendarData = { tasks: {}, events: {}, notes: {}, finances: {} };
    }

    renderCalendar();
}

function renderCalendar() {
    const grid = document.getElementById('calendarGrid');
    if (!grid) return;
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

        const dayTasks = calendarData.tasks?.[dateStr] || [];
        const dayEvents = calendarData.events?.[dateStr] || [];
        const dayNotes = calendarData.notes?.[dateStr] || [];
        const dayFinances = calendarData.finances?.[dateStr] || [];

        const el = createCalDay(d, false, isToday, dateStr, dayTasks, dayEvents, dayNotes, dayFinances);
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

function createCalDay(day, isOtherMonth, isToday = false, dateStr = '', tasks = [], events = [], notes = [], finances = []) {
    const el = document.createElement('div');
    el.className = 'cal-day';
    if (isOtherMonth) el.classList.add('other-month');
    if (isToday) el.classList.add('today');

    const totalCount = (tasks?.length || 0) + (events?.length || 0) + (notes?.length || 0) + (finances?.length || 0);

    // Day Header (number & count badge)
    const header = document.createElement('div');
    header.className = 'cal-day-header';

    const numSpan = document.createElement('span');
    numSpan.className = 'cal-day-num';
    numSpan.textContent = day;
    header.appendChild(numSpan);

    if (totalCount > 0 && !isOtherMonth) {
        const badge = document.createElement('span');
        badge.className = 'cal-day-badge-count';
        badge.textContent = totalCount;
        header.appendChild(badge);
    }
    el.appendChild(header);

    // Render Event / Task / Note / Finance Chip Badges
    if (!isOtherMonth && totalCount > 0) {
        const eventsContainer = document.createElement('div');
        eventsContainer.className = 'cal-day-events';

        let itemsRendered = 0;
        const maxVisible = 2;

        // 1. Tasks
        if (tasks && tasks.length > 0) {
            tasks.forEach(t => {
                if (itemsRendered < maxVisible) {
                    const chip = document.createElement('div');
                    chip.className = `cal-event-chip chip-task priority-${t.priority} ${t.status === 'completed' ? 'is-completed' : ''}`;
                    chip.title = `Tugas: ${t.title} (${t.priority})`;
                    chip.innerHTML = `
                        <span class="chip-dot"></span>
                        <span class="chip-text">${escapeHtml(t.title)}</span>
                    `;
                    eventsContainer.appendChild(chip);
                    itemsRendered++;
                }
            });
        }

        // 2. Events
        if (events && events.length > 0) {
            events.forEach(ev => {
                if (itemsRendered < maxVisible) {
                    const chip = document.createElement('div');
                    chip.className = `cal-event-chip chip-event cat-${ev.category}`;
                    chip.title = `Acara: ${ev.title} (${ev.category})`;
                    chip.innerHTML = `
                        <span class="chip-dot"></span>
                        <span class="chip-text">${escapeHtml(ev.title)}</span>
                    `;
                    eventsContainer.appendChild(chip);
                    itemsRendered++;
                }
            });
        }

        // 3. Notes
        if (notes && notes.length > 0) {
            notes.forEach(n => {
                if (itemsRendered < maxVisible) {
                    const chip = document.createElement('div');
                    chip.className = 'cal-event-chip chip-note';
                    chip.title = `Catatan: ${n.title}`;
                    chip.innerHTML = `
                        <span class="chip-dot"></span>
                        <span class="chip-text">${escapeHtml(n.title)}</span>
                    `;
                    eventsContainer.appendChild(chip);
                    itemsRendered++;
                }
            });
        }

        // 4. Finances
        if (finances && finances.length > 0) {
            finances.forEach(f => {
                if (itemsRendered < maxVisible) {
                    const chip = document.createElement('div');
                    chip.className = `cal-event-chip chip-finance`;
                    chip.style.borderColor = 'rgba(234, 179, 8, 0.4)';
                    chip.style.background = 'rgba(234, 179, 8, 0.15)';
                    chip.style.color = '#fef08a';
                    chip.title = `${f.title} (${f.type})`;
                    chip.innerHTML = `
                        <span class="chip-dot" style="background:#eab308;"></span>
                        <span class="chip-text">${f.icon || '💰'} ${escapeHtml(f.title)}</span>
                    `;
                    eventsContainer.appendChild(chip);
                    itemsRendered++;
                }
            });
        }

        // 5. More chip if remaining
        if (totalCount > maxVisible) {
            const moreChip = document.createElement('div');
            moreChip.className = 'cal-event-chip chip-more';
            moreChip.textContent = `+${totalCount - maxVisible} lainnya`;
            eventsContainer.appendChild(moreChip);
        }

        el.appendChild(eventsContainer);
    }

    if (dateStr && !isOtherMonth) {
        el.addEventListener('click', () => {
            hideCalTooltip();
            openDayDetail(dateStr, day);
        });

        const hasTasks = tasks && tasks.length > 0;
        const hasEvents = events && events.length > 0;
        const hasNotes = notes && notes.length > 0;
        const hasFinances = finances && finances.length > 0;

        el.addEventListener('mouseenter', (e) => showCalTooltip(e, dateStr, day, hasTasks, hasEvents, hasNotes, hasFinances));
        el.addEventListener('mousemove', (e) => positionCalTooltip(e));
        el.addEventListener('mouseleave', () => hideCalTooltip());
    }

    return el;
}

function renderAttachmentsHtml(attachments = []) {
    if (!attachments || attachments.length === 0) return '';
    let pills = '';
    attachments.forEach(att => {
        pills += `
            <a href="${att.file_url}" target="_blank" class="attachment-pill type-${att.file_type || 'file'}" style="margin: 2px; padding: 2px 8px; font-size: 11px;">
                <span class="att-icon">${getFileIcon(att.file_type)}</span>
                <span class="att-name">${escapeHtml(att.file_name)}</span>
                <span class="att-size">(${att.formatted_file_size || ''})</span>
            </a>
        `;
    });
    return `<div class="attachments-wrap" style="margin-top: 4px;">${pills}</div>`;
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
    const finances = calendarData.finances?.[dateStr] || [];

    let html = '';

    if (tasks.length === 0 && events.length === 0 && notes.length === 0 && finances.length === 0) {
        html = `
            <div class="day-detail-empty">
                <p style="margin-bottom: 12px;">Tidak ada aktivitas di hari ini</p>
                <div style="display: flex; gap: 8px; justify-content: center; flex-wrap: wrap;">
                    <button class="btn-primary" style="padding: 6px 12px; font-size: 12px;" onclick="openTaskModalForDate('${dateStr}')">+ Tugas</button>
                    <button class="btn-secondary" style="padding: 6px 12px; font-size: 12px;" onclick="openEventModalForDate('${dateStr}')">+ Acara</button>
                    <button class="btn-secondary" style="padding: 6px 12px; font-size: 12px;" onclick="openNoteModalForDate('${dateStr}')">+ Catatan</button>
                </div>
            </div>
        `;
    } else {
        html += `
            <div style="display: flex; gap: 6px; margin-bottom: 16px; flex-wrap: wrap;">
                <button class="btn-primary" style="padding: 5px 10px; font-size: 11px;" onclick="openTaskModalForDate('${dateStr}')">+ Tugas</button>
                <button class="btn-secondary" style="padding: 5px 10px; font-size: 11px;" onclick="openEventModalForDate('${dateStr}')">+ Acara</button>
                <button class="btn-secondary" style="padding: 5px 10px; font-size: 11px;" onclick="openNoteModalForDate('${dateStr}')">+ Catatan</button>
            </div>
        `;

        if (tasks.length > 0) {
            html += '<div class="day-detail-section"><h4>📋 Tugas (' + tasks.length + ')</h4>';
            tasks.forEach(t => {
                const fileHtml = renderAttachmentsHtml(t.attachments);
                const taskJson = escapeHtml(JSON.stringify(t));

                html += `<div class="day-detail-item" style="border-left-color: var(--priority-${t.priority})" id="task-${t.id}">
                    <div class="day-detail-item-body">
                        <div class="item-title ${t.status === 'completed' ? 'completed' : ''}">${escapeHtml(t.title)}</div>
                        <div class="item-meta">${t.subject ? escapeHtml(t.subject) + ' · ' : ''}${t.priority} · ${t.status}</div>
                        ${fileHtml}
                    </div>
                    <div class="mini-actions" style="opacity: 1;">
                        <button class="action-btn-sm" onclick="toggleTaskStatus(${t.id})" title="Ubah Status">
                            ${t.status === 'completed' ? '✓' : '○'}
                        </button>
                        <button class="action-btn-sm" onclick='editTask(${t.id}, ${taskJson})' title="Edit Tugas">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                        </button>
                        <button class="action-btn-sm action-delete" onclick="deleteTask(${t.id})" title="Hapus Tugas">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            </svg>
                        </button>
                    </div>
                </div>`;
            });
            html += '</div>';
        }

        if (events.length > 0) {
            html += '<div class="day-detail-section"><h4>🗓️ Acara (' + events.length + ')</h4>';
            events.forEach(e => {
                const fileHtml = renderAttachmentsHtml(e.attachments);
                const eventJson = escapeHtml(JSON.stringify(e));

                html += `<div class="day-detail-item" style="border-left-color: var(--cat-${e.category})" id="event-${e.id}">
                    <div class="day-detail-item-body">
                        <div class="item-title">${escapeHtml(e.title)}</div>
                        <div class="item-meta">${e.category}${e.location ? ' · ' + escapeHtml(e.location) : ''}</div>
                        ${fileHtml}
                    </div>
                    <div class="mini-actions" style="opacity: 1;">
                        <button class="action-btn-sm" onclick='editEvent(${e.id}, ${eventJson})' title="Edit Acara">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                        </button>
                        <button class="action-btn-sm action-delete" onclick="deleteEvent(${e.id})" title="Hapus Acara">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            </svg>
                        </button>
                    </div>
                </div>`;
            });
            html += '</div>';
        }

        if (notes.length > 0) {
            html += '<div class="day-detail-section"><h4>📌 Catatan (' + notes.length + ')</h4>';
            notes.forEach(n => {
                const fileHtml = renderAttachmentsHtml(n.attachments);
                const noteJson = escapeHtml(JSON.stringify(n));

                html += `<div class="day-detail-item" style="border-left-color: ${n.color || '#6366f1'}" id="note-${n.id}">
                    <div class="day-detail-item-body">
                        <div class="item-title">${escapeHtml(n.title)}</div>
                        ${n.content ? `<div class="item-meta">${escapeHtml(n.content.substring(0, 80))}</div>` : ''}
                        ${fileHtml}
                    </div>
                    <div class="mini-actions" style="opacity: 1;">
                        <button class="action-btn-sm" onclick="togglePin(${n.id})" title="${n.is_pinned ? 'Unpin' : 'Pin'}">
                            ${n.is_pinned ? '★' : '☆'}
                        </button>
                        <button class="action-btn-sm" onclick='editNote(${n.id}, ${noteJson})' title="Edit Catatan">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                        </button>
                        <button class="action-btn-sm action-delete" onclick="deleteNote(${n.id})" title="Hapus Catatan">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            </svg>
                        </button>
                    </div>
                </div>`;
            });
            html += '</div>';
        }

        if (finances.length > 0) {
            html += '<div class="day-detail-section"><h4>💰 Agenda & Kewajiban Finansial (' + finances.length + ')</h4>';
            finances.forEach(f => {
                const icon = f.icon || '💰';
                const color = f.color || '#eab308';
                const amtFormatted = f.amount ? `Rp ${new Intl.NumberFormat('id-ID').format(f.amount)}` : '';
                html += `<div class="day-detail-item" style="border-left-color: ${color}">
                    <div class="day-detail-item-body">
                        <div class="item-title" style="color: #fff;">${icon} ${escapeHtml(f.title)}</div>
                        <div class="item-meta" style="color: var(--text-secondary);">
                            <strong style="color: ${color};">${amtFormatted}</strong> · <span style="text-transform: capitalize;">${escapeHtml(f.type)}</span>
                            ${f.badge ? `· <span class="badge badge-sm" style="background: rgba(255,255,255,0.06); padding: 2px 6px; border-radius: 4px;">${escapeHtml(f.badge)}</span>` : ''}
                        </div>
                        ${f.subtitle ? `<div style="font-size: 11px; color: var(--text-tertiary); margin-top: 2px;">${escapeHtml(f.subtitle)}</div>` : ''}
                    </div>
                </div>`;
            });
            html += '</div>';
        }
    }

    contentEl.innerHTML = html;
    document.querySelectorAll('.cal-day.selected').forEach(el => el.classList.remove('selected'));
    panel.classList.add('open');
}

window.openTaskModalForDate = function(dateStr) {
    openTaskModal({ deadline: `${dateStr}T23:59`, priority: 'medium', status: 'pending' });
};

window.openEventModalForDate = function(dateStr) {
    openEventModal({ start_date: `${dateStr}T09:00`, category: 'kuliah' });
};

window.openNoteModalForDate = function(dateStr) {
    openNoteModal({ note_date: dateStr });
};

// ===== PIN PAD =====
function initPinpad() {
    const numpad = document.getElementById('numpad');
    if (!numpad) return;

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

            dots.forEach((dot, i) => {
                dot.classList.toggle('filled', i < pin.length);
            });

            hiddenInput.value = pin;

            if (pin.length === maxLen) {
                setTimeout(() => form.submit(), 200);
            }
        });
    });
}

function initSetupPinpad() {
    let pin = '';
    let confirmPin = '';
    let activeField = 'pin';
    const maxLen = 6;
    const pinDots = document.querySelectorAll('#pinDotsSetup .pin-dot');
    const confirmDots = document.querySelectorAll('#pinDotsConfirm .pin-dot');
    const pinInput = document.getElementById('pinInput');
    const confirmInput = document.getElementById('pinConfirmInput');
    const submitBtn = document.getElementById('setupSubmitBtn');

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
window.escapeHtml = escapeHtml;

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

// ===== WEB PUSH & NOTIFICATIONS =====
let swRegistration = null;
let isPushSubscribed = false;

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

async function initPushNotifications() {
    const notifBtn = document.getElementById('notificationBtn');
    if (!notifBtn) return;

    notifBtn.addEventListener('click', openNotificationModal);

    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        return;
    }

    try {
        swRegistration = await navigator.serviceWorker.register('/sw.js');
        const subscription = await swRegistration.pushManager.getSubscription();
        isPushSubscribed = !(subscription === null);
        updateNotificationUI();
    } catch (err) {
        console.warn('Service worker registration failed:', err);
    }
}

function updateNotificationUI() {
    const btn = document.getElementById('notificationBtn');
    const indicator = document.getElementById('notifIndicator');
    if (!btn) return;

    if (isPushSubscribed) {
        btn.classList.add('active');
        if (indicator) indicator.style.display = 'block';
    } else {
        btn.classList.remove('active');
        if (indicator) indicator.style.display = 'none';
    }
}

window.openNotificationModal = async function() {
    const permission = ('Notification' in window) ? Notification.permission : 'unsupported';
    let subStatusText = isPushSubscribed ? '🟢 Notifikasi Aktif' : '⚪ Notifikasi Belum Aktif';
    let permissionText = permission === 'granted' ? 'Diizinkan' : (permission === 'denied' ? 'Diblokir oleh Browser' : 'Belum Diatur');

    const html = `
        <div style="text-align: center; padding: 8px 0 16px;">
            <div style="width: 56px; height: 56px; border-radius: 50%; background: var(--accent-soft); color: var(--accent); display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; font-size: 24px;">
                🔔
            </div>
            <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 6px;">Pengingat Deadline & Jadwal HP</h3>
            <p style="font-size: 13px; color: var(--text-tertiary); line-height: 1.6; max-width: 400px; margin: 0 auto;">
                Aktifkan notifikasi Web Push untuk menerima pemberitahuan otomatis ke HP saat ada tugas yang mendekati deadline atau jadwal kegiatan kuliah.
            </p>
        </div>

        <div style="background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px 16px; margin-bottom: 16px; font-size: 13px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                <span style="color: var(--text-secondary);">Status Perangkat Ini:</span>
                <span style="font-weight: 700;" id="modalNotifStatus">${subStatusText}</span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span style="color: var(--text-secondary);">Izin Browser:</span>
                <span style="font-weight: 600; color: ${permission === 'granted' ? '#4ade80' : '#f87171'};">${permissionText}</span>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px;">
            ${!isPushSubscribed ? `
                <button type="button" class="btn-primary btn-full" id="btnTogglePush" onclick="enablePushNotifications()">
                    🔔 Aktifkan Notifikasi di Perangkat Ini
                </button>
            ` : `
                <button type="button" class="btn-secondary btn-full" id="btnTogglePush" onclick="disablePushNotifications()">
                    🔕 Nonaktifkan Notifikasi di Perangkat Ini
                </button>
            `}
            <button type="button" class="btn-primary btn-full" style="background: linear-gradient(135deg, #e11d48, #be123c); border: none;" onclick="sendTestScheduleNotification()">
                📚 Tes Pengingat Jadwal Kuliah Besok (Multi-Matkul)
            </button>
            <button type="button" class="btn-secondary btn-full" onclick="sendTestPushNotification()">
                ⚡ Kirim Tes Notifikasi Standar
            </button>
        </div>

        <div style="border-top: 1px solid var(--border-color); padding-top: 14px; font-size: 12px; color: var(--text-tertiary); line-height: 1.5;">
            <div style="font-weight: 600; color: var(--text-secondary); margin-bottom: 4px;">💡 Tips untuk HP (Android / iOS):</div>
            <div>• <strong>Android</strong>: Buka di Chrome → Klik titik tiga ⋮ → "Tambahkan ke Layar Utama" / "Install App".</div>
            <div>• <strong>iPhone / iOS</strong>: Buka di Safari → Klik tombol Share 📤 → "Add to Home Screen" → Buka aplikasi dari layar utama lalu aktifkan notifikasi.</div>
        </div>

        <div class="form-actions" style="margin-top: 16px;">
            <button type="button" class="btn-secondary" onclick="closeModal()">Tutup</button>
        </div>
    `;

    openModal('Pengaturan Notifikasi', html);
};

window.enablePushNotifications = async function() {
    const btn = document.getElementById('btnTogglePush');
    if (btn) btn.disabled = true;

    try {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
            alert('Browser ini tidak mendukung Web Push Notifications.');
            return;
        }

        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
            alert('Izin notifikasi tidak diberikan. Silakan izinkan notifikasi di pengaturan browser kamu.');
            return;
        }

        const res = await fetch('/push/vapid-public-key');
        const data = await res.json();
        const publicKey = data.publicKey;

        if (!publicKey) {
            alert('VAPID Public Key belum disiapkan di server.');
            return;
        }

        const reg = await navigator.serviceWorker.ready;
        const convertedKey = urlBase64ToUint8Array(publicKey);

        const subscription = await reg.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: convertedKey,
        });

        const subJson = subscription.toJSON();

        const saveRes = await apiRequest('/push/subscribe', 'POST', {
            endpoint: subscription.endpoint,
            keys: subJson.keys,
            contentEncoding: (PushManager.supportedContentEncodings || ['aesgcm'])[0],
        });

        if (saveRes.success) {
            isPushSubscribed = true;
            updateNotificationUI();
            alert('🎉 Notifikasi berhasil diaktifkan untuk perangkat ini!');
            closeModal();
        }
    } catch (err) {
        console.error('Error enabling push:', err);
        alert('Gagal mengaktifkan notifikasi: ' + err.message);
    } finally {
        if (btn) btn.disabled = false;
    }
};

window.disablePushNotifications = async function() {
    if (!swRegistration) return;

    try {
        const subscription = await swRegistration.pushManager.getSubscription();
        if (subscription) {
            await apiRequest('/push/unsubscribe', 'POST', {
                endpoint: subscription.endpoint,
            });
            await subscription.unsubscribe();
        }

        isPushSubscribed = false;
        updateNotificationUI();
        alert('Notifikasi push telah dinonaktifkan untuk perangkat ini.');
        closeModal();
    } catch (err) {
        console.error('Error unsubscribing:', err);
    }
};

window.sendTestPushNotification = async function() {
    // Trigger local immediate notification if permission granted
    if ('Notification' in window && Notification.permission === 'granted') {
        try {
            if (swRegistration && swRegistration.showNotification) {
                swRegistration.showNotification('🔔 Tes Notifikasi Schedule Berhasil!', {
                    body: 'Notifikasi push di perangkat kamu sudah aktif dan siap mengirimkan pengingat deadline!',
                    icon: '/favicon.ico',
                    badge: '/favicon.ico',
                    vibrate: [200, 100, 200],
                });
            } else {
                new Notification('🔔 Tes Notifikasi Schedule Berhasil!', {
                    body: 'Notifikasi push di perangkat kamu sudah aktif dan siap mengirimkan pengingat deadline!',
                    icon: '/favicon.ico',
                });
            }
        } catch (e) {
            console.log('Direct notification fallback:', e);
        }
    }

    try {
        const res = await apiRequest('/push/test', 'POST');
        if (res && res.message) {
            alert(res.message);
        } else {
            alert('Tes notifikasi selesai diproses!');
        }
    } catch (err) {
        console.error('Push test error:', err);
        alert('Catatan: Web Push di HP membutuhkan koneksi aman (HTTPS). Di server Linux dengan domain HTTPS nanti, notifikasi ini akan bekerja otomatis.');
    }
};

window.sendTestScheduleNotification = async function(dayOfWeek = null) {
    try {
        const payload = dayOfWeek ? { day_of_week: dayOfWeek } : {};
        const res = await apiRequest('/push/test-schedule', 'POST', payload);

        if (res.success && res.title && res.body) {
            // Trigger local immediate notification if permission granted
            if ('Notification' in window && Notification.permission === 'granted') {
                try {
                    if (swRegistration && swRegistration.showNotification) {
                        swRegistration.showNotification(res.title, {
                            body: res.body,
                            icon: '/favicon.ico',
                            badge: '/favicon.ico',
                            vibrate: [200, 100, 200],
                            data: { url: '/schedules' }
                        });
                    } else {
                        new Notification(res.title, {
                            body: res.body,
                            icon: '/favicon.ico',
                        });
                    }
                } catch (e) {
                    console.log('Direct notification fallback:', e);
                }
            }

            const previewText = `${res.title}\n\n${res.body}`;
            alert(`✅ Pengingat Jadwal Kuliah Terkirim:\n\n${previewText}\n\n${res.message}`);
        } else {
            alert(res.message || 'Gagal mengirim notifikasi jadwal.');
        }
    } catch (err) {
        console.error('Schedule push test error:', err);
        alert('Gagal mengirim tes notifikasi jadwal: ' + err.message);
    }
};

// ==========================================
// COURSE SCHEDULE CRUD & TIMETABLE MATRIX JS
// ==========================================

window.setScheduleTimePreset = function(start, end) {
    const startInput = document.getElementById('scheduleStartTime');
    const endInput = document.getElementById('scheduleEndTime');
    if (startInput) startInput.value = start;
    if (endInput) endInput.value = end;
};

window.selectScheduleColor = function(color) {
    const input = document.getElementById('scheduleColorTag');
    if (input) input.value = color;

    document.querySelectorAll('.color-option').forEach(el => {
        if (el.dataset.color === color) {
            el.classList.add('selected');
        } else {
            el.classList.remove('selected');
        }
    });
};

window.openScheduleModal = function(schedule = null) {
    const isEdit = !!schedule;
    const title = isEdit ? 'Edit Mata Kuliah' : 'Tambah Mata Kuliah Baru';
    const attachments = schedule?.attachments || [];
    const existingFilesHtml = isEdit ? buildExistingAttachmentsHtml(attachments) : '';

    const selectedColor = schedule?.color_tag || 'red';
    const startTimeVal = schedule?.start_time ? schedule.start_time.substring(0, 5) : '07:30';
    const endTimeVal = schedule?.end_time ? schedule.end_time.substring(0, 5) : '10:30';
    const currentDay = schedule?.day_of_week || 1;

    const colors = [
        { key: 'red', hex: '#ef4444' },
        { key: 'indigo', hex: '#6366f1' },
        { key: 'blue', hex: '#3b82f6' },
        { key: 'emerald', hex: '#10b981' },
        { key: 'amber', hex: '#f59e0b' },
        { key: 'purple', hex: '#a855f7' },
        { key: 'rose', hex: '#f43f5e' },
        { key: 'cyan', hex: '#06b6d4' },
    ];

    const colorPickerHtml = colors.map(c => `
        <div class="color-option ${c.key === selectedColor ? 'selected' : ''}" 
             data-color="${c.key}" 
             style="background: ${c.hex};" 
             onclick="selectScheduleColor('${c.key}')" 
             title="Warna ${c.key}"></div>
    `).join('');

    const html = `
        <form id="scheduleForm" onsubmit="submitSchedule(event, ${isEdit ? schedule.id : 'null'})" enctype="multipart/form-data">
            <div class="form-group">
                <label class="form-label" for="scheduleCourseName">Nama Mata Kuliah *</label>
                <input type="text" class="form-input" id="scheduleCourseName" name="course_name" value="${isEdit ? escapeHtml(schedule.course_name) : ''}" required placeholder="Contoh: Rekayasa Perangkat Lunak">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="scheduleCourseCode">Kode Matkul</label>
                    <input type="text" class="form-input" id="scheduleCourseCode" name="course_code" value="${isEdit && schedule.course_code ? escapeHtml(schedule.course_code) : ''}" placeholder="Contoh: ISG2A3">
                </div>
                <div class="form-group">
                    <label class="form-label" for="scheduleClassCode">Kode Kelas</label>
                    <input type="text" class="form-input" id="scheduleClassCode" name="class_code" value="${isEdit && schedule.class_code ? escapeHtml(schedule.class_code) : ''}" placeholder="Contoh: SI-47-01">
                </div>
                <div class="form-group">
                    <label class="form-label" for="scheduleSks">SKS *</label>
                    <select class="form-select" id="scheduleSks" name="sks" required>
                        <option value="1" ${isEdit && schedule.sks === 1 ? 'selected' : ''}>1 SKS</option>
                        <option value="2" ${isEdit && schedule.sks === 2 ? 'selected' : ''}>2 SKS</option>
                        <option value="3" ${!isEdit || schedule.sks === 3 ? 'selected' : ''}>3 SKS</option>
                        <option value="4" ${isEdit && schedule.sks === 4 ? 'selected' : ''}>4 SKS</option>
                        <option value="6" ${isEdit && schedule.sks === 6 ? 'selected' : ''}>6 SKS (Tugas Akhir)</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="scheduleLecturer">Dosen Pengampu & Kode Dosen</label>
                <input type="text" class="form-input" id="scheduleLecturer" name="lecturer_name" value="${isEdit && schedule.lecturer_name ? escapeHtml(schedule.lecturer_name) : ''}" placeholder="Contoh: Dr. Ir. Budi Santoso, M.Kom / BDS">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="scheduleDayOfWeek">Hari Perkuliahan *</label>
                    <select class="form-select" id="scheduleDayOfWeek" name="day_of_week" required>
                        <option value="1" ${currentDay === 1 ? 'selected' : ''}>Senin</option>
                        <option value="2" ${currentDay === 2 ? 'selected' : ''}>Selasa</option>
                        <option value="3" ${currentDay === 3 ? 'selected' : ''}>Rabu</option>
                        <option value="4" ${currentDay === 4 ? 'selected' : ''}>Kamis</option>
                        <option value="5" ${currentDay === 5 ? 'selected' : ''}>Jumat</option>
                        <option value="6" ${currentDay === 6 ? 'selected' : ''}>Sabtu</option>
                        <option value="7" ${currentDay === 7 ? 'selected' : ''}>Minggu</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="scheduleDeliveryMode">Mode Kuliah *</label>
                    <select class="form-select" id="scheduleDeliveryMode" name="delivery_mode" required>
                        <option value="offline" ${!isEdit || schedule.delivery_mode === 'offline' ? 'selected' : ''}>🏛️ Tatap Muka (Offline)</option>
                        <option value="online" ${isEdit && schedule.delivery_mode === 'online' ? 'selected' : ''}>🌐 Daring (Online / LMS)</option>
                        <option value="hybrid" ${isEdit && schedule.delivery_mode === 'hybrid' ? 'selected' : ''}>🔀 Hybrid</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="scheduleStartTime">Jam Mulai *</label>
                    <input type="time" class="form-input" id="scheduleStartTime" name="start_time" value="${startTimeVal}" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="scheduleEndTime">Jam Selesai *</label>
                    <input type="time" class="form-input" id="scheduleEndTime" name="end_time" value="${endTimeVal}" required>
                </div>
            </div>

            {{-- Quick Presets for Telkom Univ Schedule Slots --}}
            <div class="form-group">
                <label class="form-label" style="font-size: 11px; color: var(--text-secondary);">⚡ Preset Jam Kuliah Telkom University:</label>
                <div class="preset-time-pills">
                    <button type="button" class="time-preset-btn" onclick="setScheduleTimePreset('06:30', '09:30')">06:30 - 09:30 (3 SKS)</button>
                    <button type="button" class="time-preset-btn" onclick="setScheduleTimePreset('07:30', '10:30')">07:30 - 10:30 (3 SKS)</button>
                    <button type="button" class="time-preset-btn" onclick="setScheduleTimePreset('09:30', '12:30')">09:30 - 12:30 (3 SKS)</button>
                    <button type="button" class="time-preset-btn" onclick="setScheduleTimePreset('13:30', '16:30')">13:30 - 16:30 (3 SKS)</button>
                    <button type="button" class="time-preset-btn" onclick="setScheduleTimePreset('15:30', '18:30')">15:30 - 18:30 (3 SKS)</button>
                    <button type="button" class="time-preset-btn" onclick="setScheduleTimePreset('07:30', '09:30')">07:30 - 09:30 (2 SKS)</button>
                    <button type="button" class="time-preset-btn" onclick="setScheduleTimePreset('13:30', '15:30')">13:30 - 15:30 (2 SKS)</button>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="scheduleRoom">Ruangan</label>
                    <input type="text" class="form-input" id="scheduleRoom" name="room" value="${isEdit && schedule.room ? escapeHtml(schedule.room) : ''}" placeholder="Contoh: KU3.02.04 / TULT-0801">
                </div>
                <div class="form-group">
                    <label class="form-label" for="scheduleMeetingLink">Link Kuliah Online (Zoom / Meet / CeLOE)</label>
                    <input type="url" class="form-input" id="scheduleMeetingLink" name="meeting_link" value="${isEdit && schedule.meeting_link ? escapeHtml(schedule.meeting_link) : ''}" placeholder="https://zoom.us/j/...">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Warna Aksen Jadwal</label>
                <input type="hidden" name="color_tag" id="scheduleColorTag" value="${selectedColor}">
                <div class="color-picker-group">
                    ${colorPickerHtml}
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="scheduleNotes">Catatan Tambahan</label>
                <textarea class="form-textarea" id="scheduleNotes" name="notes" placeholder="Catatan aturan kelas, toleransi keterlambatan, software yang dibutuhkan...">${isEdit && schedule.notes ? escapeHtml(schedule.notes) : ''}</textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Lampiran RPS / Silabus / Kontrak Kuliah</label>
                ${existingFilesHtml}
                <div class="file-upload-zone" id="scheduleDropZone">
                    <input type="file" id="scheduleFileInput" multiple>
                    <div class="file-upload-label">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                        <span>${isEdit && attachments.length > 0 ? '+ Tambah / Tarik file silabus baru ke sini' : 'Tarik & Lepas file RPS/Silabus (PDF, DOCX) atau Klik untuk memilih'}</span>
                    </div>
                </div>
                <div class="file-selected-list" id="scheduleSelectedList" style="display: none;"></div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn-primary" id="scheduleSubmitBtn">${isEdit ? 'Simpan Perubahan' : 'Tambahkan Jadwal'}</button>
            </div>
        </form>
    `;
    openModal(title, html);
    setupDropZone('scheduleDropZone', 'scheduleFileInput', 'scheduleSelectedList');
};

window.editSchedule = function(id, schedule) {
    openScheduleModal(schedule);
};

window.submitSchedule = async function(e, scheduleId) {
    e.preventDefault();
    const form = document.getElementById('scheduleForm');
    const submitBtn = document.getElementById('scheduleSubmitBtn');
    if (submitBtn) submitBtn.disabled = true;

    const formData = new FormData(form);

    // Append queued files
    window.activeUploadFiles.forEach(file => {
        formData.append('files[]', file);
    });

    if (scheduleId) {
        formData.append('_method', 'PUT');
    }

    const url = scheduleId ? `/schedules/${scheduleId}` : '/schedules';

    try {
        const result = await apiFormRequest(url, formData);
        if (result.success) {
            closeModal();
            location.reload();
        } else if (result.errors) {
            alert(Object.values(result.errors).flat().join('\n'));
        }
    } catch (err) {
        alert('Gagal menyimpan jadwal perkuliahan. Periksa format waktu dan koneksi.');
    } finally {
        if (submitBtn) submitBtn.disabled = false;
    }
};

window.deleteSchedule = async function(scheduleId) {
    if (!confirm('Yakin ingin menghapus mata kuliah ini dari jadwal?')) return;
    const result = await apiRequest(`/schedules/${scheduleId}`, 'DELETE');
    if (result.success) {
        location.reload();
    }
};

// Toast notification helper
window.showToast = function(message, type = 'info') {
    const existing = document.getElementById('appToast');
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.id = 'appToast';
    toast.className = `app-toast toast-${type}`;
    toast.textContent = message;
    toast.style.cssText = `
        position: fixed;
        bottom: 24px;
        left: 50%;
        transform: translateX(-50%) translateY(20px);
        background: #111118;
        color: #fff;
        padding: 12px 24px;
        border-radius: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        border: 1px solid ${type === 'success' ? '#22c55e' : (type === 'error' ? '#ef4444' : '#6366f1')};
        font-size: 14px;
        font-weight: 600;
        z-index: 99999;
        opacity: 0;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        pointer-events: none;
    `;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateX(-50%) translateY(0)';
    }, 10);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(-50%) translateY(20px)';
        setTimeout(() => toast.remove(), 300);
    }, 3500);
};

// ==========================================
// V3 — AI ASSISTANT CLIENT-SIDE SUITE
// Voice Input, Natural Language, OCR, Breakdown
// ==========================================

let activeSpeechRecognition = null;
let currentAiBreakdownData = null;

// Modal Control
window.openAiModal = function(tab = 'text', initialText = '') {
    const overlay = document.getElementById('aiModalOverlay');
    if (!overlay) return;

    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';

    switchAiTab(tab);

    if (initialText) {
        if (tab === 'breakdown') {
            const goalInput = document.getElementById('aiBreakdownGoalInput');
            if (goalInput) {
                goalInput.value = initialText;
                generateTaskBreakdown();
            }
        } else {
            const promptInput = document.getElementById('aiPromptInput');
            if (promptInput) promptInput.value = initialText;
        }
    }
};

window.closeAiModal = function() {
    const overlay = document.getElementById('aiModalOverlay');
    if (overlay) {
        overlay.classList.remove('open');
        document.body.style.overflow = '';
    }
    if (activeSpeechRecognition) {
        activeSpeechRecognition.stop();
        activeSpeechRecognition = null;
    }
};

// Close on escape
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        const aiOverlay = document.getElementById('aiModalOverlay');
        if (aiOverlay && aiOverlay.classList.contains('open')) {
            closeAiModal();
        }
    }
});

// Close when clicking overlay backdrop
document.getElementById('aiModalOverlay')?.addEventListener('click', (e) => {
    if (e.target.id === 'aiModalOverlay') {
        closeAiModal();
    }
});

// Switch Tab
window.switchAiTab = function(tabName) {
    const tabs = ['text', 'ocr', 'breakdown'];
    tabs.forEach(t => {
        const btn = document.getElementById(`aiTabBtn${t.charAt(0).toUpperCase() + t.slice(1)}`);
        const content = document.getElementById(`aiTabContent${t.charAt(0).toUpperCase() + t.slice(1)}`);
        if (btn) btn.classList.toggle('active', t === tabName);
        if (content) {
            content.style.display = t === tabName ? 'block' : 'none';
            content.classList.toggle('active', t === tabName);
        }
    });
};

// Voice Input (Web Speech API with id-ID)
window.toggleVoiceInput = function() {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRecognition) {
        showToast('Browser kamu belum mendukung Web Speech API. Gunakan Google Chrome atau Microsoft Edge.', 'info');
        return;
    }

    const voiceBtn = document.getElementById('aiVoiceBtn');
    const micLabel = document.getElementById('aiMicLabel');
    const textarea = document.getElementById('aiPromptInput');

    if (activeSpeechRecognition) {
        activeSpeechRecognition.stop();
        activeSpeechRecognition = null;
        voiceBtn?.classList.remove('listening');
        if (micLabel) micLabel.textContent = 'Bicara';
        return;
    }

    try {
        const recognition = new SpeechRecognition();
        recognition.lang = 'id-ID';
        recognition.continuous = false;
        recognition.interimResults = true;

        recognition.onstart = function() {
            activeSpeechRecognition = recognition;
            voiceBtn?.classList.add('listening');
            if (micLabel) micLabel.textContent = 'Mendengarkan...';
            showToast('Mendengarkan suara kamu dalam Bahasa Indonesia... Silakan bicara.', 'info');
        };

        recognition.onresult = function(event) {
            let transcript = '';
            for (let i = event.resultIndex; i < event.results.length; ++i) {
                transcript += event.results[i][0].transcript;
            }
            if (textarea && transcript.trim()) {
                textarea.value = transcript;
            }
        };

        recognition.onerror = function(event) {
            console.warn('Speech recognition error:', event.error);
            voiceBtn?.classList.remove('listening');
            if (micLabel) micLabel.textContent = 'Bicara';
            activeSpeechRecognition = null;
        };

        recognition.onend = function() {
            voiceBtn?.classList.remove('listening');
            if (micLabel) micLabel.textContent = 'Bicara';
            activeSpeechRecognition = null;
            if (textarea && textarea.value.trim().length > 5) {
                showToast('Suara berhasil dikenali! Klik "Proses dengan AI" untuk melihat draf.', 'success');
            }
        };

        recognition.start();
    } catch (err) {
        console.error('Speech recognition exception:', err);
        showToast('Gagal memulai mikrofon. Pastikan izin mikrofon telah diberikan.', 'error');
    }
};

window.applyAiExample = function(text) {
    const textarea = document.getElementById('aiPromptInput');
    if (textarea) {
        textarea.value = text;
        textarea.focus();
    }
};

// Process Natural Language Text
window.processAiText = async function() {
    const textarea = document.getElementById('aiPromptInput');
    const promptText = textarea ? textarea.value.trim() : '';

    if (!promptText) {
        showToast('Ketik atau ucapkan teks jadwal terlebih dahulu', 'error');
        return;
    }

    const btn = document.getElementById('btnProcessAiText');
    const originalHtml = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-sm"></span> Menganalisis dengan AI...';
    }

    try {
        const res = await apiRequest('/ai/parse', 'POST', { text: promptText });
        if (res.success && res.draft) {
            populateAiDraft(res.draft);
            showToast('Draf berhasil dibuat! Silakan tinjau sebelum disimpan.', 'success');
        } else {
            showToast('Gagal memproses input AI.', 'error');
        }
    } catch (err) {
        console.error('AI parse error:', err);
        showToast('Terjadi kesalahan saat memproses input AI.', 'error');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    }
};

// Populate Draft Card Form
function populateAiDraft(draft) {
    const container = document.getElementById('aiDraftContainer');
    if (!container) return;

    document.getElementById('aiDraftType').value = draft.type || 'task';
    document.getElementById('aiDraftTypeBadge').textContent = draft.type === 'event' ? '📅 Acara' : '📝 Tugas Kuliah';
    document.getElementById('aiDraftTitle').value = draft.title || '';

    const subjectGroup = document.getElementById('aiDraftSubjectGroup');
    const taskExtras = document.getElementById('aiDraftTaskExtras');
    const eventExtras = document.getElementById('aiDraftEventExtras');
    const dateLabel = document.getElementById('aiDraftDateLabel');

    if (draft.type === 'event') {
        if (subjectGroup) subjectGroup.style.display = 'none';
        if (taskExtras) taskExtras.style.display = 'none';
        if (eventExtras) eventExtras.style.display = 'grid';
        if (dateLabel) dateLabel.textContent = 'Waktu Acara';
        document.getElementById('aiDraftLocation').value = draft.location || '';
        if (draft.category) document.getElementById('aiDraftCategory').value = draft.category;
    } else {
        if (subjectGroup) subjectGroup.style.display = 'block';
        if (taskExtras) taskExtras.style.display = 'grid';
        if (eventExtras) eventExtras.style.display = 'none';
        if (dateLabel) dateLabel.textContent = 'Deadline Tugas';
        document.getElementById('aiDraftSubject').value = draft.subject || '';
        document.getElementById('aiDraftDuration').value = draft.estimated_duration || 60;
        if (draft.priority) document.getElementById('aiDraftPriority').value = draft.priority;
    }

    // Format deadline to datetime-local (YYYY-MM-DDTHH:MM)
    if (draft.deadline) {
        const dt = new Date(draft.deadline.replace(' ', 'T'));
        if (!isNaN(dt.getTime())) {
            const pad = (n) => String(n).padStart(2, '0');
            const localIso = `${dt.getFullYear()}-${pad(dt.getMonth() + 1)}-${pad(dt.getDate())}T${pad(dt.getHours())}:${pad(dt.getMinutes())}`;
            document.getElementById('aiDraftDeadline').value = localIso;
        }
    }

    // Render Subtasks
    renderAiDraftSubtasks(draft.subtasks || []);

    container.style.display = 'block';
    container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function renderAiDraftSubtasks(subtasks) {
    const listEl = document.getElementById('aiDraftSubtasksList');
    if (!listEl) return;
    listEl.innerHTML = '';

    subtasks.forEach(st => {
        const title = typeof st === 'object' ? (st.title || '') : String(st);
        addAiDraftSubtask(title);
    });
}

window.addAiDraftSubtask = function(title = '') {
    const listEl = document.getElementById('aiDraftSubtasksList');
    if (!listEl) return;

    const row = document.createElement('div');
    row.className = 'ai-subtask-row';
    row.innerHTML = `
        <span class="subtask-dot">•</span>
        <input type="text" class="form-input ai-subtask-input" value="${escapeHtml(title)}" placeholder="Nama langkah subtask...">
        <button type="button" class="btn-remove-subtask" onclick="this.closest('.ai-subtask-row').remove()" title="Hapus">&times;</button>
    `;
    listEl.appendChild(row);
};

window.resetAiDraft = function() {
    const container = document.getElementById('aiDraftContainer');
    if (container) container.style.display = 'none';
};

// Confirm & Save Draft (Rule 9 compliance)
window.confirmAiDraft = async function(e) {
    e.preventDefault();

    const type = document.getElementById('aiDraftType').value;
    const title = document.getElementById('aiDraftTitle').value.trim();
    const deadline = document.getElementById('aiDraftDeadline').value;

    if (!title) {
        showToast('Judul tidak boleh kosong', 'error');
        return;
    }

    const payload = {
        type,
        title,
        deadline,
    };

    if (type === 'event') {
        payload.start_date = deadline;
        payload.location = document.getElementById('aiDraftLocation').value.trim() || null;
        payload.category = document.getElementById('aiDraftCategory').value;
    } else {
        payload.subject = document.getElementById('aiDraftSubject').value.trim() || null;
        payload.priority = document.getElementById('aiDraftPriority').value;
        payload.estimated_duration = parseInt(document.getElementById('aiDraftDuration').value, 10) || 60;

        // Gather subtasks
        const subtaskInputs = document.querySelectorAll('.ai-subtask-input');
        const subtasks = [];
        subtaskInputs.forEach(input => {
            const val = input.value.trim();
            if (val) {
                subtasks.push({ title: val, completed: false });
            }
        });
        payload.subtasks = subtasks;
    }

    const btn = document.getElementById('btnConfirmDraft');
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Menyimpan...';
    }

    try {
        const res = await apiRequest('/ai/confirm-draft', 'POST', payload);
        if (res.success) {
            showToast(res.message || 'Berhasil disimpan!', 'success');
            closeAiModal();
            setTimeout(() => location.reload(), 600);
        } else {
            showToast(res.message || 'Gagal menyimpan draf.', 'error');
        }
    } catch (err) {
        console.error('Confirm draft error:', err);
        showToast('Gagal menyimpan draf.', 'error');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.textContent = '✅ Simpan ke Jadwal';
        }
    }
};

// OCR Screenshot Processing
window.handleOcrImageUpload = async function(files) {
    if (!files || files.length === 0) return;
    const file = files[0];

    const progressWrap = document.getElementById('aiOcrProgressWrap');
    const progressFill = document.getElementById('aiOcrProgressFill');
    const progressStatus = document.getElementById('aiOcrProgressStatus');
    const resultsWrap = document.getElementById('aiOcrResultsWrap');

    if (resultsWrap) resultsWrap.style.display = 'none';
    if (progressWrap) progressWrap.style.display = 'block';

    try {
        if (progressFill) progressFill.style.width = '20%';
        if (progressStatus) progressStatus.textContent = 'Memuat OCR Engine...';

        let extractedText = '';
        if (typeof Tesseract !== 'undefined') {
            const worker = await Tesseract.createWorker(['ind', 'eng']);
            if (progressFill) progressFill.style.width = '60%';
            if (progressStatus) progressStatus.textContent = 'Mengenali teks pada screenshot...';

            const ret = await worker.recognize(file);
            await worker.terminate();
            extractedText = ret.data.text || '';
        } else {
            throw new Error('Tesseract library not loaded');
        }

        if (progressFill) progressFill.style.width = '90%';
        if (progressStatus) progressStatus.textContent = 'Menganalisis tugas dengan AI...';

        const res = await apiRequest('/ai/parse-ocr', 'POST', { text: extractedText });

        if (progressFill) progressFill.style.width = '100%';
        setTimeout(() => {
            if (progressWrap) progressWrap.style.display = 'none';
        }, 400);

        if (res.success && res.drafts && res.drafts.length > 0) {
            renderOcrResults(res.drafts);
            showToast(`Berhasil menemukan ${res.drafts.length} tugas dari gambar!`, 'success');
        } else {
            showToast('Tidak ada tugas terstruktur yang terdeteksi dari gambar ini.', 'info');
        }
    } catch (err) {
        console.error('OCR Error:', err);
        if (progressWrap) progressWrap.style.display = 'none';
        showToast('Gagal memindai gambar OCR. Pastikan format gambar valid.', 'error');
    }
};

function renderOcrResults(drafts) {
    const wrap = document.getElementById('aiOcrResultsWrap');
    const countEl = document.getElementById('aiOcrFoundCount');
    const listEl = document.getElementById('aiOcrTasksList');

    if (!wrap || !listEl) return;
    listEl.innerHTML = '';
    if (countEl) countEl.textContent = `Ditemukan ${drafts.length} Tugas dari Gambar`;

    window.activeOcrDrafts = drafts;

    drafts.forEach((d, idx) => {
        const item = document.createElement('div');
        item.className = 'ai-ocr-task-item';
        item.innerHTML = `
            <div style="display: flex; align-items: flex-start; gap: 10px;">
                <input type="checkbox" id="ocr_task_chk_${idx}" class="ocr-task-chk" checked data-idx="${idx}">
                <div style="flex: 1;">
                    <strong style="color: #fff; font-size: 14px;">${escapeHtml(d.title)}</strong>
                    <div style="font-size: 11px; color: var(--text-secondary); margin-top: 4px; display: flex; gap: 8px; flex-wrap: wrap;">
                        <span>📅 ${d.deadline ? d.deadline.slice(0, 16) : 'Besok'}</span>
                        <span>⏱️ ${d.estimated_duration || 60} menit</span>
                        ${d.subject ? `<span>📚 ${escapeHtml(d.subject)}</span>` : ''}
                    </div>
                </div>
            </div>
        `;
        listEl.appendChild(item);
    });

    wrap.style.display = 'block';
}

window.saveAllSelectedOcrTasks = async function() {
    const checkboxes = document.querySelectorAll('.ocr-task-chk:checked');
    if (checkboxes.length === 0) {
        showToast('Pilih minimal satu tugas untuk disimpan', 'error');
        return;
    }

    const drafts = window.activeOcrDrafts || [];
    let savedCount = 0;

    showToast(`Menyimpan ${checkboxes.length} tugas...`, 'info');

    for (const chk of checkboxes) {
        const idx = parseInt(chk.getAttribute('data-idx'), 10);
        const d = drafts[idx];
        if (d) {
            try {
                await apiRequest('/ai/confirm-draft', 'POST', {
                    type: d.type || 'task',
                    title: d.title,
                    subject: d.subject || null,
                    deadline: d.deadline || null,
                    priority: d.priority || 'medium',
                    estimated_duration: d.estimated_duration || 60,
                    subtasks: d.subtasks || [],
                });
                savedCount++;
            } catch (e) {
                console.error('Error saving OCR task:', e);
            }
        }
    }

    showToast(`${savedCount} tugas berhasil ditambahkan ke jadwal!`, 'success');
    closeAiModal();
    setTimeout(() => location.reload(), 600);
};

// Task Breakdown
window.generateTaskBreakdown = async function() {
    const input = document.getElementById('aiBreakdownGoalInput');
    const goal = input ? input.value.trim() : '';

    if (!goal) {
        showToast('Ketik nama tugas atau proyek terlebih dahulu', 'error');
        return;
    }

    try {
        const res = await apiRequest('/ai/breakdown', 'POST', { title: goal });
        if (res.success && res.breakdown) {
            currentAiBreakdownData = res.breakdown;
            renderBreakdownResult(res.breakdown);
        } else {
            showToast('Gagal memecah tugas.', 'error');
        }
    } catch (err) {
        console.error('Breakdown error:', err);
        showToast('Terjadi kesalahan saat memecah tugas.', 'error');
    }
};

function renderBreakdownResult(breakdown) {
    const wrap = document.getElementById('aiBreakdownResultWrap');
    const titleEl = document.getElementById('aiBreakdownResultTitle');
    const durEl = document.getElementById('aiBreakdownTotalDuration');
    const tipsEl = document.getElementById('aiBreakdownTips');
    const listEl = document.getElementById('aiBreakdownStepsList');

    if (!wrap || !listEl) return;

    if (titleEl) titleEl.textContent = breakdown.goal;
    if (durEl) durEl.textContent = `⏱️ Total Perkiraan Waktu: ~${Math.round(breakdown.total_estimated_minutes / 60 * 10) / 10} Jam (${breakdown.total_estimated_minutes} Menit)`;
    if (tipsEl) tipsEl.innerHTML = breakdown.tips ? `💡 <strong>Tips AI:</strong> ${escapeHtml(breakdown.tips)}` : '';

    listEl.innerHTML = '';
    (breakdown.steps || []).forEach((st, idx) => {
        const stepCard = document.createElement('div');
        stepCard.className = 'ai-breakdown-step-card';
        stepCard.innerHTML = `
            <div class="step-num">${idx + 1}</div>
            <div style="flex: 1;">
                <strong style="color: #fff; font-size: 13px;">${escapeHtml(st.title)}</strong>
                <div style="font-size: 11px; color: var(--text-secondary); margin-top: 2px;">
                    <span class="step-phase-tag">${escapeHtml(st.phase || 'Eksekusi')}</span>
                    <span>~${st.estimated_minutes} menit</span>
                </div>
            </div>
        `;
        listEl.appendChild(stepCard);
    });

    wrap.style.display = 'block';
}

window.saveBreakdownAsTask = async function() {
    if (!currentAiBreakdownData) return;

    const subtasks = (currentAiBreakdownData.steps || []).map(st => ({
        title: `${st.title} (${st.estimated_minutes}m)`,
        completed: false,
    }));

    try {
        const res = await apiRequest('/ai/confirm-draft', 'POST', {
            type: 'task',
            title: currentAiBreakdownData.goal,
            estimated_duration: currentAiBreakdownData.total_estimated_minutes,
            priority: 'high',
            deadline: new Date(Date.now() + 86400000 * 3).toISOString().slice(0, 19).replace('T', ' '),
            subtasks,
        });

        if (res.success) {
            showToast('Tugas berhasil dibuat beserta rincian subtasks!', 'success');
            closeAiModal();
            setTimeout(() => location.reload(), 600);
        }
    } catch (err) {
        console.error('Save breakdown error:', err);
        showToast('Gagal menyimpan tugas.', 'error');
    }
};

window.openAiBreakdownForTask = function(title, taskId) {
    openAiModal('breakdown', title);
};

// Toggle Subtask Checkbox on Task Card
window.toggleTaskSubtask = async function(taskId, subtaskIndex, isChecked) {
    try {
        const res = await apiRequest(`/tasks/${taskId}/subtask-toggle`, 'PATCH', {
            index: subtaskIndex,
            completed: isChecked,
        });

        if (res.success) {
            showToast(isChecked ? 'Subtask diselesaikan! 🎉' : 'Subtask diperbarui', 'success');
        }
    } catch (err) {
        console.error('Toggle subtask error:', err);
        showToast('Gagal memperbarui status subtask', 'error');
    }
};

// Global Paste (Ctrl+V) listener for screenshots
document.addEventListener('paste', (e) => {
    if (!e.clipboardData || !e.clipboardData.items) return;
    const items = e.clipboardData.items;

    for (let i = 0; i < items.length; i++) {
        if (items[i].type.indexOf('image') !== -1) {
            const blob = items[i].getAsFile();
            if (blob) {
                openAiModal('ocr');
                handleOcrImageUpload([blob]);
                showToast('Gambar dari clipboard berhasil dideteksi! Memulai OCR...', 'info');
                break;
            }
        }
    }
});

// ==========================================
// V4 PERSONAL OS — Focus Mode & Habits Module
// ==========================================

let focusTimerState = {
    running: false,
    intervalId: null,
    totalSeconds: 25 * 60,
    remainingSeconds: 25 * 60,
    type: 'pomodoro',
    taskId: null,
};

window.switchProdTab = function(tabName) {
    const tabs = ['focus', 'habits', 'analytics', 'review'];
    tabs.forEach(t => {
        const btn = document.getElementById(`tabBtn${t.charAt(0).toUpperCase() + t.slice(1)}`);
        const sec = document.getElementById(`tabSection${t.charAt(0).toUpperCase() + t.slice(1)}`);
        if (btn && sec) {
            if (t === tabName) {
                btn.classList.add('active');
                sec.style.display = 'block';
            } else {
                btn.classList.remove('active');
                sec.style.display = 'none';
            }
        }
    });
};

window.selectTimerPreset = function(minutes, type, btnElement) {
    if (focusTimerState.running) {
        if (!confirm('Sesi sedang berjalan. Ubah durasi timer?')) return;
        clearInterval(focusTimerState.intervalId);
        focusTimerState.running = false;
    }

    document.querySelectorAll('.timer-type-btn').forEach(b => b.classList.remove('active'));
    if (btnElement) btnElement.classList.add('active');

    focusTimerState.totalSeconds = minutes * 60;
    focusTimerState.remainingSeconds = minutes * 60;
    focusTimerState.type = type;

    updateTimerDisplay();

    const toggleBtn = document.getElementById('btnTimerToggle');
    if (toggleBtn) toggleBtn.innerHTML = '▶ Mulai Sesi';
    const statusText = document.getElementById('timerStatusText');
    if (statusText) statusText.textContent = 'Siap Fokus';
    const finishBtn = document.getElementById('btnTimerFinish');
    if (finishBtn) finishBtn.style.display = 'none';
};

window.toggleTimer = function() {
    const toggleBtn = document.getElementById('btnTimerToggle');
    const statusText = document.getElementById('timerStatusText');
    const finishBtn = document.getElementById('btnTimerFinish');

    if (!focusTimerState.running) {
        // Start Timer
        focusTimerState.running = true;
        if (toggleBtn) toggleBtn.innerHTML = '⏸ Jeda';
        if (statusText) statusText.textContent = 'Sesi Sedang Berjalan...';
        if (finishBtn) finishBtn.style.display = 'inline-block';

        const taskSelect = document.getElementById('focusTaskSelect');
        if (taskSelect) {
            focusTimerState.taskId = taskSelect.value ? parseInt(taskSelect.value, 10) : null;
        }

        focusTimerState.intervalId = setInterval(() => {
            if (focusTimerState.remainingSeconds > 0) {
                focusTimerState.remainingSeconds--;
                updateTimerDisplay();
            } else {
                clearInterval(focusTimerState.intervalId);
                focusTimerState.running = false;
                onTimerFinished();
            }
        }, 1000);
    } else {
        // Pause Timer
        clearInterval(focusTimerState.intervalId);
        focusTimerState.running = false;
        if (toggleBtn) toggleBtn.innerHTML = '▶ Lanjutkan';
        if (statusText) statusText.textContent = 'Dijeda';
    }
};

window.resetTimer = function() {
    clearInterval(focusTimerState.intervalId);
    focusTimerState.running = false;
    focusTimerState.remainingSeconds = focusTimerState.totalSeconds;

    const toggleBtn = document.getElementById('btnTimerToggle');
    if (toggleBtn) toggleBtn.innerHTML = '▶ Mulai Sesi';
    const statusText = document.getElementById('timerStatusText');
    if (statusText) statusText.textContent = 'Siap Fokus';
    const finishBtn = document.getElementById('btnTimerFinish');
    if (finishBtn) finishBtn.style.display = 'none';

    updateTimerDisplay();
};

function updateTimerDisplay() {
    const digitsEl = document.getElementById('timerDigits');
    if (!digitsEl) return;

    const mins = Math.floor(focusTimerState.remainingSeconds / 60);
    const secs = focusTimerState.remainingSeconds % 60;
    digitsEl.textContent = `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;

    // SVG Dial progress
    const circle = document.getElementById('timerProgressCircle');
    if (circle) {
        const radius = 95;
        const circumference = 2 * Math.PI * radius;
        const progress = focusTimerState.remainingSeconds / focusTimerState.totalSeconds;
        const offset = circumference * (1 - progress);
        circle.style.strokeDasharray = `${circumference}`;
        circle.style.strokeDashoffset = `${offset}`;
    }
}

window.finishSessionManually = function() {
    if (!confirm('Selesaikan sesi fokus sekarang dan simpan progres ke log?')) return;
    clearInterval(focusTimerState.intervalId);
    focusTimerState.running = false;
    onTimerFinished(true);
};

async function onTimerFinished(manual = false) {
    const elapsedMinutes = Math.max(1, Math.round((focusTimerState.totalSeconds - focusTimerState.remainingSeconds) / 60));
    const taskSelect = document.getElementById('focusTaskSelect');
    const taskId = taskSelect && taskSelect.value ? parseInt(taskSelect.value, 10) : null;

    let markCompleted = false;
    if (taskId) {
        markCompleted = confirm(`Sesi ${elapsedMinutes} menit selesai! Apakah tugas ini ingin ditandai Selesai (100%)?`);
    }

    try {
        const res = await apiRequest('/productivity/focus-sessions', 'POST', {
            task_id: taskId,
            type: focusTimerState.type,
            duration_minutes: elapsedMinutes,
            notes: manual ? 'Selesai manual' : 'Sesi timer tuntas',
            mark_task_completed: markCompleted,
        });

        if (res.success) {
            showToast(`Sesi fokus ${elapsedMinutes} menit berhasil dicatat! 🎉`, 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1200);
        } else {
            showToast('Gagal mencatat sesi fokus.', 'error');
            resetTimer();
        }
    } catch (err) {
        console.error('Focus session save error:', err);
        showToast('Gagal mencatat sesi fokus.', 'error');
        resetTimer();
    }
}

// Habits Interactivity
window.toggleHabitCheckin = async function(habitId, btnElement) {
    if (!habitId) return;
    btnElement.disabled = true;

    try {
        const todayStr = new Date().toISOString().slice(0, 10);
        const res = await apiRequest(`/productivity/habits/${habitId}/toggle`, 'POST', {
            date: todayStr,
        });

        if (res.success) {
            showToast(res.message || 'Status rutinitas diperbarui!', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 800);
        } else {
            showToast('Gagal memperbarui rutinitas.', 'error');
            btnElement.disabled = false;
        }
    } catch (err) {
        console.error('Habit toggle error:', err);
        showToast('Gagal memperbarui rutinitas.', 'error');
        btnElement.disabled = false;
    }
};

window.openAddHabitModal = function() {
    const modal = document.getElementById('addHabitModalOverlay');
    if (modal) {
        modal.classList.add('open', 'active');
        document.body.style.overflow = 'hidden';
    }
};

window.closeAddHabitModal = function() {
    const modal = document.getElementById('addHabitModalOverlay');
    if (modal) {
        modal.classList.remove('open', 'active');
        document.body.style.overflow = '';
    }
};

window.submitNewHabit = async function(e) {
    e.preventDefault();

    const title = document.getElementById('habitTitleInput')?.value.trim();
    const icon = document.getElementById('habitIconInput')?.value.trim() || '⚡';
    const category = document.getElementById('habitCategorySelect')?.value || 'kesehatan';
    const cadence = document.getElementById('habitCadenceSelect')?.value || 'alternate_days';
    const description = document.getElementById('habitDescInput')?.value.trim();

    if (!title) {
        showToast('Nama rutinitas wajib diisi!', 'warning');
        return;
    }

    try {
        const res = await apiRequest('/productivity/habits', 'POST', {
            title,
            icon,
            category,
            cadence,
            description,
        });

        if (res.success) {
            showToast('Rutinitas berhasil ditambahkan! 🚀', 'success');
            closeAddHabitModal();
            setTimeout(() => {
                window.location.reload();
            }, 800);
        } else {
            showToast('Gagal membuat rutinitas.', 'error');
        }
    } catch (err) {
        console.error('Habit create error:', err);
        showToast('Gagal membuat rutinitas.', 'error');
    }
};

window.deleteHabit = async function(habitId) {
    if (!confirm('Apakah kamu yakin ingin menghapus rutinitas ini?')) return;

    try {
        const res = await apiRequest(`/productivity/habits/${habitId}`, 'DELETE');
        if (res.success) {
            showToast('Rutinitas dihapus.', 'info');
            const card = document.getElementById(`habit-card-${habitId}`);
            if (card) card.remove();
        } else {
            showToast('Gagal menghapus rutinitas.', 'error');
        }
    } catch (err) {
        console.error('Habit delete error:', err);
        showToast('Gagal menghapus rutinitas.', 'error');
    }
};

// URL query parameter check for auto-launching Focus mode with a task
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const focusTaskId = urlParams.get('focus_task_id');
    if (focusTaskId) {
        switchProdTab('focus');
        const taskSelect = document.getElementById('focusTaskSelect');
        if (taskSelect) {
            taskSelect.value = focusTaskId;
            showToast('Tugas ditautkan ke sesi fokus!', 'info');
        }
    }
});

// ==========================================
// PERSONAL FINANCE — Phase 1: Ledger Module
// ==========================================

window.openTransactionModal = function(defaultType = 'expense') {
    const modal = document.getElementById('transactionModalOverlay');
    const typeSelect = document.getElementById('txTypeSelect');
    if (typeSelect && defaultType) {
        typeSelect.value = defaultType;
        onTxTypeChange();
    }
    if (modal) {
        modal.classList.add('open', 'active');
        document.body.style.overflow = 'hidden';
    }
};

window.closeTransactionModal = function() {
    const modal = document.getElementById('transactionModalOverlay');
    if (modal) {
        modal.classList.remove('open', 'active');
        document.body.style.overflow = '';
    }
};

window.onTxTypeChange = function() {
    const typeSelect = document.getElementById('txTypeSelect');
    const destGroup = document.getElementById('txDestAccountGroup');
    const destSelect = document.getElementById('txDestAccountSelect');
    const catGroup = document.getElementById('txCategoryGroup');

    if (!typeSelect) return;

    if (typeSelect.value === 'transfer') {
        if (destGroup) destGroup.style.display = 'block';
        if (destSelect) destSelect.required = true;
        if (catGroup) catGroup.style.display = 'none';
    } else {
        if (destGroup) destGroup.style.display = 'none';
        if (destSelect) destSelect.required = false;
        if (catGroup) catGroup.style.display = 'block';
    }
};

window.submitTransaction = async function(e) {
    e.preventDefault();

    const account_id = document.getElementById('txAccountSelect')?.value;
    const destination_account_id = document.getElementById('txDestAccountSelect')?.value || null;
    const type = document.getElementById('txTypeSelect')?.value;
    const category_id = document.getElementById('txCategorySelect')?.value || null;
    const amount = parseFloat(document.getElementById('txAmountInput')?.value || 0);
    const transaction_date = document.getElementById('txDateInput')?.value;
    const description = document.getElementById('txDescInput')?.value.trim();
    const notes = document.getElementById('txNotesInput')?.value.trim() || null;

    if (!account_id || !type || isNaN(amount) || amount <= 0 || !description) {
        showToast('Mohon lengkapi formulir transaksi dengan benar!', 'warning');
        return;
    }

    if (type === 'transfer' && (!destination_account_id || destination_account_id === account_id)) {
        showToast('Pilih akun tujuan yang berbeda untuk transfer!', 'warning');
        return;
    }

    const submitBtn = document.getElementById('btnSubmitTx');
    if (submitBtn) submitBtn.disabled = true;

    try {
        const res = await apiRequest('/finance/transactions', 'POST', {
            account_id: parseInt(account_id, 10),
            destination_account_id: destination_account_id ? parseInt(destination_account_id, 10) : null,
            type,
            category_id: category_id ? parseInt(category_id, 10) : null,
            amount,
            transaction_date,
            description,
            notes,
        });

        if (res.success) {
            showToast(res.message || 'Transaksi berhasil dicatat!', 'success');
            closeTransactionModal();
            setTimeout(() => {
                window.location.reload();
            }, 700);
        } else {
            showToast(res.message || 'Gagal mencatat transaksi.', 'error');
            if (submitBtn) submitBtn.disabled = false;
        }
    } catch (err) {
        console.error('Transaction save error:', err);
        showToast('Terjadi kesalahan saat mencatat transaksi.', 'error');
        if (submitBtn) submitBtn.disabled = false;
    }
};

window.deleteTransaction = async function(transactionId) {
    if (!confirm('Apakah kamu yakin ingin menghapus transaksi ini? Saldo akun akan dikalkulasi ulang.')) return;

    try {
        const res = await apiRequest(`/finance/transactions/${transactionId}`, 'DELETE');
        if (res.success) {
            showToast('Transaksi berhasil dihapus!', 'info');
            setTimeout(() => {
                window.location.reload();
            }, 600);
        } else {
            showToast('Gagal menghapus transaksi.', 'error');
        }
    } catch (err) {
        console.error('Delete transaction error:', err);
        showToast('Terjadi kesalahan saat menghapus transaksi.', 'error');
    }
};

window.openAccountModal = function() {
    const modal = document.getElementById('accountModalOverlay');
    if (modal) {
        modal.classList.add('open', 'active');
        document.body.style.overflow = 'hidden';
    }
};

window.closeAccountModal = function() {
    const modal = document.getElementById('accountModalOverlay');
    if (modal) {
        modal.classList.remove('open', 'active');
        document.body.style.overflow = '';
    }
};

window.submitAccount = async function(e) {
    e.preventDefault();

    const name = document.getElementById('accNameInput')?.value.trim();
    const type = document.getElementById('accTypeSelect')?.value;
    const opening_balance = parseFloat(document.getElementById('accOpeningBalanceInput')?.value || 0);
    const notes = document.getElementById('accNotesInput')?.value.trim() || null;

    if (!name) {
        showToast('Nama akun wajib diisi!', 'warning');
        return;
    }

    try {
        const res = await apiRequest('/finance/accounts', 'POST', {
            name,
            type,
            opening_balance,
            notes,
        });

        if (res.success) {
            showToast('Akun berhasil dibuat!', 'success');
            closeAccountModal();
            setTimeout(() => {
                window.location.reload();
            }, 700);
        } else {
            showToast('Gagal membuat akun.', 'error');
        }
    } catch (err) {
        console.error('Account save error:', err);
        showToast('Terjadi kesalahan saat membuat akun.', 'error');
    }
};

// ==========================================
// PERSONAL FINANCE — Phase 2: Income & Budget
// ==========================================

window.switchFinanceTab = function(tabName) {
    const tabs = ['ledger', 'income', 'budget', 'installments', 'savings', 'intelligence'];
    tabs.forEach(t => {
        const btn = document.getElementById(`tabBtn${t.charAt(0).toUpperCase() + t.slice(1)}`);
        const sec = document.getElementById(`tabSection${t.charAt(0).toUpperCase() + t.slice(1)}`);
        if (btn && sec) {
            if (t === tabName) {
                btn.classList.add('active');
                sec.style.display = 'block';
            } else {
                btn.classList.remove('active');
                sec.style.display = 'none';
            }
        }
    });
};

window.openIncomeScheduleModal = function() {
    const modal = document.getElementById('incomeScheduleModalOverlay');
    if (modal) {
        modal.classList.add('open', 'active');
        document.body.style.overflow = 'hidden';
    }
};

window.closeIncomeScheduleModal = function() {
    const modal = document.getElementById('incomeScheduleModalOverlay');
    if (modal) {
        modal.classList.remove('open', 'active');
        document.body.style.overflow = '';
    }
};

window.submitIncomeSchedule = async function(e) {
    e.preventDefault();

    const weekday_amounts = {
        '1': parseFloat(document.getElementById('schDay1')?.value || 0),
        '2': parseFloat(document.getElementById('schDay2')?.value || 0),
        '3': parseFloat(document.getElementById('schDay3')?.value || 0),
        '4': parseFloat(document.getElementById('schDay4')?.value || 0),
        '5': parseFloat(document.getElementById('schDay5')?.value || 0),
        '6': parseFloat(document.getElementById('schDay6')?.value || 0),
        '7': parseFloat(document.getElementById('schDay7')?.value || 0),
    };

    try {
        const res = await apiRequest('/finance/income/schedule', 'POST', {
            type: 'daily_variable',
            weekday_amounts,
        });

        if (res.success) {
            showToast(res.message || 'Pola pemasukan mingguan berhasil disimpan!', 'success');
            closeIncomeScheduleModal();
            setTimeout(() => {
                window.location.reload();
            }, 700);
        } else {
            showToast(res.message || 'Gagal menyimpan pola pemasukan.', 'error');
        }
    } catch (err) {
        console.error('Schedule save error:', err);
        showToast('Terjadi kesalahan saat menyimpan pola pemasukan.', 'error');
    }
};

window.openOverrideModal = function() {
    const modal = document.getElementById('overrideModalOverlay');
    if (modal) {
        modal.classList.add('open', 'active');
        document.body.style.overflow = 'hidden';
    }
};

window.closeOverrideModal = function() {
    const modal = document.getElementById('overrideModalOverlay');
    if (modal) {
        modal.classList.remove('open', 'active');
        document.body.style.overflow = '';
    }
};

window.submitOverride = async function(e) {
    e.preventDefault();

    const is_extra = document.getElementById('ovIsExtraSelect')?.value === '1';
    const override_date = document.getElementById('ovDateInput')?.value;
    const title = document.getElementById('ovTitleInput')?.value.trim();
    const amount = parseFloat(document.getElementById('ovAmountInput')?.value || 0);
    const notes = document.getElementById('ovNotesInput')?.value.trim() || null;

    if (!override_date || !title || isNaN(amount) || amount <= 0) {
        showToast('Lengkapi data pemasukan ekstra dengan benar!', 'warning');
        return;
    }

    try {
        const res = await apiRequest('/finance/income/overrides', 'POST', {
            override_date,
            title,
            amount,
            is_extra,
            notes,
        });

        if (res.success) {
            showToast(res.message || 'Jadwal pemasukan berhasil disimpan!', 'success');
            closeOverrideModal();
            setTimeout(() => {
                window.location.reload();
            }, 700);
        } else {
            showToast(res.message || 'Gagal menyimpan jadwal.', 'error');
        }
    } catch (err) {
        console.error('Override save error:', err);
        showToast('Terjadi kesalahan saat menyimpan jadwal.', 'error');
    }
};

window.deleteIncomeOverride = async function(id) {
    if (!confirm('Apakah kamu yakin ingin menghapus jadwal ini?')) return;

    try {
        const res = await apiRequest(`/finance/income/overrides/${id}`, 'DELETE');
        if (res.success) {
            showToast('Jadwal berhasil dihapus!', 'info');
            setTimeout(() => {
                window.location.reload();
            }, 600);
        } else {
            showToast('Gagal menghapus jadwal.', 'error');
        }
    } catch (err) {
        console.error('Delete override error:', err);
        showToast('Terjadi kesalahan saat menghapus jadwal.', 'error');
    }
};

window.submitEssentialBudget = async function(e) {
    e.preventDefault();

    const food = parseFloat(document.getElementById('budgetFoodInput')?.value || 0);
    const transport = parseFloat(document.getElementById('budgetTransportInput')?.value || 0);
    const snack = parseFloat(document.getElementById('budgetSnackInput')?.value || 0);
    const other = parseFloat(document.getElementById('budgetOtherInput')?.value || 0);
    const notes = document.getElementById('budgetNotesInput')?.value.trim() || null;

    try {
        const res = await apiRequest('/finance/budget', 'POST', {
            food,
            transport,
            snack,
            other,
            notes,
        });

        if (res.success) {
            showToast(res.message || 'Kebutuhan pokok harian berhasil diperbarui!', 'success');
            const totalPreview = document.getElementById('budgetTotalPreview');
            const total = food + transport + snack + other;
            if (totalPreview) {
                totalPreview.textContent = `Rp ${total.toLocaleString('id-ID')} / hari`;
            }
            setTimeout(() => {
                window.location.reload();
            }, 700);
        } else {
            showToast(res.message || 'Gagal menyimpan anggaran pokok.', 'error');
        }
    } catch (err) {
        console.error('Budget save error:', err);
        showToast('Terjadi kesalahan saat menyimpan anggaran pokok.', 'error');
    }
};

// ==========================================
// PERSONAL FINANCE — Phase 3: Installments
// ==========================================

window.openInstallmentModal = function() {
    const modal = document.getElementById('installmentModalOverlay');
    if (modal) {
        modal.classList.add('open', 'active');
        document.body.style.overflow = 'hidden';
    }
};

window.closeInstallmentModal = function() {
    const modal = document.getElementById('installmentModalOverlay');
    if (modal) {
        modal.classList.remove('open', 'active');
        document.body.style.overflow = '';
    }
};

window.submitInstallment = async function(e) {
    e.preventDefault();

    const name = document.getElementById('instNameInput')?.value.trim();
    const category = document.getElementById('instCategorySelect')?.value || 'elektronik';
    const due_day = parseInt(document.getElementById('instDueDayInput')?.value || '24', 10);
    const monthly_amount = parseFloat(document.getElementById('instMonthlyAmountInput')?.value || 0);
    const total_amount = parseFloat(document.getElementById('instTotalAmountInput')?.value || 0);
    const start_date = document.getElementById('instStartDateInput')?.value;
    const end_date = document.getElementById('instEndDateInput')?.value || null;
    const notes = document.getElementById('instNotesInput')?.value.trim() || null;

    if (!name || isNaN(monthly_amount) || monthly_amount <= 0 || isNaN(total_amount) || total_amount <= 0) {
        showToast('Mohon lengkapi formulir cicilan dengan benar!', 'warning');
        return;
    }

    try {
        const res = await apiRequest('/finance/installments', 'POST', {
            name,
            category,
            due_day,
            monthly_amount,
            total_amount,
            start_date,
            end_date,
            notes,
        });

        if (res.success) {
            showToast(res.message || 'Cicilan berhasil ditambahkan!', 'success');
            closeInstallmentModal();
            setTimeout(() => {
                window.location.reload();
            }, 700);
        } else {
            showToast(res.message || 'Gagal menyimpan cicilan.', 'error');
        }
    } catch (err) {
        console.error('Installment save error:', err);
        showToast('Terjadi kesalahan saat menyimpan cicilan.', 'error');
    }
};

window.deleteInstallment = async function(id) {
    if (!confirm('Apakah kamu yakin ingin menghapus data cicilan ini?')) return;

    try {
        const res = await apiRequest(`/finance/installments/${id}`, 'DELETE');
        if (res.success) {
            showToast('Cicilan berhasil dihapus!', 'info');
            setTimeout(() => {
                window.location.reload();
            }, 600);
        } else {
            showToast(res.message || 'Gagal menghapus cicilan.', 'error');
        }
    } catch (err) {
        console.error('Delete installment error:', err);
        showToast('Terjadi kesalahan saat menghapus cicilan.', 'error');
    }
};

window.openReserveModal = function(id, name, remainingObligation) {
    const modal = document.getElementById('reserveModalOverlay');
    const idInput = document.getElementById('reserveInstallmentId');
    const title = document.getElementById('reserveTargetTitle');
    const amountInput = document.getElementById('reserveAmountInput');

    if (idInput) idInput.value = id;
    if (title) title.textContent = `Cicilan: ${name} (Sisa Perlu Dicadangkan: Rp ${Number(remainingObligation).toLocaleString('id-ID')})`;
    if (amountInput) amountInput.value = remainingObligation > 0 ? remainingObligation : '';

    if (modal) {
        modal.classList.add('open', 'active');
        document.body.style.overflow = 'hidden';
    }
};

window.closeReserveModal = function() {
    const modal = document.getElementById('reserveModalOverlay');
    if (modal) {
        modal.classList.remove('open', 'active');
        document.body.style.overflow = '';
    }
};

window.submitReserve = async function(e) {
    e.preventDefault();

    const id = document.getElementById('reserveInstallmentId')?.value;
    const account_id = document.getElementById('reserveAccountSelect')?.value;
    const amount = parseFloat(document.getElementById('reserveAmountInput')?.value || 0);
    const notes = document.getElementById('reserveNotesInput')?.value.trim() || null;

    if (!id || !account_id || isNaN(amount) || amount <= 0) {
        showToast('Tentukan akun kas dan nominal cadangan dengan benar!', 'warning');
        return;
    }

    try {
        const res = await apiRequest(`/finance/installments/${id}/reserve`, 'POST', {
            account_id,
            amount,
            notes,
        });

        if (res.success) {
            showToast(res.message || 'Dana berhasil dicadangkan!', 'success');
            closeReserveModal();
            setTimeout(() => {
                window.location.reload();
            }, 700);
        } else {
            showToast(res.message || 'Gagal mencadangkan dana.', 'error');
        }
    } catch (err) {
        console.error('Reserve save error:', err);
        showToast('Terjadi kesalahan saat mencadangkan dana.', 'error');
    }
};

window.openPayInstallmentModal = function(id, name, monthlyAmount) {
    const modal = document.getElementById('payInstallmentModalOverlay');
    const idInput = document.getElementById('payInstallmentId');
    const title = document.getElementById('payTargetTitle');
    const amountInput = document.getElementById('payAmountInput');

    if (idInput) idInput.value = id;
    if (title) title.textContent = `Tagihan: ${name} (Nominal: Rp ${Number(monthlyAmount).toLocaleString('id-ID')})`;
    if (amountInput) amountInput.value = monthlyAmount;

    if (modal) {
        modal.classList.add('open', 'active');
        document.body.style.overflow = 'hidden';
    }
};

window.closePayInstallmentModal = function() {
    const modal = document.getElementById('payInstallmentModalOverlay');
    if (modal) {
        modal.classList.remove('open', 'active');
        document.body.style.overflow = '';
    }
};

window.submitPayInstallment = async function(e) {
    e.preventDefault();

    const id = document.getElementById('payInstallmentId')?.value;
    const account_id = document.getElementById('payAccountSelect')?.value;
    const amount = parseFloat(document.getElementById('payAmountInput')?.value || 0);
    const transaction_date = document.getElementById('payDateInput')?.value;
    const notes = document.getElementById('payNotesInput')?.value.trim() || null;

    if (!id || !account_id || isNaN(amount) || amount <= 0) {
        showToast('Tentukan akun pembayar dan nominal pembayaran dengan benar!', 'warning');
        return;
    }

    try {
        const res = await apiRequest(`/finance/installments/${id}/pay`, 'POST', {
            account_id,
            amount,
            transaction_date,
            notes,
        });

        if (res.success) {
            showToast(res.message || 'Pembayaran cicilan berhasil dicatat!', 'success');
            closePayInstallmentModal();
            setTimeout(() => {
                window.location.reload();
            }, 700);
        } else {
            showToast(res.message || 'Gagal mencatat pembayaran cicilan.', 'error');
        }
    } catch (err) {
        console.error('Pay installment error:', err);
        showToast('Terjadi kesalahan saat mencatat pembayaran.', 'error');
    }
};

// ==========================================
// PERSONAL FINANCE — Phase 4: Savings Goals
// ==========================================

window.openSavingsGoalModal = function() {
    const modal = document.getElementById('savingsGoalModalOverlay');
    if (modal) {
        modal.classList.add('open', 'active');
        document.body.style.overflow = 'hidden';
    }
};

window.closeSavingsGoalModal = function() {
    const modal = document.getElementById('savingsGoalModalOverlay');
    if (modal) {
        modal.classList.remove('open', 'active');
        document.body.style.overflow = '';
    }
};

window.onGoalTypeChange = function() {
    const typeSelect = document.getElementById('goalTypeSelect');
    const dateGroup = document.getElementById('goalDateGroup');
    const dateInput = document.getElementById('goalTargetDateInput');
    if (!typeSelect || !dateGroup) return;

    if (typeSelect.value === 'deadline') {
        dateGroup.style.display = 'block';
        if (dateInput) dateInput.required = true;
    } else {
        dateGroup.style.display = 'none';
        if (dateInput) dateInput.required = false;
    }
};

window.submitSavingsGoal = async function(e) {
    e.preventDefault();

    const name = document.getElementById('goalNameInput')?.value.trim();
    const type = document.getElementById('goalTypeSelect')?.value || 'deadline';
    const target_amount = parseFloat(document.getElementById('goalTargetAmountInput')?.value || 0);
    const target_date = type === 'deadline' ? document.getElementById('goalTargetDateInput')?.value : null;
    const icon = document.getElementById('goalIconSelect')?.value || '🎯';
    const color = document.getElementById('goalColorSelect')?.value || '#10b981';
    const notes = document.getElementById('goalNotesInput')?.value.trim() || null;

    if (!name || isNaN(target_amount) || target_amount <= 0) {
        showToast('Mohon lengkapi target tabungan dengan benar!', 'warning');
        return;
    }

    if (type === 'deadline' && !target_date) {
        showToast('Tanggal target deadline wajib diisi!', 'warning');
        return;
    }

    try {
        const res = await apiRequest('/finance/savings', 'POST', {
            name,
            type,
            target_amount,
            target_date,
            icon,
            color,
            notes,
        });

        if (res.success) {
            showToast(res.message || 'Target tabungan berhasil dibuat!', 'success');
            closeSavingsGoalModal();
            setTimeout(() => {
                window.location.reload();
            }, 700);
        } else {
            showToast(res.message || 'Gagal membuat target tabungan.', 'error');
        }
    } catch (err) {
        console.error('Savings goal save error:', err);
        showToast('Terjadi kesalahan saat menyimpan target.', 'error');
    }
};

window.deleteSavingsGoal = async function(id) {
    if (!confirm('Apakah kamu yakin ingin menghapus target tabungan ini?')) return;

    try {
        const res = await apiRequest(`/finance/savings/${id}`, 'DELETE');
        if (res.success) {
            showToast('Target tabungan berhasil dihapus!', 'info');
            setTimeout(() => {
                window.location.reload();
            }, 600);
        } else {
            showToast(res.message || 'Gagal menghapus target tabungan.', 'error');
        }
    } catch (err) {
        console.error('Delete savings goal error:', err);
        showToast('Terjadi kesalahan saat menghapus target tabungan.', 'error');
    }
};

window.openSaveFundsModal = function(id, name, remainingAmount) {
    const modal = document.getElementById('saveFundsModalOverlay');
    const idInput = document.getElementById('saveFundsGoalId');
    const title = document.getElementById('saveFundsTargetTitle');
    const amountInput = document.getElementById('saveFundsAmountInput');

    if (idInput) idInput.value = id;
    if (title) title.textContent = `Target: ${name} (Sisa Menuju Target: Rp ${Number(remainingAmount).toLocaleString('id-ID')})`;
    if (amountInput) amountInput.value = '';

    if (modal) {
        modal.classList.add('open', 'active');
        document.body.style.overflow = 'hidden';
    }
};

window.closeSaveFundsModal = function() {
    const modal = document.getElementById('saveFundsModalOverlay');
    if (modal) {
        modal.classList.remove('open', 'active');
        document.body.style.overflow = '';
    }
};

window.submitSaveFunds = async function(e) {
    e.preventDefault();

    const id = document.getElementById('saveFundsGoalId')?.value;
    const account_id = document.getElementById('saveFundsAccountSelect')?.value;
    const amount = parseFloat(document.getElementById('saveFundsAmountInput')?.value || 0);
    const allocation_date = document.getElementById('saveFundsDateInput')?.value;
    const notes = document.getElementById('saveFundsNotesInput')?.value.trim() || null;

    if (!id || !account_id || isNaN(amount) || amount <= 0) {
        showToast('Tentukan akun kas dan nominal tabungan dengan benar!', 'warning');
        return;
    }

    try {
        const res = await apiRequest(`/finance/savings/${id}/allocate`, 'POST', {
            account_id,
            amount,
            allocation_date,
            notes,
        });

        if (res.success) {
            showToast(res.message || 'Dana berhasil ditabung!', 'success');
            closeSaveFundsModal();
            setTimeout(() => {
                window.location.reload();
            }, 700);
        } else {
            showToast(res.message || 'Gagal menabung dana.', 'error');
        }
    } catch (err) {
        console.error('Save funds error:', err);
        showToast('Terjadi kesalahan saat menabung dana.', 'error');
    }
};

window.openWithdrawSavingsModal = function(id, name, currentAmount) {
    const modal = document.getElementById('withdrawSavingsModalOverlay');
    const idInput = document.getElementById('withdrawGoalId');
    const title = document.getElementById('withdrawTargetTitle');
    const amountInput = document.getElementById('withdrawAmountInput');

    if (idInput) idInput.value = id;
    if (title) title.textContent = `Target: ${name} (Terkumpul Saat Ini: Rp ${Number(currentAmount).toLocaleString('id-ID')})`;
    if (amountInput) {
        amountInput.value = currentAmount;
        amountInput.max = currentAmount;
    }

    if (modal) {
        modal.classList.add('open', 'active');
        document.body.style.overflow = 'hidden';
    }
};

window.closeWithdrawSavingsModal = function() {
    const modal = document.getElementById('withdrawSavingsModalOverlay');
    if (modal) {
        modal.classList.remove('open', 'active');
        document.body.style.overflow = '';
    }
};

window.submitWithdrawSavings = async function(e) {
    e.preventDefault();

    const id = document.getElementById('withdrawGoalId')?.value;
    const account_id = document.getElementById('withdrawAccountSelect')?.value;
    const amount = parseFloat(document.getElementById('withdrawAmountInput')?.value || 0);
    const is_expense = document.getElementById('withdrawIsExpenseSelect')?.value === '1';
    const notes = document.getElementById('withdrawNotesInput')?.value.trim() || null;

    if (!id || !account_id || isNaN(amount) || amount <= 0) {
        showToast('Tentukan akun dan nominal penarikan dengan benar!', 'warning');
        return;
    }

    try {
        const res = await apiRequest(`/finance/savings/${id}/withdraw`, 'POST', {
            account_id,
            amount,
            is_expense,
            notes,
        });

        if (res.success) {
            showToast(res.message || 'Penarikan berhasil diproses!', 'success');
            closeWithdrawSavingsModal();
            setTimeout(() => {
                window.location.reload();
            }, 700);
        } else {
            showToast(res.message || 'Gagal memproses penarikan tabungan.', 'error');
        }
    } catch (err) {
        console.error('Withdraw savings error:', err);
        showToast('Terjadi kesalahan saat memproses penarikan.', 'error');
    }
};

// ==========================================
// PERSONAL FINANCE — Phase 5: Intelligence Simulator
// ==========================================

window.checkDateSafeToSpend = async function() {
    const dateInput = document.getElementById('simDateInput');
    if (!dateInput || !dateInput.value) {
        showToast('Pilih tanggal terlebih dahulu!', 'warning');
        return;
    }

    const btn = document.getElementById('btnSimulate');
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Menghitung...';
    }

    try {
        const res = await apiRequest('/finance/safe-to-spend', 'POST', {
            date: dateInput.value,
        });

        if (res.success && res.data) {
            const d = res.data;
            const emptyText = document.getElementById('simEmptyText');
            const details = document.getElementById('simDetails');
            if (emptyText) emptyText.style.display = 'none';
            if (details) details.style.display = 'block';

            const dateLabel = document.getElementById('simDateLabel');
            if (dateLabel) dateLabel.textContent = `Simulasi Tanggal: ${d.date}`;

            const badge = document.getElementById('simBadge');
            if (badge) {
                if (d.status_tone === 'positive') {
                    badge.textContent = '🟢 AMAN';
                    badge.style.background = 'rgba(34, 197, 94, 0.2)';
                    badge.style.color = '#86efac';
                } else if (d.status_tone === 'cautious') {
                    badge.textContent = '🟡 WASPADA';
                    badge.style.background = 'rgba(234, 179, 8, 0.2)';
                    badge.style.color = '#fde047';
                } else {
                    badge.textContent = '🔴 DEFISIT / OVERSPENT';
                    badge.style.background = 'rgba(239, 68, 68, 0.2)';
                    badge.style.color = '#f87171';
                }
            }

            const amountEl = document.getElementById('simAmount');
            if (amountEl) {
                amountEl.textContent = `Rp ${new Intl.NumberFormat('id-ID').format(d.safe_to_spend_today)}`;
                amountEl.style.color = d.safe_to_spend_today > 0 ? '#4ade80' : '#f87171';
            }

            const incomeEl = document.getElementById('simIncome');
            if (incomeEl) incomeEl.textContent = `Rp ${new Intl.NumberFormat('id-ID').format(d.expected_income_today)}`;

            const essEl = document.getElementById('simEssential');
            if (essEl) essEl.textContent = `- Rp ${new Intl.NumberFormat('id-ID').format(d.essential_allowance_today)}`;

            const instEl = document.getElementById('simInstallment');
            if (instEl) instEl.textContent = `- Rp ${new Intl.NumberFormat('id-ID').format(d.installment_daily_today)}`;

            const savEl = document.getElementById('simSavings');
            if (savEl) savEl.textContent = `- Rp ${new Intl.NumberFormat('id-ID').format(d.savings_daily_today)}`;

            const cashEl = document.getElementById('simCash');
            if (cashEl) cashEl.textContent = `Rp ${new Intl.NumberFormat('id-ID').format(d.available_cash)}`;
        } else {
            showToast(res.message || 'Gagal menghitung safe-to-spend.', 'error');
        }
    } catch (err) {
        console.error('Safe to spend simulation error:', err);
        showToast('Terjadi kesalahan saat menghitung safe-to-spend.', 'error');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.textContent = '⚡ Hitung';
        }
    }
};


