<?php

namespace App\Services;

use App\Models\LoginHistory;
use App\Models\User;
use App\Models\UserInfo;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class UserProfileService
{
    public function profilePayload(User $user): array
    {
        $user->loadMissing('userInfo');

        $info = $user->userInfo;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'avatar_url' => $user->avatar_url,
            'phone' => $info?->phone,
            'address' => $info?->address,
            'profile_updated_at' => $user->profile_updated_at?->toIso8601String(),
            'profile_completeness' => $this->completenessScore($user, $info),
        ];
    }

    /**
     * @param  array{name?: string, phone?: string|null, address?: string|null}  $data
     */
    public function updateProfile(Request $request, User $user, array $data): User
    {
        $conn = $user->getConnectionName();
        DB::connection($conn)->transaction(function () use ($user, $data) {
            if (array_key_exists('name', $data) && is_string($data['name'])) {
                $user->name = $data['name'];
            }
            $user->profile_updated_at = now();
            $user->save();

            $info = UserInfo::query()->firstOrCreate(
                ['user_id' => $user->id],
                ['phone' => null, 'address' => null]
            );

            $patch = Arr::only($data, ['phone', 'address']);
            if ($patch !== []) {
                $info->fill($patch);
                $info->save();
            }
        });

        $this->writeHistory($request, $user, LoginHistory::EVENT_PROFILE_UPDATED, []);

        return $user->fresh(['userInfo']);
    }

    public function storeAvatar(Request $request, User $user, UploadedFile $file): User
    {
        $dir = 'avatars/'.$user->id;
        $path = $file->storePublicly($dir, ['disk' => 'public']);

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->forceFill([
            'avatar_path' => $path,
            'profile_updated_at' => now(),
        ])->save();

        $this->writeHistory($request, $user, LoginHistory::EVENT_PROFILE_UPDATED, ['avatar' => true]);

        return $user->fresh();
    }

    protected function completenessScore(User $user, ?UserInfo $info): int
    {
        $fields = 0;
        $filled = 0;

        $fields++;
        if (trim((string) $user->name) !== '') {
            $filled++;
        }

        $fields++;
        if ($info && trim((string) $info->phone) !== '') {
            $filled++;
        }

        $fields++;
        if ($info && trim((string) $info->address) !== '') {
            $filled++;
        }

        $fields++;
        if ($user->avatar_path) {
            $filled++;
        }

        return (int) round($filled / max(1, $fields) * 100);
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
