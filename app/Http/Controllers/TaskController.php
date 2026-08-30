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
     * Store a new task.
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
            'file' => 'nullable|file|max:51200',
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $validated['file_path'] = $file->store('uploads/tasks', 'public');
            $validated['file_name'] = $file->getClientOriginalName();
            $validated['file_size'] = $file->getSize();
        }

        unset($validated['file']);

        $task = Task::create($validated);

        return response()->json(['success' => true, 'task' => $task], 201);
    }

    /**
     * Update a task.
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
            'file' => 'nullable|file|max:51200',
            'remove_file' => 'nullable|in:true,false,1,0',
        ]);

        if ($request->hasFile('file')) {
            $task->deleteAttachmentFile();
            $file = $request->file('file');
            $validated['file_path'] = $file->store('uploads/tasks', 'public');
            $validated['file_name'] = $file->getClientOriginalName();
            $validated['file_size'] = $file->getSize();
        } elseif ($request->boolean('remove_file')) {
            $task->deleteAttachmentFile();
            $validated['file_path'] = null;
            $validated['file_name'] = null;
            $validated['file_size'] = null;
        }

        unset($validated['file'], $validated['remove_file']);

        $task->update($validated);

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
