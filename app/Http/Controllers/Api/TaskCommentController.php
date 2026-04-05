<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskComment;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class TaskCommentController extends Controller
{
    public function index(Task $task)
    {
        return $task->comments()->with('user:id,name')->orderByDesc('id')->limit(200)->get();
    }

    public function store(Request $request, Task $task)
    {
        $data = $request->validate([
            'body' => 'required|string|max:10000',
        ]);

        $comment = $task->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        AuditLogger::log('task_comment.created', $comment, null, $comment->toArray());

        return response()->json($comment->load('user:id,name'), 201);
    }

    public function destroy(Request $request, TaskComment $comment)
    {
        if ($comment->user_id !== $request->user()->id && ! in_array($request->user()->role, ['admin', 'pm', 'tl'], true)) {
            abort(403);
        }

        $comment->delete();

        return response()->noContent();
    }
}
