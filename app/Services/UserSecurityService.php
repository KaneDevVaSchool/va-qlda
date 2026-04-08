<?php

namespace App\Services;

use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Throwable;

class UserSecurityService
{
    /**
     * @return array{risk: string, last_login_at: string|null, last_login_ip: string|null, last_login_device: string|null, password_expired: bool, weak_password_hint: bool}
     */
    public function summary(User $user): array
    {
        $risk = 'safe';
        if ($user->isPasswordExpired()) {
            $risk = 'high';
        } elseif ($user->failed_login_count > 0) {
            $risk = 'medium';
        }

        return [
            'risk' => $risk,
            'last_login_at' => $user->last_login_at?->toIso8601String(),
            'last_login_ip' => $user->last_login_ip,
            'last_login_device' => $user->last_login_device,
            'password_expired' => $user->isPasswordExpired(),
            'weak_password_hint' => false,
        ];
    }

    /**
     * @throws ValidationException
     */
    public function changePassword(Request $request, User $user, string $current, string $new): void
    {
        if (! Hash::check($current, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Mật khẩu hiện tại không đúng.'],
            ]);
        }

        $user->forceFill([
            'password' => $new,
            'password_changed_at' => now(),
            'must_change_password' => false,
        ])->save();

        $this->writeHistory($request, $user, LoginHistory::EVENT_PASSWORD_CHANGED, []);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    protected function writeHistory(Request $request, User $user, string $event, array $meta): void
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
            //
        }
    }
}
