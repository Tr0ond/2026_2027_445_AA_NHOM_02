<?php

namespace App\Notifications;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $token,
        public string $email,
        public CarbonInterface $expiresAt,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $baseUrl = rtrim((string) config('auth.password_reset_url'), '/');
        $url = $baseUrl.'?token='.rawurlencode($this->token).'&email='.rawurlencode($this->email);

        return (new MailMessage)
            ->subject('Đặt lại mật khẩu EduPortal')
            ->greeting('Xin chào!')
            ->line('Hệ thống nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn.')
            ->action('Đặt lại mật khẩu', $url)
            ->line('Liên kết có hiệu lực đến '.$this->expiresAt->timezone(config('app.timezone'))->format('H:i d/m/Y').'.')
            ->line('Nếu bạn không gửi yêu cầu này, hãy bỏ qua email.');
    }
}
