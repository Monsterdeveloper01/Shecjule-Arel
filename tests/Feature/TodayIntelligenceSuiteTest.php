<?php

namespace Tests\Feature;

use App\Models\CourseSchedule;
use App\Models\Task;
use App\Services\FreeTimeService;
use App\Services\ScheduleOverloadService;
use App\Services\ScheduleRecommendationService;
use App\Services\SmartReminderService;
use App\Services\TodayIntelligenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodayIntelligenceSuiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_free_time_service_detects_slots_between_classes(): void
    {
        $dayOfWeek = 1; // Monday
        $testDate = now()->next('Monday')->setTime(8, 0);

        // Class 1: 08:30 - 10:30
        CourseSchedule::create([
            'course_name' => 'Kuliah Pagi',
            'course_code' => 'KP101',
            'sks' => 2,
            'day_of_week' => $dayOfWeek,
            'start_time' => '08:30',
            'end_time' => '10:30',
        ]);

        // Class 2: 13:00 - 15:00
        CourseSchedule::create([
            'course_name' => 'Kuliah Siang',
            'course_code' => 'KS101',
            'sks' => 2,
            'day_of_week' => $dayOfWeek,
            'start_time' => '13:00',
            'end_time' => '15:00',
        ]);

        $service = app(FreeTimeService::class);
        $slots = $service->getFreeSlotsForDate($testDate, minDurationMinutes: 30);

        // Expect free slot between 10:30 and 13:00 (150 minutes = 2.5 hours)
        $gapSlot = $slots->first(fn ($s) => $s->durationMinutes == 150);
        $this->assertNotNull($gapSlot);
        $this->assertStringContainsString('10:30', $gapSlot->formattedRange);
        $this->assertStringContainsString('13:00', $gapSlot->formattedRange);
    }

    public function test_schedule_recommendation_matches_task_to_free_slot(): void
    {
        $testDate = now()->next('Tuesday')->setTime(8, 0);

        // Create high risk urgent task
        $task = Task::create([
            'title' => 'Tugas Prioritas Tinggi',
            'deadline' => now()->addDay(),
            'priority' => 'urgent',
            'status' => 'pending',
            'progress' => 0,
            'estimated_duration' => 90,
            'priority_score' => 90,
            'risk_level' => 'KRITIS',
            'risk_score' => 88,
        ]);

        $service = app(ScheduleRecommendationService::class);
        $recommendations = $service->getRecommendationsForDate($testDate);

        $this->assertNotEmpty($recommendations);
        $rec = $recommendations->first();
        $this->assertEquals($task->id, $rec->task->id);
        $this->assertGreaterThan(0, $rec->recommendedDurationMinutes);
    }

    public function test_smart_reminder_service_generates_contextual_alerts(): void
    {
        Task::create([
            'title' => 'Tugas Mendesak',
            'deadline' => now()->addHours(6),
            'priority' => 'urgent',
            'status' => 'pending',
            'progress' => 10,
            'estimated_duration' => 180,
            'risk_level' => 'KRITIS',
            'risk_score' => 92,
        ]);

        $service = app(SmartReminderService::class);
        $reminders = $service->getPendingSmartReminders();

        $this->assertNotEmpty($reminders);
        $first = $reminders->first();
        $this->assertStringContainsString('Tugas Mendesak', $first['title']);
        $this->assertStringContainsString('KRITIS', $first['body']);
    }

    public function test_schedule_overload_service_calculates_daily_burden(): void
    {
        $today = now();

        // 3 classes today
        for ($i = 1; $i <= 3; $i++) {
            CourseSchedule::create([
                'course_name' => "Matkul {$i}",
                'course_code' => "MK{$i}",
                'sks' => 3,
                'day_of_week' => $today->dayOfWeekIso,
                'start_time' => sprintf('%02d:00', 8 + ($i * 3)),
                'end_time' => sprintf('%02d:30', 10 + ($i * 3)),
            ]);
        }

        $service = app(ScheduleOverloadService::class);
        $analysis = $service->analyzeDate($today);

        $this->assertEquals(3, $analysis->classCount);
        $this->assertNotEmpty($analysis->headline);
        $this->assertNotEmpty($analysis->recommendation);
        $this->assertTrue(in_array($analysis->level, ['MODERAT', 'TINGGI', 'KRITIS'], true));
    }

    public function test_today_intelligence_service_bundles_all_hub_data(): void
    {
        $hubService = app(TodayIntelligenceService::class);
        $hub = $hubService->getTodayHub();

        $this->assertArrayHasKey('overload', $hub);
        $this->assertArrayHasKey('nextUp', $hub);
        $this->assertArrayHasKey('freeSlots', $hub);
        $this->assertArrayHasKey('totalFreeHoursToday', $hub);
        $this->assertArrayHasKey('recommendation', $hub);
        $this->assertArrayHasKey('highRiskTasks', $hub);
        $this->assertArrayHasKey('aiInsight', $hub);
        $this->assertNotEmpty($hub['aiInsight']);
    }

    public function test_dashboard_renders_today_intelligence_hub(): void
    {
        $response = $this->withSession(['authenticated' => true])
            ->get('/');

        $response->assertStatus(200);
        $response->assertSee('Today Intelligence Hub');
        $response->assertSee('Status Beban:');
        $response->assertSee('Total Waktu Luang Hari Ini:');
    }
}
