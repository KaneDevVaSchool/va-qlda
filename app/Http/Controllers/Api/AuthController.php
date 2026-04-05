<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'developer',
        ]);

        $token = $user->createToken('ppms-spa')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            if ($user) {
                $this->writeLoginHistory($request, $user, LoginHistory::EVENT_LOGIN_FAILED, [
                    'reason' => 'invalid_password',
                ]);
            }

            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        $this->writeLoginHistory($request, $user, LoginHistory::EVENT_LOGIN_SUCCESS, [
            'via' => 'api_spa',
        ]);

        $token = $user->createToken('ppms-spa')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    protected function writeLoginHistory(Request $request, User $user, string $event, array $meta = []): void
    {
        try {
            LoginHistory::query()->create([
                'user_id' => $user->id,
                'ip_address' => $request->ip() ?: '0.0.0.0',
                'user_agent' => $request->userAgent(),
                'device_fingerprint' => hash('sha256', ($request->userAgent() ?? '').'|'.($request->ip() ?? '')),
                'event' => $event,
                'meta' => $meta,
            ]);
        } catch (Throwable) {
            // Không chặn đăng nhập nếu bảng audit chưa migrate / lỗi ghi
        }
    }
}
