<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TaskAttachmentController extends Controller
{
    public function index(Task $task)
    {
        return $task->attachments()->with('uploader:id,name')->orderByDesc('id')->get();
    }

    public function store(Request $request, Task $task)
    {
        $request->validate([
            'file' => 'required|file|max:10240',
        ]);

        $file = $request->file('file');
        $path = $file->store('task-attachments', 'public');

        $attachment = $task->attachments()->create([
            'uploaded_by' => $request->user()->id,
            'disk' => 'public',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'size_bytes' => $file->getSize(),
        ]);

        AuditLogger::log('task_attachment.created', $attachment, null, $attachment->toArray());

        return response()->json($attachment->load('uploader:id,name'), 201);
    }

    public function download(TaskAttachment $attachment)
    {
        if (! Storage::disk($attachment->disk)->exists($attachment->path)) {
            abort(404);
        }

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }

    public function destroy(Request $request, TaskAttachment $attachment)
    {
        if ($attachment->uploaded_by !== $request->user()->id && ! in_array($request->user()->role, ['admin', 'pm', 'tl'], true)) {
            abort(403);
        }

        Storage::disk($attachment->disk)->delete($attachment->path);
        $attachment->delete();

        return response()->noContent();
    }
}
