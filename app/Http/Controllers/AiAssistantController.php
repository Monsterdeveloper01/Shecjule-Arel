<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Task;
use App\Services\AiAssistantService;
use App\Services\DeadlineRiskEngine;
use App\Services\PriorityEngine;
use App\Services\TodayIntelligenceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiAssistantController extends Controller
{
    /**
     * Parse natural language text or voice transcript into a structured draft.
     */
    public function parseInput(Request $request, AiAssistantService $aiService): JsonResponse
    {
        $validated = $request->validate([
            'text' => 'required|string|max:1500',
        ]);

        $draft = $aiService->parseNaturalLanguageInput($validated['text']);

        return response()->json([
            'success' => true,
            'draft' => $draft->toArray(),
        ]);
    }

    /**
     * Break down a complex task into actionable subtasks with time estimates.
     */
    public function breakdown(Request $request, AiAssistantService $aiService): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1500',
        ]);

        $breakdown = $aiService->breakdownTask($validated['title'], $validated['description'] ?? null);

        return response()->json([
            'success' => true,
            'breakdown' => $breakdown->toArray(),
        ]);
    }

    /**
     * Parse multiple task drafts from raw OCR or chat screenshot text.
     */
    public function parseOcr(Request $request, AiAssistantService $aiService): JsonResponse
    {
        $validated = $request->validate([
            'text' => 'required|string|max:10000',
        ]);

        $drafts = $aiService->parseMultipleTasksFromText($validated['text']);

        return response()->json([
            'success' => true,
            'count' => $drafts->count(),
            'drafts' => $drafts->map->toArray()->values()->all(),
        ]);
    }

    /**
     * Confirm and store an AI-generated draft into the database (Rule 9 compliance).
     */
    public function confirmDraft(
        Request $request,
        PriorityEngine $priorityEngine,
        DeadlineRiskEngine $riskEngine
    ): JsonResponse {
        $validated = $request->validate([
            'type' => 'required|in:task,event',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'subject' => 'nullable|string|max:255',
            'deadline' => 'nullable|date',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'priority' => 'nullable|in:urgent,high,medium,low',
            'estimated_duration' => 'nullable|integer|min:1|max:9999',
            'subtasks' => 'nullable|array',
            'location' => 'nullable|string|max:255',
            'category' => 'nullable|in:kuliah,ujian,seminar,organisasi,pribadi',
        ]);

        if ($validated['type'] === 'event') {
            $startDate = $validated['start_date'] ?? $validated['deadline'] ?? now()->addDay()->format('Y-m-d 09:00:00');
            $endDate = $validated['end_date'] ?? (clone Carbon::parse($startDate))->addHour()->format('Y-m-d H:i:s');

            $event = Event::create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'location' => $validated['location'] ?? null,
                'category' => $validated['category'] ?? 'organisasi',
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

            return response()->json([
                'success' => true,
                'type' => 'event',
                'message' => 'Acara berhasil disimpan ke kalender!',
                'data' => $event,
            ], 201);
        }

        // Store Task
        $deadline = $validated['deadline'] ?? now()->addDay()->format('Y-m-d 23:59:00');
        $priority = $validated['priority'] ?? 'medium';
        $duration = $validated['estimated_duration'] ?? 60;

        $task = Task::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'subject' => $validated['subject'] ?? null,
            'deadline' => $deadline,
            'priority' => $priority,
            'status' => 'pending',
            'estimated_duration' => $duration,
            'progress' => 0,
            'subtasks' => $validated['subtasks'] ?? [],
        ]);

        $priorityEngine->recalculateAndPersist($task);
        $riskEngine->recalculateAndPersist($task);

        return response()->json([
            'success' => true,
            'type' => 'task',
            'message' => 'Tugas berhasil disimpan!',
            'data' => $task,
        ], 201);
    }

    /**
     * Get real-time AI daily insight briefing.
     */
    public function dailyInsight(TodayIntelligenceService $todayService, AiAssistantService $aiService): JsonResponse
    {
        $hub = $todayService->getTodayHub();
        $insight = $aiService->generateDailyInsight($hub);

        return response()->json([
            'success' => true,
            'insight' => $insight,
        ]);
    }
}
