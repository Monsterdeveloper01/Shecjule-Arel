<?php

namespace App\Http\Controllers;

use App\Models\CourseSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseScheduleController extends Controller
{
    /**
     * Display the weekly timetable matrix and course schedule manager.
     */
    public function index(Request $request): View
    {
        $schedules = CourseSchedule::with('attachments')
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        $schedulesByDay = [];
        for ($day = 1; $day <= 7; $day++) {
            $schedulesByDay[$day] = $schedules->where('day_of_week', $day)->values();
        }

        $totalSks = (int) $schedules->sum('sks');
        $totalCourses = $schedules->count();
        $activeDays = $schedules->pluck('day_of_week')->unique()->count();

        $today = now()->dayOfWeekIso;
        $todaySchedules = $schedulesByDay[$today] ?? collect();

        $ongoingSchedule = $todaySchedules->first(fn (CourseSchedule $s) => $s->is_ongoing_now);
        $nextSchedule = $todaySchedules->first(fn (CourseSchedule $s) => $s->minutes_until_start !== null && $s->minutes_until_start > 0);

        return view('schedules.index', [
            'schedules' => $schedules,
            'schedulesByDay' => $schedulesByDay,
            'totalSks' => $totalSks,
            'totalCourses' => $totalCourses,
            'activeDays' => $activeDays,
            'today' => $today,
            'todaySchedules' => $todaySchedules,
            'ongoingSchedule' => $ongoingSchedule,
            'nextSchedule' => $nextSchedule,
            'days' => CourseSchedule::DAYS,
        ]);
    }

    /**
     * Store a newly created course schedule.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'course_name' => 'required|string|max:255',
            'course_code' => 'nullable|string|max:50',
            'sks' => 'required|integer|min:1|max:10',
            'class_code' => 'nullable|string|max:50',
            'lecturer_name' => 'nullable|string|max:255',
            'day_of_week' => 'required|integer|min:1|max:7',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room' => 'nullable|string|max:100',
            'delivery_mode' => 'required|in:offline,online,hybrid',
            'meeting_link' => 'nullable|url|max:500',
            'color_tag' => 'required|string|max:30',
            'notes' => 'nullable|string',
            'files' => 'nullable|array',
            'files.*' => 'file|max:51200',
        ]);

        unset($validated['files']);

        $schedule = CourseSchedule::create($validated);

        if ($request->hasFile('files')) {
            $schedule->saveAttachments($request->file('files'), 'uploads/schedules');
        }

        $schedule->load('attachments');

        return response()->json(['success' => true, 'schedule' => $schedule], 201);
    }

    /**
     * Update the specified course schedule.
     */
    public function update(Request $request, CourseSchedule $schedule): JsonResponse
    {
        $validated = $request->validate([
            'course_name' => 'required|string|max:255',
            'course_code' => 'nullable|string|max:50',
            'sks' => 'required|integer|min:1|max:10',
            'class_code' => 'nullable|string|max:50',
            'lecturer_name' => 'nullable|string|max:255',
            'day_of_week' => 'required|integer|min:1|max:7',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room' => 'nullable|string|max:100',
            'delivery_mode' => 'required|in:offline,online,hybrid',
            'meeting_link' => 'nullable|url|max:500',
            'color_tag' => 'required|string|max:30',
            'notes' => 'nullable|string',
            'files' => 'nullable|array',
            'files.*' => 'file|max:51200',
            'deleted_attachment_ids' => 'nullable|array',
            'deleted_attachment_ids.*' => 'integer',
        ]);

        if ($request->filled('deleted_attachment_ids')) {
            $schedule->deleteAttachmentsByIds($request->input('deleted_attachment_ids'));
        }

        if ($request->hasFile('files')) {
            $schedule->saveAttachments($request->file('files'), 'uploads/schedules');
        }

        unset($validated['files'], $validated['deleted_attachment_ids']);

        $schedule->update($validated);
        $schedule->load('attachments');

        return response()->json(['success' => true, 'schedule' => $schedule]);
    }

    /**
     * Remove the specified course schedule.
     */
    public function destroy(CourseSchedule $schedule): JsonResponse
    {
        $schedule->delete();

        return response()->json(['success' => true]);
    }
}
