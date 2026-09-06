<?php

namespace Tests\Feature;

use App\Models\CourseSchedule;
use App\Services\WebPushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchedulePushNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_format_schedule_alert_for_multi_course_day(): void
    {
        CourseSchedule::create([
            'course_name' => 'Algoritma dan Pemrograman',
            'course_code' => 'CS101',
            'sks' => 4,
            'day_of_week' => 1,
            'start_time' => '10:30',
            'end_time' => '14:30',
            'room' => 'KU3.02.04',
            'delivery_mode' => 'offline',
        ]);

        CourseSchedule::create([
            'course_name' => 'Pengantar Sistem Informasi',
            'course_code' => 'IS101',
            'sks' => 3,
            'day_of_week' => 1,
            'start_time' => '14:30',
            'end_time' => '17:30',
            'room' => 'KU3.03.07',
            'delivery_mode' => 'offline',
        ]);

        $service = app(WebPushService::class);
        $result = $service->formatScheduleAlert(1, 'tomorrow');

        $this->assertNotNull($result);
        $this->assertEquals(1, $result['day_of_week']);
        $this->assertEquals('Senin', $result['day_name']);
        $this->assertEquals(2, $result['count']);
        $this->assertEquals(7, $result['total_sks']);
        $this->assertEquals('📚 Pengingat Kuliah Besok: Senin (2 Matkul)', $result['title']);
        $this->assertStringContainsString('1. Algoritma dan Pemrograman (10:30 - 14:30 WIB) [📍 KU3.02.04]', $result['body']);
        $this->assertStringContainsString('2. Pengantar Sistem Informasi (14:30 - 17:30 WIB) [📍 KU3.03.07]', $result['body']);
    }

    public function test_send_schedule_test_endpoint(): void
    {
        CourseSchedule::create([
            'course_name' => 'Algoritma dan Pemrograman',
            'course_code' => 'CS101',
            'sks' => 4,
            'day_of_week' => 1,
            'start_time' => '10:30',
            'end_time' => '14:30',
            'room' => 'KU3.02.04',
            'delivery_mode' => 'offline',
        ]);

        $response = $this->withSession(['authenticated' => true, 'user_name' => 'Farel'])
            ->postJson('/push/test-schedule', [
                'day_of_week' => 1,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'title',
                'body',
                'message',
            ]);
    }
}
