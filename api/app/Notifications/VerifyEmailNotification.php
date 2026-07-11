<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyEmailNotification extends Notification
{
    use Queueable;

    public function __construct() {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $hash = sha1($notifiable->getEmailForVerification());
        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
        $verifyUrl = $frontendUrl . '/email/verify?email=' . urlencode($notifiable->getEmailForVerification()) . '&hash=' . $hash;

        return (new MailMessage)
            ->subject('Verifica tu cuenta en Intermedius')
            ->line('Haz clic en el botón para verificar tu dirección de correo electrónico.')
            ->action('Verificar correo', $verifyUrl)
            ->line('Este enlace expira en 60 minutos.')
            ->line('Si no creaste esta cuenta, ignora este mensaje.');
    }
}
