<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventController extends Controller
{
    /**
     * Display all events.
     */
    public function index(Request $request): View
    {
        $query = Event::query();

        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }

        $events = $query->orderBy('start_date', 'desc')->get();

        return view('events.index', compact('events'));
    }

    /**
     * Store a new event with multiple file attachments.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'category' => 'required|in:kuliah,ujian,seminar,organisasi,pribadi',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'files' => 'nullable|array',
            'files.*' => 'file|max:51200',
        ]);

        unset($validated['files']);

        $event = Event::create($validated);

        if ($request->hasFile('files')) {
            $event->saveAttachments($request->file('files'), 'uploads/events');
        }

        $event->load('attachments');

        return response()->json(['success' => true, 'event' => $event], 201);
    }

    /**
     * Update an event with attachment management.
     */
    public function update(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'category' => 'required|in:kuliah,ujian,seminar,organisasi,pribadi',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'files' => 'nullable|array',
            'files.*' => 'file|max:51200',
            'deleted_attachment_ids' => 'nullable|array',
            'deleted_attachment_ids.*' => 'integer',
        ]);

        if ($request->filled('deleted_attachment_ids')) {
            $event->deleteAttachmentsByIds($request->input('deleted_attachment_ids'));
        }

        if ($request->hasFile('files')) {
            $event->saveAttachments($request->file('files'), 'uploads/events');
        }

        unset($validated['files'], $validated['deleted_attachment_ids']);

        $event->update($validated);
        $event->load('attachments');

        return response()->json(['success' => true, 'event' => $event]);
    }

    /**
     * Delete an event.
     */
    public function destroy(Event $event): JsonResponse
    {
        $event->delete();

        return response()->json(['success' => true]);
    }
}
