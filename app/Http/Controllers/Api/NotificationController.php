<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PpmsNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return PpmsNotification::query()
            ->where('recipient_id', $request->user()->id)
            ->orderByDesc('id')
            ->paginate(40);
    }

    public function markRead(Request $request, PpmsNotification $notification)
    {
        if ($notification->recipient_id !== $request->user()->id) {
            abort(403);
        }

        $notification->update(['read_at' => now()]);

        return $notification->fresh();
    }

    public function markAllRead(Request $request)
    {
        PpmsNotification::query()
            ->where('recipient_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
