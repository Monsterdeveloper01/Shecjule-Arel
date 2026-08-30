<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskController extends Controller
{
    /**
     * Display all tasks with optional filters.
     */
    public function index(Request $request): View
    {
        $query = Task::query();

        if ($request->filled('priority')) {
            $query->byPriority($request->priority);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('subject')) {
            $query->where('subject', $request->subject);
        }

        $tasks = $query->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')")
            ->orderBy('deadline')
            ->get();

        $subjects = Task::whereNotNull('subject')
            ->distinct()
            ->pluck('subject');

        return view('tasks.index', compact('tasks', 'subjects'));
    }

    /**
     * Store a new task with optional multiple file attachments.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'subject' => 'nullable|string|max:255',
            'deadline' => 'required|date',
            'priority' => 'required|in:urgent,high,medium,low',
            'status' => 'nullable|in:pending,in_progress,completed',
            'files' => 'nullable|array',
            'files.*' => 'file|max:51200',
        ]);

        unset($validated['files']);

        $task = Task::create($validated);

        if ($request->hasFile('files')) {
            $task->saveAttachments($request->file('files'), 'uploads/tasks');
        }

        $task->load('attachments');

        return response()->json(['success' => true, 'task' => $task], 201);
    }

    /**
     * Update a task, attach new files, and delete selected existing files.
     */
    public function update(Request $request, Task $task): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'subject' => 'nullable|string|max:255',
            'deadline' => 'required|date',
            'priority' => 'required|in:urgent,high,medium,low',
            'status' => 'nullable|in:pending,in_progress,completed',
            'files' => 'nullable|array',
            'files.*' => 'file|max:51200',
            'deleted_attachment_ids' => 'nullable|array',
            'deleted_attachment_ids.*' => 'integer',
        ]);

        if ($request->filled('deleted_attachment_ids')) {
            $task->deleteAttachmentsByIds($request->input('deleted_attachment_ids'));
        }

        if ($request->hasFile('files')) {
            $task->saveAttachments($request->file('files'), 'uploads/tasks');
        }

        unset($validated['files'], $validated['deleted_attachment_ids']);

        $task->update($validated);
        $task->load('attachments');

        return response()->json(['success' => true, 'task' => $task]);
    }

    /**
     * Delete a task.
     */
    public function destroy(Task $task): JsonResponse
    {
        $task->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Toggle task status (pending → in_progress → completed → pending).
     */
    public function toggleStatus(Task $task): JsonResponse
    {
        $statusFlow = [
            'pending' => 'in_progress',
            'in_progress' => 'completed',
            'completed' => 'pending',
        ];

        $task->update(['status' => $statusFlow[$task->status] ?? 'pending']);

        return response()->json(['success' => true, 'task' => $task]);
    }
}
