<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoginHistory;
use App\Models\User;
use App\Services\LoginMfaService;
use Illuminate\Http\JsonResponse;
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
            'remember' => 'sometimes|boolean',
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user && $user->isLocked()) {
            throw ValidationException::withMessages([
                'email' => ['Tài khoản đang tạm khóa do đăng nhập sai nhiều lần. Vui lòng thử lại sau ít phút.'],
            ]);
        }

        if (! $user || ! Hash::check($request->password, $user->password)) {
            if ($user) {
                $this->registerFailedLogin($request, $user);
            }

            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        if ($user->requiresMfa()) {
            $user->forceFill([
                'failed_login_count' => 0,
                'locked_until' => null,
            ])->save();

            $challenge = app(LoginMfaService::class)->start($user);

            return response()->json([
                'mfa_required' => true,
                'challenge' => $challenge,
                'expires_in' => max(60, (int) config('ppms.login_mfa_ttl_minutes', 10) * 60),
            ]);
        }

        return $this->issueTokenResponse($request, $user, [
            'via' => 'api_spa',
        ]);
    }

    /**
     * Complete password login after email OTP (when MFA is enabled for the account).
     */
    public function loginMfa(Request $request): JsonResponse
    {
        $request->validate([
            'challenge' => 'required|string|size:48',
            'code' => 'required|string|regex:/^[0-9]{6}$/',
            'remember' => 'sometimes|boolean',
        ]);

        $user = app(LoginMfaService::class)->verify($request->input('challenge'), $request->input('code'));

        if (! $user) {
            throw ValidationException::withMessages([
                'code' => ['Mã xác thực không đúng hoặc đã hết hạn.'],
            ]);
        }

        if ($user->isLocked()) {
            throw ValidationException::withMessages([
                'code' => ['Tài khoản đang tạm khóa. Vui lòng thử lại sau.'],
            ]);
        }

        return $this->issueTokenResponse($request, $user, [
            'via' => 'api_spa_mfa',
        ]);
    }

    /**
     * @param  array<string, mixed>  $historyMeta
     */
    protected function issueTokenResponse(Request $request, User $user, array $historyMeta = []): JsonResponse
    {
        $user->forceFill([
            'failed_login_count' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
            'last_login_ip' => $request->ip() ?: null,
            'last_login_device' => mb_substr((string) $request->userAgent(), 0, 512),
        ])->save();

        $this->writeLoginHistory($request, $user, LoginHistory::EVENT_LOGIN_SUCCESS, $historyMeta);

        $token = $user->createToken('ppms-spa')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    protected function registerFailedLogin(Request $request, User $user): void
    {
        $threshold = max(1, (int) ($user->lockout_threshold ?: 5));
        $count = (int) $user->failed_login_count + 1;

        if ($count >= $threshold) {
            $user->forceFill([
                'failed_login_count' => 0,
                'locked_until' => now()->addMinutes(max(1, (int) config('ppms.lockout_minutes', 15))),
            ])->save();
        } else {
            $user->forceFill(['failed_login_count' => $count])->save();
        }

        $this->writeLoginHistory($request, $user, LoginHistory::EVENT_LOGIN_FAILED, [
            'reason' => 'invalid_password',
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
