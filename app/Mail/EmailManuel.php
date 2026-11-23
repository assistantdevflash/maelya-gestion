<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email rédigé depuis l'espace super-admin.
 *
 * Le prénom est passé en chaîne et non en modèle User : la campagne peut
 * cibler une adresse libre qui n'a pas de compte (mode « email personnalisé »),
 * et la file d'envoi doit rester sérialisable sans dépendance en base.
 */
class EmailManuel extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $sujet,
        public string $corps,
        public string $prenom = ''
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->sujet,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.email-manuel',
        );
    }
}
