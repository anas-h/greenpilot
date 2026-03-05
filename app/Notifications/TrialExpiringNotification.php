<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrialExpiringNotification extends Notification
{
    use Queueable;

    public function __construct(
        private int $daysRemaining
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject($this->daysRemaining > 0
                ? "Votre essai gratuit expire dans {$this->daysRemaining} jours"
                : 'Votre essai gratuit a expire')
            ->greeting('Bonjour,');

        if ($this->daysRemaining > 0) {
            $message->line("Votre essai gratuit GreenPilot expire dans {$this->daysRemaining} jours.")
                ->line('Pour continuer a utiliser toutes les fonctionnalites, souscrivez un abonnement des maintenant.')
                ->action('Souscrire un abonnement', config('app.url').'/abonnement')
                ->line("Sans abonnement, votre compte passera en mode lecture seule a l'expiration de l'essai.");
        } else {
            $message->line('Votre essai gratuit GreenPilot a expire.')
                ->line('Votre compte est maintenant en mode lecture seule. Vous pouvez toujours consulter vos donnees, mais vous ne pouvez plus creer ou modifier des elements.')
                ->action('Souscrire un abonnement', config('app.url').'/abonnement')
                ->line('Souscrivez un abonnement pour retrouver un acces complet.');
        }

        return $message;
    }
}
