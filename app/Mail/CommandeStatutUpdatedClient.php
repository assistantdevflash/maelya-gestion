<?php

namespace App\Mail;

use App\Models\Commande;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CommandeStatutUpdatedClient extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Commande $commande
    ) {
        $this->commande->loadMissing('institut');
    }

    public function envelope(): Envelope
    {
        $statutLabels = [
            'nouvelle' => 'Reçue',
            'acceptee' => 'Acceptée',
            'en_preparation' => 'En préparation',
            'en_livraison' => 'En livraison',
            'livree' => 'Livrée',
            'annulee' => 'Annulée',
            'refusee' => 'Refusée',
        ];

        $statutLabel = $statutLabels[$this->commande->statut] ?? $this->commande->statut;

        return new Envelope(
            subject: "Commande {$this->commande->numero} - {$statutLabel}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.commande.statut-updated-client',
        );
    }
}
