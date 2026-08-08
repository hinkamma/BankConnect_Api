<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginOtpNotification extends Notification
{
    use Queueable;

    protected $otpCode;

    public function __construct(string $otpCode)
    {
        $this->otpCode = $otpCode;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Votre code de vérification')
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Voici votre code d'authentification à 6 chiffres pour vous connecter :")
            ->line(" **{$this->otpCode}** ")
            ->line("Ce code est valide pendant 5 minutes. Ne le partagez avec personne.");
    }

    public function toArray($notifiable): array
    {
        return [
            'title'   => 'Code de connexion généré',
            'message' => "Votre code de sécurité est : {$this->otpCode}",
        ];
    }
}