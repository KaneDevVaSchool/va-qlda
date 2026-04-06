<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginMfaNotification extends Notification
{
    public function __construct(
        protected string $code
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $minutes = max(1, (int) config('ppms.login_mfa_ttl_minutes', 10));

        return (new MailMessage)
            ->subject(__('Mã đăng nhập PPMS'))
            ->line(__('Mã xác thực đăng nhập của bạn là:'))
            ->line($this->code)
            ->line(__('Mã có hiệu lực trong :minutes phút. Nếu bạn không yêu cầu, hãy bỏ qua email này.', ['minutes' => $minutes]));
    }
}
