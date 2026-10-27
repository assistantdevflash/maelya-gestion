<?php

namespace App\Mail;

use App\Models\Abonnement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RappelAbonnement extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Abonnement $abonnement, public int $joursRestants)
    {
        $this->abonnement->loadMissing(['user', 'plan']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '⏳ Votre abonnement expire dans ' . \App\Helpers\TexteHelper::pluriel($this->joursRestants, 'jour', 'jours') . ' – ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.rappel-abonnement',
        );
    }
}
