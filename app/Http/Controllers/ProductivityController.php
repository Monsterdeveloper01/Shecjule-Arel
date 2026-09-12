<?php

namespace App\Http\Controllers;

use App\Models\Habit;
use App\Models\Task;
use App\Services\FocusSessionService;
use App\Services\HabitService;
use App\Services\ProductivityAnalyticsService;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductivityController extends Controller
{
    public function __construct(
        protected HabitService $habitService,
        protected FocusSessionService $focusService,
        protected ProductivityAnalyticsService $analyticsService,
        protected ReviewService $reviewService
    ) {}

    /**
     * Display the central Personal OS dashboard.
     */
    public function index(): View
    {
        $habits = $this->habitService->getHabitsForToday();
        $analytics = $this->analyticsService->getAnalyticsSummary();
        $weeklyReview = $this->reviewService->getWeeklyReview();
        $monthlyReview = $this->reviewService->getMonthlyReview();
        $activeTasks = Task::where('status', '!=', 'completed')->orderBy('deadline')->get();
        $todayFocusMinutes = $this->focusService->getTotalFocusMinutesToday();
        $recentSessions = $this->focusService->getRecentSessions(5);

        return view('productivity.index', compact(
            'habits',
            'analytics',
            'weeklyReview',
            'monthlyReview',
            'activeTasks',
            'todayFocusMinutes',
            'recentSessions'
        ));
    }

    /**
     * Store a completed focus session.
     */
    public function storeFocusSession(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'task_id' => 'nullable|exists:tasks,id',
            'type' => 'required|in:pomodoro,deep_work,custom',
            'duration_minutes' => 'required|integer|min:1|max:1440',
            'started_at' => 'nullable|date',
            'ended_at' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
            'mark_task_completed' => 'nullable|boolean',
        ]);

        $session = $this->focusService->logSession(
            taskId: $validated['task_id'] ?? null,
            type: $validated['type'],
            durationMinutes: $validated['duration_minutes'],
            startedAt: $validated['started_at'] ?? null,
            endedAt: $validated['ended_at'] ?? null,
            notes: $validated['notes'] ?? null,
            markTaskCompleted: ! empty($validated['mark_task_completed'])
        );

        return response()->json([
            'success' => true,
            'message' => 'Sesi fokus berhasil disimpan!',
            'session' => $session,
        ], 201);
    }

    /**
     * Store a new habit / routine.
     */
    public function storeHabit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'icon' => 'nullable|string|max:10',
            'color' => 'nullable|string|max:20',
            'category' => 'nullable|string|in:kesehatan,akademik,produktivitas,pribadi',
            'cadence' => 'required|in:daily,alternate_days,specific_days,weekly_target',
            'specific_days' => 'nullable|array',
            'target_days_per_week' => 'nullable|integer|min:1|max:7',
        ]);

        $habit = Habit::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'icon' => $validated['icon'] ?? '⚡',
            'color' => $validated['color'] ?? '#6366f1',
            'category' => $validated['category'] ?? 'kesehatan',
            'cadence' => $validated['cadence'],
            'specific_days' => $validated['specific_days'] ?? null,
            'target_days_per_week' => $validated['target_days_per_week'] ?? 7,
            'order' => Habit::count() + 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Rutinitas baru berhasil ditambahkan!',
            'habit' => $habit,
        ], 201);
    }

    /**
     * Toggle habit completion checkmark for a given date.
     */
    public function toggleHabit(Request $request, Habit $habit): JsonResponse
    {
        $date = $request->input('date', now()->toDateString());
        $log = $this->habitService->toggleHabit($habit, $date);

        $status = $habit->getStatusForDate($date);
        $streak = $habit->calculateStreak();

        return response()->json([
            'success' => true,
            'is_completed' => $status['is_completed'],
            'status_label' => $status['label'],
            'streak' => $streak,
            'log' => $log,
        ]);
    }

    /**
     * Delete a habit.
     */
    public function destroyHabit(Habit $habit): JsonResponse
    {
        $habit->delete();

        return response()->json([
            'success' => true,
            'message' => 'Rutinitas berhasil dihapus.',
        ]);
    }

    /**
     * Get real-time weekly review data.
     */
    public function weeklyReview(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'review' => $this->reviewService->getWeeklyReview(),
        ]);
    }

    /**
     * Get real-time monthly review data.
     */
    public function monthlyReview(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'review' => $this->reviewService->getMonthlyReview(),
        ]);
    }
}
