<?php

namespace App\Http\Controllers;

use App\Models\CourseSchedule;
use App\Models\Event;
use App\Models\Note;
use App\Models\Task;
use App\Services\DeadlineRiskEngine;
use App\Services\PriorityEngine;
use App\Services\TodayIntelligenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the main dashboard with calendar and today overview.
     */
    public function index(
        Request $request,
        PriorityEngine $priorityEngine,
        DeadlineRiskEngine $riskEngine,
        TodayIntelligenceService $todayIntelligenceService
    ): View {
        // Batch recalculate all active task priorities and deadline risks
        $priorityEngine->recalculateAllAndPersist();
        $riskEngine->recalculateAllAndPersist();

        $today = now()->toDateString();

        $todayTasks = Task::forDate($today)->byComputedPriority()->get();
        $todayEvents = Event::forDate($today)->orderBy('start_date')->get();
        $todayNotes = Note::forDate($today)->orderBy('is_pinned', 'desc')->get();
        $todaySchedules = CourseSchedule::today()->with('attachments')->orderBy('start_time')->get();

        $overdueTasks = Task::overdue()->byComputedPriority()->get();
        $upcomingTasks = Task::upcoming()->byComputedPriority()->limit(5)->get();
        $highRiskTasks = $riskEngine->getHighRiskTasks();

        $todayIntelligence = $todayIntelligenceService->getTodayHub();

        $stats = [
            'pending' => Task::where('status', 'pending')->count(),
            'in_progress' => Task::where('status', 'in_progress')->count(),
            'completed_week' => Task::where('status', 'completed')
                ->where('updated_at', '>=', now()->startOfWeek())
                ->count(),
            'overdue' => Task::overdue()->count(),
            'today_classes' => $todaySchedules->count(),
            'high_risk' => $highRiskTasks->count(),
        ];

        return view('dashboard', compact(
            'todayTasks',
            'todayEvents',
            'todayNotes',
            'todaySchedules',
            'overdueTasks',
            'upcomingTasks',
            'highRiskTasks',
            'todayIntelligence',
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
