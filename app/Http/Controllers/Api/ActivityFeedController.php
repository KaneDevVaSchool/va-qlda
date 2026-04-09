<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityFeedItemResource;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\ActivityFeed\ActivityFeedService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ActivityFeedController extends Controller
{
    public function index(Request $request, ActivityFeedService $feed)
    {
        $filters = $request->validate([
            'subject_type' => 'nullable|in:project,contract',
            'activity_kind' => 'nullable|in:created,updated,deleted,file_upload,status_change,assign_po,assign_project',
            'user_id' => ['nullable', 'integer', Rule::exists(User::class, 'id')],
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'q' => 'nullable|string|max:200',
            'per_page' => 'nullable|integer|min:5|max:100',
            'before_id' => 'nullable|integer',
        ]);

        $perPage = min(100, max(5, (int) ($filters['per_page'] ?? 25)));
        $beforeId = isset($filters['before_id']) ? (int) $filters['before_id'] : null;
        unset($filters['per_page'], $filters['before_id']);

        $result = $feed->paginate($request->user(), $filters, $perPage, $beforeId);

        return response()->json([
            'data' => ActivityFeedItemResource::collection($result['data']),
            'next_cursor' => $result['next_cursor'],
            'has_more' => $result['has_more'],
        ]);
    }

    public function unreadCount(Request $request, ActivityFeedService $feed)
    {
        return response()->json([
            'unread' => $feed->unreadCount($request->user()),
        ]);
    }

    public function markRead(Request $request, ActivityFeedService $feed, AuditLog $auditLog)
    {
        if (! $feed->userCanSeeLog($request->user(), $auditLog)) {
            abort(403);
        }

        $feed->markRead($request->user(), $auditLog);

        return response()->json(['ok' => true]);
    }

    public function markAllRead(Request $request, ActivityFeedService $feed)
    {
        $filters = $request->validate([
            'subject_type' => 'nullable|in:project,contract',
            'activity_kind' => 'nullable|in:created,updated,deleted,file_upload,status_change,assign_po,assign_project',
            'user_id' => ['nullable', 'integer', Rule::exists(User::class, 'id')],
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'q' => 'nullable|string|max:200',
        ]);

        $count = $feed->markAllRead($request->user(), $filters);

        return response()->json(['ok' => true, 'marked' => $count]);
    }
}
