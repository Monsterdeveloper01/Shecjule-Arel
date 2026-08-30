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
     * Store a new event.
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
            'file' => 'nullable|file|max:51200',
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $validated['file_path'] = $file->store('uploads/events', 'public');
            $validated['file_name'] = $file->getClientOriginalName();
            $validated['file_size'] = $file->getSize();
        }

        unset($validated['file']);

        $event = Event::create($validated);

        return response()->json(['success' => true, 'event' => $event], 201);
    }

    /**
     * Update an event.
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
            'file' => 'nullable|file|max:51200',
            'remove_file' => 'nullable|in:true,false,1,0',
        ]);

        if ($request->hasFile('file')) {
            $event->deleteAttachmentFile();
            $file = $request->file('file');
            $validated['file_path'] = $file->store('uploads/events', 'public');
            $validated['file_name'] = $file->getClientOriginalName();
            $validated['file_size'] = $file->getSize();
        } elseif ($request->boolean('remove_file')) {
            $event->deleteAttachmentFile();
            $validated['file_path'] = null;
            $validated['file_name'] = null;
            $validated['file_size'] = null;
        }

        unset($validated['file'], $validated['remove_file']);

        $event->update($validated);

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
