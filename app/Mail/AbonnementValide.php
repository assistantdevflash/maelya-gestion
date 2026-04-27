<?php

namespace App\Mail;

use App\Models\Abonnement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AbonnementValide extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Abonnement $abonnement)
    {
        $this->abonnement->loadMissing(['user', 'plan', 'validePar']);
    }

    public function envelope(): Envelope
    {
        $client = $this->abonnement->user;
        $plan   = $this->abonnement->plan;
        $prenom = $client->prenom ?: $client->name;

        return new Envelope(
            subject: "✅ Votre abonnement {$plan->nom} est activé – " . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.abonnement-valide',
        );
    }
}
