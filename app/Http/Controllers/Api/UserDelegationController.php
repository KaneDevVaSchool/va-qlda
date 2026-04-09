<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDelegation;
use App\Services\UserRbacService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserDelegationController extends Controller
{
    private const SCOPES = ['supplier_contracts', 'projects', 'all'];

    public function index(Request $request, UserRbacService $rbac): JsonResponse
    {
        $user = $request->user();
        $asAdmin = $rbac->isPermissionAdmin($user);

        $delegatorId = (int) $user->id;
        if ($asAdmin && $request->filled('delegator_id')) {
            $did = (int) $request->query('delegator_id');
            if ($did < 1 || ! User::query()->whereKey($did)->exists()) {
                abort(422, 'Delegator not found.');
            }
            $delegatorId = $did;
        }

        $rows = UserDelegation::query()
            ->where('delegator_id', $delegatorId)
            ->with('delegatee:id,name,email,role')
            ->orderByDesc('created_at')
            ->get()
            ->sortBy(fn (UserDelegation $d) => $d->is_expired ? 1 : 0)
            ->values();

        return response()->json([
            'scopes' => self::SCOPES,
            'can_manage_others' => $asAdmin,
            'view_delegator_id' => $delegatorId,
            'items' => $rows,
        ]);
    }

    public function store(Request $request, UserRbacService $rbac): JsonResponse
    {
        $user = $request->user();

        $delegatorId = (int) $user->id;
        if ($rbac->isPermissionAdmin($user) && $request->filled('delegator_id')) {
            $delegatorId = (int) $request->input('delegator_id');
            if ($delegatorId < 1 || ! User::query()->whereKey($delegatorId)->exists()) {
                abort(422, 'Delegator not found.');
            }
        }

        $data = $request->validate([
            'delegatee_id' => ['required', 'integer', Rule::notIn([$delegatorId])],
            'scope' => ['required', 'string', Rule::in(self::SCOPES)],
            'expires_at' => ['nullable', 'date'],
        ]);

        $delegateeId = (int) $data['delegatee_id'];

        if (! User::query()->whereKey($delegateeId)->exists()) {
            abort(422, 'User not found.');
        }

        $delegation = UserDelegation::query()->updateOrCreate(
            [
                'delegator_id' => $delegatorId,
                'delegatee_id' => $delegateeId,
            ],
            [
                'scope' => $data['scope'],
                'expires_at' => $data['expires_at'] ?? null,
            ],
        );

        $delegation->load('delegatee:id,name,email,role');

        return response()->json($delegation, 201);
    }

    public function destroy(Request $request, UserRbacService $rbac, UserDelegation $delegation): JsonResponse
    {
        $user = $request->user();

        if ($delegation->delegator_id !== $user->id && ! $rbac->isPermissionAdmin($user)) {
            abort(403);
        }

        $delegation->delete();

        return response()->json(['ok' => true]);
    }
}
