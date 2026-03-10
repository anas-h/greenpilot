<?php

namespace App\Notifications;

use App\Models\Alerte;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AlerteCritiqueNotification extends Notification
{
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
        $prioriteLabel = match ($this->alerte->priorite) {
            'critique' => 'CRITIQUE',
            'haute' => 'HAUTE',
            default => strtoupper($this->alerte->priorite),
        };

        $typeLabel = match ($this->alerte->type) {
            'conteneur_95' => 'Conteneur plein',
            'stockage_11mois' => 'Limite de stockage',
            'autorisation_expiree' => 'Autorisation expiree',
            'bsd_refused' => 'BSD refuse',
            'bsd_sent_15j' => 'BSD en transit prolonge',
            'fds_manquante' => 'FDS manquante',
            default => $this->alerte->type,
        };

        return (new MailMessage)
            ->subject("[GreenPilot] Alerte {$prioriteLabel} - {$typeLabel}")
            ->greeting('Alerte '.$prioriteLabel)
            ->line('**'.$this->alerte->titre.'**')
            ->line($this->alerte->message)
            ->line('---')
            ->line('**Type:** '.$typeLabel)
            ->line('**Priorite:** '.$prioriteLabel)
            ->line('**Date:** '.$this->alerte->created_at->format('d/m/Y a H:i'))
            ->action('Voir les alertes', url('/alertes'))
            ->line('Cette alerte necessite une action immediate de votre part.')
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
            'priorite' => $this->alerte->priorite,
            'titre' => $this->alerte->titre,
            'message' => $this->alerte->message,
        ];
    }
}
