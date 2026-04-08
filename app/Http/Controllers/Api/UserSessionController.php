<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoginHistory;
use App\Services\SessionDeviceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class UserSessionController extends Controller
{
    public function index(Request $request, SessionDeviceService $sessions): JsonResponse
    {
        $tokenId = $request->user()->currentAccessToken()?->id;

        return response()->json([
            'devices' => $sessions->listDevices($request->user(), $tokenId ? (int) $tokenId : null),
        ]);
    }

    public function destroy(Request $request, int $session, SessionDeviceService $sessions): JsonResponse
    {
        $sessions->revokeSession($request->user(), $session);

        $this->writeHistory($request, $request->user()->id, LoginHistory::EVENT_SESSION_REVOKED, [
            'session_id' => $session,
        ]);

        return response()->json(['message' => 'Session revoked.']);
    }

    public function revokeOthers(Request $request, SessionDeviceService $sessions): JsonResponse
    {
        $tokenId = $request->user()->currentAccessToken()?->id;
        if (! $tokenId) {
            return response()->json(['message' => 'No active token.'], 422);
        }

        $n = $sessions->revokeOthers($request->user(), (int) $tokenId);

        $this->writeHistory($request, $request->user()->id, LoginHistory::EVENT_SESSION_REVOKED, [
            'revoke_others' => true,
            'count' => $n,
        ]);

        return response()->json(['message' => 'Other sessions revoked.', 'count' => $n]);
    }

    protected function writeHistory(Request $request, int $userId, string $event, array $meta): void
    {
        try {
            LoginHistory::query()->create([
                'user_id' => $userId,
                'ip_address' => $request->ip() ?: '0.0.0.0',
                'user_agent' => $request->userAgent(),
                'device_fingerprint' => hash('sha256', ($request->userAgent() ?? '').'|'.($request->ip() ?? '')),
                'event' => $event,
                'meta' => $meta,
            ]);
        } catch (Throwable) {
            //
        }
    }
}
