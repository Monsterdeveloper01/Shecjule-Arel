<?php

namespace Tests\Feature;

use App\Models\Habit;
use App\Models\Task;
use App\Services\FocusSessionService;
use App\Services\ProductivityAnalyticsService;
use App\Services\ReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductivityPersonalOsTest extends TestCase
{
    use RefreshDatabase;

    public function test_focus_session_logging_and_task_progress_update(): void
    {
        $task = Task::create([
            'title' => 'Tugas Coding Laravel',
            'deadline' => now()->addDays(2),
            'priority' => 'high',
            'status' => 'pending',
            'estimated_duration' => 120,
            'progress' => 10,
        ]);

        $service = app(FocusSessionService::class);
        $session = $service->logSession(
            taskId: $task->id,
            type: 'deep_work',
            durationMinutes: 50,
            notes: 'Fokus pengerjaan module auth',
            markTaskCompleted: false
        );

        $this->assertDatabaseHas('focus_sessions', [
            'id' => $session->id,
            'task_id' => $task->id,
            'duration_minutes' => 50,
            'completed' => true,
        ]);

        $task->refresh();
        $this->assertEquals('in_progress', $task->status);
        $this->assertGreaterThan(10, $task->progress);
    }

    public function test_focus_session_with_mark_task_completed(): void
    {
        $task = Task::create([
            'title' => 'Tugas Desain ERD',
            'deadline' => now()->addDay(),
            'priority' => 'urgent',
            'status' => 'in_progress',
            'estimated_duration' => 60,
            'progress' => 50,
        ]);

        $service = app(FocusSessionService::class);
        $service->logSession(
            taskId: $task->id,
            type: 'pomodoro',
            durationMinutes: 25,
            markTaskCompleted: true
        );

        $task->refresh();
        $this->assertEquals('completed', $task->status);
        $this->assertEquals(100, $task->progress);
        $this->assertNotNull($task->completed_at);
    }

    public function test_habit_alternating_days_workout_and_rest_day_protection(): void
    {
        $habit = Habit::create([
            'title' => 'Latihan Gym / Olahraga',
            'cadence' => 'alternate_days',
            'category' => 'kesehatan',
            'icon' => '🏋️',
        ]);

        // Yesterday user worked out (completed)
        $yesterday = now()->subDay()->toDateString();
        $habit->logs()->create([
            'log_date' => $yesterday,
            'status' => 'completed',
        ]);

        // Today should automatically be recognised as Rest Day
        $statusToday = $habit->getStatusForDate(now());
        $this->assertTrue($statusToday['is_rest_day']);
        $this->assertFalse($statusToday['is_scheduled']);
        $this->assertStringContainsString('Rest Day', $statusToday['label']);

        // Streak should still be active (rest day doesn't break workout streak!)
        $streak = $habit->calculateStreak();
        $this->assertEquals(1, $streak);
    }

    public function test_habit_toggle_checkin(): void
    {
        $habit = Habit::create([
            'title' => 'Membaca Jurnal',
            'cadence' => 'daily',
            'icon' => '📖',
        ]);

        $response = $this->withSession(['authenticated' => true])
            ->postJson("/productivity/habits/{$habit->id}/toggle", [
                'date' => now()->toDateString(),
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('is_completed', true);

        $this->assertDatabaseHas('habit_logs', [
            'habit_id' => $habit->id,
            'log_date' => now()->toDateString(),
            'status' => 'completed',
        ]);

        // Toggle again to uncomplete
        $response2 = $this->withSession(['authenticated' => true])
            ->postJson("/productivity/habits/{$habit->id}/toggle", [
                'date' => now()->toDateString(),
            ]);

        $response2->assertStatus(200);
        $response2->assertJsonPath('is_completed', false);
    }

    public function test_productivity_analytics_requires_sufficient_data_per_rule_27(): void
    {
        $service = app(ProductivityAnalyticsService::class);

        // With 0 tasks
        $summaryEmpty = $service->getAnalyticsSummary();
        $this->assertFalse($summaryEmpty['has_sufficient_data']);
        $this->assertStringContainsString('Sedang mengumpulkan', $summaryEmpty['insights'][0]);

        // Add 3 completed tasks
        for ($i = 1; $i <= 3; $i++) {
            Task::create([
                'title' => "Tugas Selesai {$i}",
                'deadline' => now()->addDays($i),
                'priority' => 'medium',
                'status' => 'completed',
                'completed_at' => now()->subHours($i * 2),
                'progress' => 100,
            ]);
        }

        $summaryReady = $service->getAnalyticsSummary();
        $this->assertTrue($summaryReady['has_sufficient_data']);
        $this->assertGreaterThan(0, count($summaryReady['insights']));
        $this->assertEquals(100.0, $summaryReady['completion_rate']);
    }

    public function test_weekly_review_scorecard_generation(): void
    {
        $service = app(ReviewService::class);
        $review = $service->getWeeklyReview();

        $this->assertArrayHasKey('period_label', $review);
        $this->assertArrayHasKey('tasks_completed', $review);
        $this->assertArrayHasKey('focus_hours', $review);
        $this->assertArrayHasKey('badge', $review);
        $this->assertArrayHasKey('reflection', $review);
        $this->assertNotEmpty($review['reflection']);
    }

    public function test_productivity_controller_stores_focus_session(): void
    {
        $task = Task::create([
            'title' => 'Tugas Test Controller',
            'deadline' => now()->addDay(),
            'priority' => 'medium',
            'status' => 'pending',
            'estimated_duration' => 60,
        ]);

        $response = $this->withSession(['authenticated' => true])
            ->postJson('/productivity/focus-sessions', [
                'task_id' => $task->id,
                'type' => 'pomodoro',
                'duration_minutes' => 25,
                'notes' => 'Sprint 1',
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $this->assertDatabaseHas('focus_sessions', [
            'task_id' => $task->id,
            'duration_minutes' => 25,
        ]);
    }

    public function test_productivity_page_renders(): void
    {
        $response = $this->withSession(['authenticated' => true])
            ->get('/productivity');

        $response->assertStatus(200);
        $response->assertSee('Produktivitas');
        $response->assertSee('Mode Fokus');
    }
}
