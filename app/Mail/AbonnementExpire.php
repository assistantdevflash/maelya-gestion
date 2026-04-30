<?php

namespace App\Mail;

use App\Models\Abonnement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AbonnementExpire extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Abonnement $abonnement)
    {
        $this->abonnement->loadMissing(['user', 'plan']);
    }

    public function envelope(): Envelope
    {
        $prenom = $this->abonnement->user->prenom ?: $this->abonnement->user->name;

        return new Envelope(
            subject: "⚠️ Votre abonnement a expiré – " . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.abonnement-expire',
        );
    }
}
