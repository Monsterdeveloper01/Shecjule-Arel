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
        ]);

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
        ]);

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
