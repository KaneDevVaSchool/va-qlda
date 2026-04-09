<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserRbacService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserRoleController extends Controller
{
    public function update(Request $request, User $user, UserRbacService $rbac): JsonResponse
    {
        if (! $rbac->isPermissionAdmin($request->user())) {
            abort(403);
        }

        $allowed = array_keys(config('ppms_rbac.roles', []));
        if ($allowed === []) {
            abort(500, 'No roles configured.');
        }

        $data = $request->validate([
            'role' => ['required', 'string', Rule::in($allowed)],
        ]);

        $user->role = $data['role'];
        $user->save();

        return response()->json([
            'id' => $user->id,
            'role' => $user->role,
        ]);
    }
}
