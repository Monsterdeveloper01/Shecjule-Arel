<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NoteController extends Controller
{
    /**
     * Display all notes (pinned first).
     */
    public function index(): View
    {
        $notes = Note::orderBy('is_pinned', 'desc')
            ->orderBy('updated_at', 'desc')
            ->get();

        return view('notes.index', compact('notes'));
    }

    /**
     * Store a new note.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'note_date' => 'nullable|date',
            'file' => 'nullable|file|max:51200',
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $validated['file_path'] = $file->store('uploads/notes', 'public');
            $validated['file_name'] = $file->getClientOriginalName();
            $validated['file_size'] = $file->getSize();
        }

        unset($validated['file']);

        $note = Note::create($validated);

        return response()->json(['success' => true, 'note' => $note], 201);
    }

    /**
     * Update a note.
     */
    public function update(Request $request, Note $note): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'note_date' => 'nullable|date',
            'file' => 'nullable|file|max:51200',
            'remove_file' => 'nullable|in:true,false,1,0',
        ]);

        if ($request->hasFile('file')) {
            $note->deleteAttachmentFile();
            $file = $request->file('file');
            $validated['file_path'] = $file->store('uploads/notes', 'public');
            $validated['file_name'] = $file->getClientOriginalName();
            $validated['file_size'] = $file->getSize();
        } elseif ($request->boolean('remove_file')) {
            $note->deleteAttachmentFile();
            $validated['file_path'] = null;
            $validated['file_name'] = null;
            $validated['file_size'] = null;
        }

        unset($validated['file'], $validated['remove_file']);

        $note->update($validated);

        return response()->json(['success' => true, 'note' => $note]);
    }

    /**
     * Delete a note.
     */
    public function destroy(Note $note): JsonResponse
    {
        $note->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Toggle pin status for a note.
     */
    public function togglePin(Note $note): JsonResponse
    {
        $note->update(['is_pinned' => ! $note->is_pinned]);

        return response()->json(['success' => true, 'note' => $note]);
    }
}
