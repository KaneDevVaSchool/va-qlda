<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserProfileController extends Controller
{
    public function show(Request $request, UserProfileService $profileService): JsonResponse
    {
        return response()->json($profileService->profilePayload($request->user()));
    }

    public function update(Request $request, UserProfileService $profileService): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:32|regex:/^[0-9+().\\-\\s]*$/',
            'address' => 'nullable|string|max:500',
        ]);

        $user = $profileService->updateProfile($request, $request->user(), $data);

        return response()->json($profileService->profilePayload($user));
    }

    public function avatar(Request $request, UserProfileService $profileService): JsonResponse
    {
        $maxKb = max(1, (int) config('ppms.upload_max_file_kb', 51200));
        $request->validate([
            'avatar' => 'required|image|max:'.$maxKb,
        ]);

        $user = $profileService->storeAvatar($request, $request->user(), $request->file('avatar'));

        return response()->json($profileService->profilePayload($user));
    }
}
