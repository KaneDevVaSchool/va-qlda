<?php

namespace App\Services;

use App\Models\ActiveSession;
use App\Models\LoginHistory;
use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Support\UserAgentParser;
use Illuminate\Http\Request;
use Throwable;

class SessionDeviceService
{
    public const ACTIVITY_ONLINE_MINUTES = 5;

    public function registerSession(User $user, PersonalAccessToken $token, Request $request, ?string $previousLoginIp = null): ActiveSession
    {
        $parsed = UserAgentParser::parse($request->userAgent());
        $ip = $request->ip() ?: '0.0.0.0';

        $suspicious = $this->detectSuspiciousLogin($previousLoginIp, $ip);

        ActiveSession::query()->where('user_id', $user->id)->update(['is_current' => false]);

        $row = ActiveSession::query()->firstOrNew([
            'personal_access_token_id' => $token->id,
        ]);

        $row->user_id = $user->id;
        $row->session_id_hash = hash('sha256', 'pat:'.$token->id);
        $row->ip_address = $ip;
        $row->user_agent = $request->userAgent();
        $row->browser = $parsed['browser'];
        $row->os = $parsed['os'];
        $row->device_name = $parsed['device_name'];
        $row->device_label = $parsed['label'];
        $row->is_current = true;
        $row->is_suspicious = $suspicious;
        $row->last_activity_at = now();
        $row->login_at = $row->login_at ?? now();
        $row->logout_at = null;
        $row->expires_at = $token->expires_at;
        $row->save();

        if ($suspicious) {
            $this->writeSuspiciousHistory($request, $user);
        }

        return $row;
    }

    public function logoutToken(int $personalAccessTokenId): void
    {
        $session = ActiveSession::query()
            ->where('personal_access_token_id', $personalAccessTokenId)
            ->first();

        if ($session) {
            $session->forceFill(['logout_at' => now(), 'is_current' => false])->save();
        }

        PersonalAccessToken::query()->where('id', $personalAccessTokenId)->delete();
    }

    public function revokeSession(User $user, int $activeSessionId): void
    {
        $session = ActiveSession::query()
            ->where('user_id', $user->id)
            ->where('id', $activeSessionId)
            ->firstOrFail();

        if ($session->personal_access_token_id) {
            $this->logoutToken((int) $session->personal_access_token_id);
        } else {
            $session->forceFill(['logout_at' => now(), 'is_current' => false])->save();
        }
    }

    public function revokeOthers(User $user, int $currentTokenId): int
    {
        $tokens = PersonalAccessToken::query()
            ->where('tokenable_id', $user->id)
            ->where('tokenable_type', User::class)
            ->where('id', '!=', $currentTokenId)
            ->pluck('id');

        $count = 0;
        foreach ($tokens as $tid) {
            $this->logoutToken((int) $tid);
            $count++;
        }

        return $count;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listDevices(User $user, ?int $currentTokenId): array
    {
        $rows = ActiveSession::query()
            ->where('user_id', $user->id)
            ->whereNull('logout_at')
            ->orderByDesc('last_activity_at')
            ->get();

        $out = [];
        foreach ($rows as $s) {
            $tokenExists = $s->personal_access_token_id
                && PersonalAccessToken::query()->where('id', $s->personal_access_token_id)->exists();

            if (! $tokenExists) {
                $s->forceFill(['logout_at' => now()])->save();

                continue;
            }

            $status = $this->resolveStatus($s, $tokenExists);

            $out[] = [
                'id' => $s->id,
                'device_name' => $s->device_name ?: ($s->device_label ?: '—'),
                'os' => $s->os ?: '—',
                'browser' => $s->browser ?: '—',
                'ip_address' => $s->ip_address,
                'status' => $status,
                'is_current_device' => $s->personal_access_token_id === $currentTokenId,
                'is_suspicious' => (bool) $s->is_suspicious,
                'login_at' => $s->login_at?->toIso8601String(),
                'last_activity' => $s->last_activity_at?->toIso8601String(),
                'logout_at' => $s->logout_at?->toIso8601String(),
                'personal_access_token_id' => $s->personal_access_token_id,
            ];
        }

        return $out;
    }

    public function touchActivity(int $personalAccessTokenId): void
    {
        ActiveSession::query()
            ->where('personal_access_token_id', $personalAccessTokenId)
            ->whereNull('logout_at')
            ->update(['last_activity_at' => now()]);
    }

    protected function resolveStatus(ActiveSession $s, bool $tokenExists): string
    {
        if ($s->is_suspicious) {
            return 'suspicious';
        }
        if (! $tokenExists) {
            return 'offline';
        }
        if ($s->expires_at && $s->expires_at->isPast()) {
            return 'offline';
        }
        $cutoff = now()->subMinutes(self::ACTIVITY_ONLINE_MINUTES);
        if ($s->last_activity_at && $s->last_activity_at->gte($cutoff)) {
            return 'online';
        }

        return 'offline';
    }

    protected function detectSuspiciousLogin(?string $previousLoginIp, string $ip): bool
    {
        if ($previousLoginIp === null || $previousLoginIp === '') {
            return false;
        }

        return $previousLoginIp !== $ip;
    }

    protected function writeSuspiciousHistory(Request $request, User $user): void
    {
        try {
            LoginHistory::query()->create([
                'user_id' => $user->id,
                'ip_address' => $request->ip() ?: '0.0.0.0',
                'user_agent' => $request->userAgent(),
                'device_fingerprint' => hash('sha256', ($request->userAgent() ?? '').'|'.($request->ip() ?? '')),
                'event' => LoginHistory::EVENT_SUSPICIOUS_ACTIVITY,
                'meta' => ['reason' => 'ip_change'],
                'is_suspicious' => true,
            ]);
        } catch (Throwable) {
            //
        }
    }
}
