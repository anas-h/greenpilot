<?php

namespace App\Notifications;

use App\Models\Alerte;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EnlevementRappelNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Alerte $alerte
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[GreenPilot] Rappel - Enlevement prevu prochainement')
            ->greeting('Rappel d\'enlevement')
            ->line('**'.$this->alerte->titre.'**')
            ->line($this->alerte->message)
            ->line('---')
            ->line('**Checklist de preparation:**')
            ->line('- Verifier que les conteneurs concernes sont prets')
            ->line('- S\'assurer que les BSD sont crees et signes')
            ->line('- Preparer les documents d\'accompagnement')
            ->line('- Verifier l\'accessibilite de la zone de stockage')
            ->action('Voir les details', url('/enlevements'))
            ->line('Pensez a preparer les conteneurs et les documents necessaires.')
            ->salutation('L\'equipe GreenPilot');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'alerte_id' => $this->alerte->id,
            'type' => $this->alerte->type,
            'titre' => $this->alerte->titre,
            'message' => $this->alerte->message,
        ];
    }
}
