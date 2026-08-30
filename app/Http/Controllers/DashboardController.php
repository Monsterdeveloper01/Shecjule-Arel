<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Note;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the main dashboard with calendar and today overview.
     */
    public function index(Request $request): View
    {
        $today = now()->toDateString();

        $todayTasks = Task::forDate($today)->orderBy('priority')->get();
        $todayEvents = Event::forDate($today)->orderBy('start_date')->get();
        $todayNotes = Note::forDate($today)->orderBy('is_pinned', 'desc')->get();

        $overdueTasks = Task::overdue()->get();
        $upcomingTasks = Task::upcoming()->limit(5)->get();

        $stats = [
            'pending' => Task::where('status', 'pending')->count(),
            'in_progress' => Task::where('status', 'in_progress')->count(),
            'completed_week' => Task::where('status', 'completed')
                ->where('updated_at', '>=', now()->startOfWeek())
                ->count(),
            'overdue' => Task::overdue()->count(),
        ];

        return view('dashboard', compact(
            'todayTasks',
            'todayEvents',
            'todayNotes',
            'overdueTasks',
            'upcomingTasks',
            'stats',
        ));
    }

    /**
     * Return calendar data (tasks, events, notes) for a given month as JSON.
     */
    public function calendarData(Request $request): JsonResponse
    {
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);

        $tasks = Task::whereYear('deadline', $year)
            ->whereMonth('deadline', $month)
            ->get()
            ->groupBy(fn ($task) => $task->deadline->format('Y-m-d'));

        $events = Event::whereYear('start_date', $year)
            ->whereMonth('start_date', $month)
            ->get()
            ->groupBy(fn ($event) => $event->start_date->format('Y-m-d'));

        $notes = Note::whereYear('note_date', $year)
            ->whereMonth('note_date', $month)
            ->get()
            ->groupBy(fn ($note) => $note->note_date->format('Y-m-d'));

        return response()->json([
            'tasks' => $tasks,
            'events' => $events,
            'notes' => $notes,
        ]);
    }
}
