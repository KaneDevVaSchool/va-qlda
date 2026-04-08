<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserSecurityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class UserSecurityController extends Controller
{
    public function show(Request $request, UserSecurityService $securityService): JsonResponse
    {
        return response()->json($securityService->summary($request->user()));
    }

    public function password(Request $request, UserSecurityService $securityService): JsonResponse
    {
        $data = $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $securityService->changePassword($request, $request->user(), $data['current_password'], $data['password']);

        return response()->json(['message' => 'Password updated.']);
    }
}
