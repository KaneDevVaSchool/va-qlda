<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoginHistory;
use App\Services\UserRbacService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class UserRbacController extends Controller
{
    public function show(Request $request, UserRbacService $rbac): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'role' => $user->role,
            'role_options' => array_keys(config('ppms_rbac.roles', [])),
            'permission_admin_roles' => config('ppms_rbac.permission_admin_roles', ['admin']),
            'modules' => config('ppms_rbac.modules'),
            'actions' => config('ppms_rbac.actions'),
            'matrix' => $rbac->roleMatrix(),
            'effective' => $rbac->effectivePermissions($user),
            'overrides' => $rbac->overridesFor($user),
            'can_manage' => $rbac->isPermissionAdmin($user),
        ]);
    }

    public function update(Request $request, UserRbacService $rbac): JsonResponse
    {
        $user = $request->user();
        if (! $rbac->isPermissionAdmin($user)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $data = $request->validate([
            'overrides' => 'required|array',
            'overrides.*.permission_key' => 'required|string|max:96',
            'overrides.*.granted' => 'required|boolean',
            'overrides.*.expires_at' => 'nullable|date',
        ]);

        $rbac->syncOverrides($user, $data['overrides']);

        $this->writeHistory($request, $user->id, LoginHistory::EVENT_PERMISSION_UPDATED, []);

        return response()->json([
            'effective' => $rbac->effectivePermissions($user->fresh()),
            'overrides' => $rbac->overridesFor($user->fresh()),
        ]);
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
