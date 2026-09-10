<?php

namespace Tests\Feature;

use App\Models\CourseSchedule;
use App\Models\Event;
use App\Models\Task;
use App\Services\DeadlineRiskEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeadlineRiskEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_task_is_always_safe(): void
    {
        $task = Task::create([
            'title' => 'Tugas Selesai',
            'deadline' => now()->addHours(2),
            'priority' => 'urgent',
            'status' => 'completed',
            'progress' => 100,
            'estimated_duration' => 180,
        ]);

        $engine = app(DeadlineRiskEngine::class);
        $result = $engine->calculate($task);

        $this->assertEquals(0, $result->score);
        $this->assertEquals('AMAN', $result->level);
        $this->assertStringContainsString('selesai', $result->reason);
    }

    public function test_overdue_task_is_always_critical(): void
    {
        $task = Task::create([
            'title' => 'Tugas Telat',
            'deadline' => now()->subHours(5),
            'priority' => 'low',
            'status' => 'pending',
            'progress' => 20,
            'estimated_duration' => 60,
        ]);

        $engine = app(DeadlineRiskEngine::class);
        $result = $engine->calculate($task);

        $this->assertEquals(100, $result->score);
        $this->assertEquals('KRITIS', $result->level);
        $this->assertStringContainsString('melewati', $result->reason);
    }

    public function test_task_with_more_remaining_work_than_available_time_is_high_or_critical(): void
    {
        // 8 hours remaining work, but deadline is in 2 hours
        $task = Task::create([
            'title' => 'Tugas Sangat Mepet',
            'deadline' => now()->addHours(2),
            'priority' => 'high',
            'status' => 'pending',
            'progress' => 0,
            'estimated_duration' => 480, // 8 hours
        ]);

        $engine = app(DeadlineRiskEngine::class);
        $result = $engine->calculate($task);

        $this->assertGreaterThanOrEqual(70, $result->score);
        $this->assertTrue(in_array($result->level, ['TINGGI', 'KRITIS'], true));
        $this->assertGreaterThanOrEqual(1.0, $result->feasibilityRatio);
    }

    public function test_abundant_available_time_yields_safe_risk(): void
    {
        // 1 hour remaining work, deadline is in 10 days
        $task = Task::create([
            'title' => 'Tugas Santai',
            'deadline' => now()->addDays(10),
            'priority' => 'low',
            'status' => 'in_progress',
            'progress' => 50,
            'estimated_duration' => 120, // remaining 1 hour
        ]);

        $engine = app(DeadlineRiskEngine::class);
        $result = $engine->calculate($task);

        $this->assertLessThan(40, $result->score);
        $this->assertTrue(in_array($result->level, ['AMAN', 'RENDAH'], true));
    }

    public function test_course_schedule_reduces_available_time(): void
    {
        $engine = app(DeadlineRiskEngine::class);

        $now = now()->next('Monday')->setTime(8, 0);
        $deadline = $now->copy()->setTime(18, 0); // 10 hours window (08:00 - 18:00 = 600 minutes)

        $minutesBeforeClass = $engine->calculateAvailableWorkMinutes($now, $deadline);

        // Add 4-hour class on Monday from 10:00 to 14:00 (240 minutes)
        CourseSchedule::create([
            'course_name' => 'Pemrograman Web',
            'course_code' => 'CS201',
            'sks' => 4,
            'day_of_week' => 1, // Monday
            'start_time' => '10:00',
            'end_time' => '14:00',
        ]);

        $minutesAfterClass = $engine->calculateAvailableWorkMinutes($now, $deadline);

        $this->assertEquals(240, $minutesBeforeClass - $minutesAfterClass);
    }

    public function test_event_reduces_available_time(): void
    {
        $engine = app(DeadlineRiskEngine::class);

        $start = now()->addDays(2)->setTime(8, 0);
        $deadline = $start->copy()->setTime(18, 0);

        $minutesBeforeEvent = $engine->calculateAvailableWorkMinutes($start, $deadline);

        // Add 3-hour event on that date from 13:00 to 16:00 (180 minutes)
        Event::create([
            'title' => 'Seminar Teknologi',
            'start_date' => $start->copy()->setTime(13, 0),
            'end_date' => $start->copy()->setTime(16, 0),
            'category' => 'seminar',
        ]);

        $minutesAfterEvent = $engine->calculateAvailableWorkMinutes($start, $deadline);

        $this->assertEquals(180, $minutesBeforeEvent - $minutesAfterEvent);
    }

    public function test_recalculate_and_persist_saves_risk_fields(): void
    {
        $task = Task::create([
            'title' => 'Tugas Test Persist',
            'deadline' => now()->addDays(3),
            'priority' => 'medium',
            'status' => 'pending',
            'progress' => 0,
            'estimated_duration' => 120,
        ]);

        $engine = app(DeadlineRiskEngine::class);
        $engine->recalculateAndPersist($task);

        $task->refresh();
        $this->assertNotNull($task->risk_score);
        $this->assertNotNull($task->risk_level);
    }

    public function test_task_api_store_and_toggle_persists_risk(): void
    {
        $response = $this->withSession(['authenticated' => true])
            ->postJson('/tasks', [
                'title' => 'Tugas via API with Risk',
                'deadline' => now()->addHours(4)->format('Y-m-d H:i:s'),
                'priority' => 'urgent',
                'estimated_duration' => 240,
                'progress' => 0,
            ]);

        $response->assertStatus(201);
        $data = $response->json('task');

        $this->assertNotNull($data['risk_score']);
        $this->assertNotNull($data['risk_level']);

        $taskId = $data['id'];

        // Toggle twice to completed
        $this->withSession(['authenticated' => true])->patchJson("/tasks/{$taskId}/toggle");
        $this->withSession(['authenticated' => true])->patchJson("/tasks/{$taskId}/toggle");

        $task = Task::find($taskId);
        $this->assertEquals('completed', $task->status);
        $this->assertEquals(0, $task->risk_score);
        $this->assertEquals('AMAN', $task->risk_level);
    }

    public function test_tasks_index_filter_by_risk_level(): void
    {
        $taskKritis = Task::create([
            'title' => 'Tugas Super Kritis',
            'deadline' => now()->addHour(),
            'priority' => 'urgent',
            'status' => 'pending',
            'estimated_duration' => 300,
            'risk_level' => 'KRITIS',
            'risk_score' => 95,
        ]);

        $taskAman = Task::create([
            'title' => 'Tugas Super Aman',
            'deadline' => now()->addDays(15),
            'priority' => 'low',
            'status' => 'pending',
            'estimated_duration' => 60,
            'risk_level' => 'AMAN',
            'risk_score' => 5,
        ]);

        $response = $this->withSession(['authenticated' => true])
            ->get('/tasks?risk_level=KRITIS');

        $response->assertStatus(200);
        $response->assertSee('Tugas Super Kritis');
        $response->assertDontSee('Tugas Super Aman');
    }
}
