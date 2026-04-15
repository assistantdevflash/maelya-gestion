<?php

namespace App\Mail;

use App\Models\Abonnement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NouvelleDemandeAbonnement extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Abonnement $abonnement)
    {
        $this->abonnement->loadMissing(['user', 'plan']);
    }

    public function envelope(): Envelope
    {
        $client = $this->abonnement->user;
        $plan   = $this->abonnement->plan;

        return new Envelope(
            subject: "🔔 Nouvelle demande d'abonnement – {$client->nom_complet} ({$plan->nom})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.nouvelle-demande-abonnement',
        );
    }
}
