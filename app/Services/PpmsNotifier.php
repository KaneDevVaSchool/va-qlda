<?php

namespace App\Services;

use App\Mail\PpmsAlertMail;
use App\Models\PpmsNotification;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class PpmsNotifier
{
    /**
     * @param  list<string>  $channels  in_app, email
     */
    public function notify(int $userId, string $type, string $title, string $body, array $channels = ['in_app'], bool $dedupeDay = false): void
    {
        if ($dedupeDay && $this->recentlyNotified($userId, $type)) {
            return;
        }

        if (in_array('in_app', $channels, true)) {
            PpmsNotification::query()->create([
                'type' => $type,
                'recipient_id' => $userId,
                'channel' => 'in_app',
                'payload' => ['title' => $title, 'body' => $body],
                'sent_at' => now(),
            ]);
        }

        if (in_array('email', $channels, true)) {
            $user = User::query()->find($userId);
            if ($user) {
                Mail::mailer(config('mail.default'))->to($user->email)->send(new PpmsAlertMail($title, $body));
            }
        }
    }

    protected function recentlyNotified(int $userId, string $type): bool
    {
        return PpmsNotification::query()
            ->where('recipient_id', $userId)
            ->where('type', $type)
            ->where('created_at', '>', now()->subDay())
            ->exists();
    }
}
