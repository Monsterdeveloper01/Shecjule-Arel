<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Services\PriorityEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriorityEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_task_always_has_zero_score(): void
    {
        $task = Task::create([
            'title' => 'Selesai Tugas',
            'deadline' => now()->addDays(2),
            'priority' => 'urgent',
            'status' => 'completed',
            'progress' => 100,
            'estimated_duration' => 120,
        ]);

        $engine = app(PriorityEngine::class);
        $result = $engine->calculate($task);

        $this->assertEquals(0, $result->score);
        $this->assertEquals('RENDAH', $result->level);
        $this->assertStringContainsString('selesai', $result->reason);
    }

    public function test_overdue_task_is_always_critical(): void
    {
        $task = Task::create([
            'title' => 'Tugas Telat',
            'deadline' => now()->subDay(),
            'priority' => 'low',
            'status' => 'pending',
            'progress' => 0,
            'estimated_duration' => 60,
        ]);

        $engine = app(PriorityEngine::class);
        $result = $engine->calculate($task);

        $this->assertGreaterThanOrEqual(80, $result->score);
        $this->assertEquals('KRITIS', $result->level);
        $this->assertStringContainsString('lewat deadline', $result->reason);
    }

    public function test_urgent_task_due_tomorrow_with_zero_progress_is_critical(): void
    {
        $task = Task::create([
            'title' => 'Laporan Akhir Praktikum',
            'deadline' => now()->addHours(12),
            'priority' => 'urgent',
            'status' => 'pending',
            'progress' => 0,
            'estimated_duration' => 240,
        ]);

        $engine = app(PriorityEngine::class);
        $result = $engine->calculate($task);

        $this->assertGreaterThanOrEqual(80, $result->score);
        $this->assertEquals('KRITIS', $result->level);
    }

    public function test_low_priority_task_due_far_future_with_high_progress_is_low(): void
    {
        $task = Task::create([
            'title' => 'Membaca Modul Tambahan',
            'deadline' => now()->addDays(20),
            'priority' => 'low',
            'status' => 'in_progress',
            'progress' => 80,
            'estimated_duration' => 60,
        ]);

        $engine = app(PriorityEngine::class);
        $result = $engine->calculate($task);

        $this->assertLessThan(30, $result->score);
        $this->assertEquals('RENDAH', $result->level);
    }

    public function test_recalculate_and_persist_saves_to_database(): void
    {
        $task = Task::create([
            'title' => 'Tugas Database Baru',
            'deadline' => now()->addHours(6),
            'priority' => 'high',
            'status' => 'pending',
            'progress' => 10,
            'estimated_duration' => 120,
        ]);

        $engine = app(PriorityEngine::class);
        $engine->recalculateAndPersist($task);

        $task->refresh();
        $this->assertNotNull($task->priority_score);
        $this->assertNotNull($task->priority_level);
        $this->assertGreaterThanOrEqual(55, $task->priority_score);
    }

    public function test_batch_recalculate_all_updates_multiple_tasks(): void
    {
        $task1 = Task::create([
            'title' => 'Tugas 1',
            'deadline' => now()->addDay(),
            'priority' => 'urgent',
            'status' => 'pending',
        ]);

        $task2 = Task::create([
            'title' => 'Tugas 2',
            'deadline' => now()->addDays(10),
            'priority' => 'low',
            'status' => 'pending',
        ]);

        $engine = app(PriorityEngine::class);
        $count = $engine->recalculateAllAndPersist();

        $this->assertEquals(2, $count);

        $task1->refresh();
        $task2->refresh();

        $this->assertGreaterThan($task2->priority_score, $task1->priority_score);
    }

    public function test_task_store_endpoint_calculates_priority(): void
    {
        $response = $this->withSession(['authenticated' => true])
            ->postJson('/tasks', [
                'title' => 'Tugas via API',
                'deadline' => now()->addHours(8)->format('Y-m-d H:i:s'),
                'priority' => 'urgent',
                'estimated_duration' => 180,
                'progress' => 0,
            ]);

        $response->assertStatus(201);
        $data = $response->json('task');

        $this->assertNotNull($data['priority_score']);
        $this->assertNotNull($data['priority_level']);
        $this->assertGreaterThanOrEqual(70, $data['priority_score']);
    }

    public function test_task_toggle_status_recalculates_priority(): void
    {
        $task = Task::create([
            'title' => 'Tugas Toggle',
            'deadline' => now()->addDay(),
            'priority' => 'high',
            'status' => 'pending',
            'progress' => 0,
            'estimated_duration' => 60,
        ]);

        $engine = app(PriorityEngine::class);
        $engine->recalculateAndPersist($task);

        $this->withSession(['authenticated' => true])
            ->patchJson("/tasks/{$task->id}/toggle");
        $task->refresh();
        $this->assertEquals('in_progress', $task->status);

        // Toggle again to completed
        $this->withSession(['authenticated' => true])
            ->patchJson("/tasks/{$task->id}/toggle");
        $task->refresh();
        $this->assertEquals('completed', $task->status);
        $this->assertEquals(0, $task->priority_score);
        $this->assertEquals('RENDAH', $task->priority_level);
    }
}
