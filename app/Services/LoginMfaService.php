<?php

namespace App\Services;

use App\Models\OtpCode;
use App\Models\User;
use App\Notifications\LoginMfaNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LoginMfaService
{
    protected function challengeCacheKey(string $challenge): string
    {
        return 'ppms:login_mfa:'.sha1($challenge);
    }

    /**
     * Create OTP + challenge token; send notification with plain code.
     */
    public function start(User $user): string
    {
        OtpCode::query()
            ->where('user_id', $user->id)
            ->where('purpose', OtpCode::PURPOSE_LOGIN_MFA)
            ->whereNull('used_at')
            ->delete();

        $code = $this->generateNumericCode();

        $otp = OtpCode::query()->create([
            'user_id' => $user->id,
            'email' => $user->email,
            'purpose' => OtpCode::PURPOSE_LOGIN_MFA,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(max(1, (int) config('ppms.login_mfa_ttl_minutes', 10))),
            'max_attempts' => 5,
        ]);

        $challenge = Str::random(48);
        Cache::put(
            $this->challengeCacheKey($challenge),
            $otp->id,
            now()->addMinutes(15)
        );

        $user->notify(new LoginMfaNotification($code));

        return $challenge;
    }

    /**
     * @return User|null Authenticated user if code valid; otherwise null.
     */
    public function verify(string $challenge, string $code): ?User
    {
        if (strlen($challenge) !== 48 || ! preg_match('/^\d{6}$/', $code)) {
            return null;
        }

        $cacheKey = $this->challengeCacheKey($challenge);
        $otpId = Cache::get($cacheKey);
        if (! $otpId) {
            return null;
        }

        /** @var OtpCode|null $otp */
        $otp = OtpCode::query()->find($otpId);
        if (! $otp || $otp->purpose !== OtpCode::PURPOSE_LOGIN_MFA) {
            return null;
        }

        if ($otp->isConsumed() || $otp->isExpired() || $otp->hasExceededAttempts()) {
            return null;
        }

        if (! Hash::check($code, $otp->code_hash)) {
            $otp->increment('attempts');

            return null;
        }

        $otp->forceFill(['used_at' => now()])->save();
        Cache::forget($cacheKey);

        return User::query()->find($otp->user_id);
    }

    protected function generateNumericCode(): string
    {
        return sprintf('%06d', random_int(0, 999999));
    }
}
