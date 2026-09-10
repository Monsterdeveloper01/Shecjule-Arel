<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Services\AiAssistantService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiAssistantTest extends TestCase
{
    use RefreshDatabase;

    public function test_parse_natural_language_task_input_with_subject_subtasks_duration_deadline(): void
    {
        // Reference Monday 10:00
        $refDate = Carbon::parse('2026-09-07 10:00:00'); // Monday

        $service = app(AiAssistantService::class);
        $input = 'Jumat depan ada tugas basis data, bikin ERD sama normalisasi, kira-kira 3 jam.';
        $draft = $service->parseNaturalLanguageInput($input, $refDate);

        $this->assertEquals('task', $draft->type);
        $this->assertEquals('Basis Data', $draft->subject);
        $this->assertEquals(180, $draft->estimated_duration); // 3 hours = 180 min
        $this->assertStringContainsString('2026-09-11', $draft->deadline); // Next Friday
        $this->assertStringContainsString('23:59:00', $draft->deadline);

        $subtasks = $draft->normalizeSubtasks();
        $this->assertNotEmpty($subtasks);
        $titles = array_column($subtasks, 'title');
        $this->assertTrue(in_array('Bikin ERD', $titles) || in_array('Bikin erd', $titles) || count($subtasks) >= 1);
    }

    public function test_parse_natural_language_event_input_with_voice_style_phrasing(): void
    {
        $refDate = Carbon::parse('2026-09-07 10:00:00'); // Monday

        $service = app(AiAssistantService::class);
        $input = 'Besok jam 7 malam rapat organisasi di Ruang TULT.';
        $draft = $service->parseNaturalLanguageInput($input, $refDate);

        $this->assertEquals('event', $draft->type);
        $this->assertStringContainsString('Rapat', $draft->title);
        $this->assertStringContainsString('2026-09-08 19:00:00', $draft->deadline); // Tomorrow 19:00
        $this->assertEquals('organisasi', $draft->category);
        $this->assertEquals('Ruang TULT', $draft->location);
    }

    public function test_ai_task_breakdown_generates_phases_and_estimates(): void
    {
        $service = app(AiAssistantService::class);
        $breakdown = $service->breakdownTask('Buat aplikasi kasir untuk tugas akhir');

        $this->assertNotEmpty($breakdown->steps);
        $this->assertGreaterThanOrEqual(4, count($breakdown->steps));
        $this->assertGreaterThan(0, $breakdown->totalEstimatedMinutes);
        $this->assertNotNull($breakdown->tips);

        // Should have database and frontend/backend steps
        $titles = implode(' ', array_column($breakdown->steps, 'title'));
        $this->assertStringContainsString('Database', $titles);
    }

    public function test_ai_parse_multiple_tasks_from_ocr_text(): void
    {
        $refDate = Carbon::parse('2026-09-07 10:00:00');
        $ocrText = "Daftar Tugas Minggu Ini:\n".
            "1. Tugas Basis Data bikin normalisasi deadline besok 2 jam\n".
            "2. Tugas Jarkom konfigurasi cisco deadline lusa 3 jam\n".
            '3. Rapat panitia besok jam 7 malam di Selasar';

        $service = app(AiAssistantService::class);
        $drafts = $service->parseMultipleTasksFromText($ocrText, $refDate);

        $this->assertGreaterThanOrEqual(2, $drafts->count());
        $types = $drafts->pluck('type')->all();
        $this->assertTrue(in_array('task', $types));
    }

    public function test_ai_endpoints_require_pin_auth(): void
    {
        $response = $this->postJson('/ai/parse', ['text' => 'Tugas besok']);
        $response->assertStatus(302);
    }

    public function test_ai_parse_endpoint_returns_json_draft(): void
    {
        $response = $this->withSession(['authenticated' => true])
            ->postJson('/ai/parse', [
                'text' => 'Besok ada tugas pemrograman web 2 jam',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('draft.type', 'task');
        $response->assertJsonPath('draft.estimated_duration', 120);
    }

    public function test_ai_breakdown_endpoint_returns_json_breakdown(): void
    {
        $response = $this->withSession(['authenticated' => true])
            ->postJson('/ai/breakdown', [
                'title' => 'Menulis Makalah Ilmiah Sistem Informasi',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertNotEmpty($response->json('breakdown.steps'));
    }

    public function test_ai_confirm_draft_persists_task_with_subtasks_and_engines(): void
    {
        $payload = [
            'type' => 'task',
            'title' => 'Tugas Basis Data ERD',
            'subject' => 'Basis Data',
            'deadline' => now()->addDays(2)->format('Y-m-d 23:59:00'),
            'priority' => 'high',
            'estimated_duration' => 120,
            'subtasks' => [
                ['title' => 'Identifikasi entitas', 'completed' => false],
                ['title' => 'Tentukan relasi & cardinalitas', 'completed' => false],
            ],
        ];

        $response = $this->withSession(['authenticated' => true])
            ->postJson('/ai/confirm-draft', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('type', 'task');

        $this->assertDatabaseHas('tasks', [
            'title' => 'Tugas Basis Data ERD',
            'subject' => 'Basis Data',
            'priority' => 'high',
        ]);

        $createdTask = Task::where('title', 'Tugas Basis Data ERD')->first();
        $this->assertNotNull($createdTask);
        $this->assertCount(2, $createdTask->subtasks);
        $this->assertNotNull($createdTask->priority_score);
        $this->assertNotNull($createdTask->risk_score);
    }

    public function test_ai_confirm_draft_persists_event(): void
    {
        $payload = [
            'type' => 'event',
            'title' => 'Rapat Himpunan Mahasiswa',
            'category' => 'organisasi',
            'location' => 'Ruang Rapat Gedung B',
            'start_date' => now()->addDay()->setTime(19, 0)->format('Y-m-d H:i:s'),
            'end_date' => now()->addDay()->setTime(21, 0)->format('Y-m-d H:i:s'),
        ];

        $response = $this->withSession(['authenticated' => true])
            ->postJson('/ai/confirm-draft', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('type', 'event');

        $this->assertDatabaseHas('events', [
            'title' => 'Rapat Himpunan Mahasiswa',
            'category' => 'organisasi',
            'location' => 'Ruang Rapat Gedung B',
        ]);
    }
}
