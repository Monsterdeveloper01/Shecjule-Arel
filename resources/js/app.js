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

function showCalTooltip(e, dateStr, day, hasTasks, hasEvents, hasNotes) {
    if (!hasTasks && !hasEvents && !hasNotes) return;

    const tooltip = getOrCreateTooltip();
    const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    const tasks = calendarData.tasks?.[dateStr] || [];
    const events = calendarData.events?.[dateStr] || [];
    const notes = calendarData.notes?.[dateStr] || [];

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
        calendarData = { tasks: {}, events: {}, notes: {} };
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

        const el = createCalDay(d, false, isToday, dateStr, dayTasks, dayEvents, dayNotes);
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

function createCalDay(day, isOtherMonth, isToday = false, dateStr = '', tasks = [], events = [], notes = []) {
    const el = document.createElement('div');
    el.className = 'cal-day';
    if (isOtherMonth) el.classList.add('other-month');
    if (isToday) el.classList.add('today');

    const totalCount = (tasks?.length || 0) + (events?.length || 0) + (notes?.length || 0);

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

    // Render Event / Task / Note Chip Badges
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

        // 4. More chip if remaining
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

        el.addEventListener('mouseenter', (e) => showCalTooltip(e, dateStr, day, hasTasks, hasEvents, hasNotes));
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

    let html = '';

    if (tasks.length === 0 && events.length === 0 && notes.length === 0) {
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
            <button type="button" class="btn-secondary btn-full" onclick="sendTestPushNotification()">
                ⚡ Kirim Notifikasi Uji Coba Sekarang
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
    try {
        const res = await apiRequest('/push/test', 'POST');
        alert(res.message);
    } catch (err) {
        alert('Gagal mengirim notifikasi tes.');
    }
};

