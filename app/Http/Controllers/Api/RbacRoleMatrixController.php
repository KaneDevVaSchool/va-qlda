<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserRbacService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RbacRoleMatrixController extends Controller
{
    public function show(Request $request, UserRbacService $rbac): JsonResponse
    {
        if (! $rbac->isPermissionAdmin($request->user())) {
            abort(403);
        }

        return response()->json([
            'matrix' => $rbac->roleMatrix(),
            'modules' => config('ppms_rbac.modules'),
            'actions' => config('ppms_rbac.actions'),
            'roles' => array_keys(config('ppms_rbac.roles', [])),
            'roles_with_custom_matrix' => $rbac->rolesWithCustomMatrix(),
        ]);
    }

    public function update(Request $request, string $role, UserRbacService $rbac): JsonResponse
    {
        if (! $rbac->isPermissionAdmin($request->user())) {
            abort(403);
        }

        $allowedRoles = array_keys(config('ppms_rbac.roles', []));
        if (! in_array($role, $allowedRoles, true)) {
            abort(422, 'Invalid role.');
        }

        $data = $request->validate([
            'entries' => 'required|array',
        ]);

        $keys = $rbac->allPermissionKeys();
        foreach ($data['entries'] as $permKey => $v) {
            if (! in_array($permKey, $keys, true)) {
                return response()->json(['message' => 'Unknown permission key: '.$permKey], 422);
            }
            if (! is_bool($v)) {
                return response()->json(['message' => 'Each entry must be boolean.'], 422);
            }
        }

        foreach ($keys as $k) {
            if (! array_key_exists($k, $data['entries'])) {
                return response()->json(['message' => 'Missing permission key: '.$k], 422);
            }
        }

        $rbac->syncRoleMatrixFromUi($role, $data['entries']);

        return response()->json([
            'matrix' => $rbac->roleMatrix(),
            'roles_with_custom_matrix' => $rbac->rolesWithCustomMatrix(),
        ]);
    }

    public function destroy(Request $request, string $role, UserRbacService $rbac): JsonResponse
    {
        if (! $rbac->isPermissionAdmin($request->user())) {
            abort(403);
        }

        $allowedRoles = array_keys(config('ppms_rbac.roles', []));
        if (! in_array($role, $allowedRoles, true)) {
            abort(422, 'Invalid role.');
        }

        $rbac->clearRoleMatrix($role);

        return response()->json([
            'matrix' => $rbac->roleMatrix(),
            'roles_with_custom_matrix' => $rbac->rolesWithCustomMatrix(),
        ]);
    }
}
