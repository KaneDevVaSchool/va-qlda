<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDelegation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserDelegationController extends Controller
{
    private const SCOPES = ['supplier_contracts', 'projects', 'all'];

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $rows = UserDelegation::query()
            ->where('delegator_id', $user->id)
            ->with('delegatee:id,name,email,role')
            ->orderByDesc('created_at')
            ->get()
            ->sortBy(fn (UserDelegation $d) => $d->is_expired ? 1 : 0)
            ->values();

        return response()->json([
            'scopes' => self::SCOPES,
            'items' => $rows,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'delegatee_id' => ['required', 'integer', Rule::notIn([$user->id])],
            'scope' => ['required', 'string', Rule::in(self::SCOPES)],
            'expires_at' => ['nullable', 'date'],
        ]);

        $delegateeId = (int) $data['delegatee_id'];

        if (! User::query()->whereKey($delegateeId)->exists()) {
            abort(422, 'User not found.');
        }

        $delegation = UserDelegation::query()->updateOrCreate(
            [
                'delegator_id' => $user->id,
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

    public function destroy(Request $request, UserDelegation $delegation): JsonResponse
    {
        $user = $request->user();

        if ($delegation->delegator_id !== $user->id) {
            abort(403);
        }

        $delegation->delete();

        return response()->json(['ok' => true]);
    }
}
