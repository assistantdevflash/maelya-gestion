<?php

namespace App\Mail;

use App\Models\RendezVous;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RdvAnnuleClient extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public RendezVous $rdv)
    {
        $this->rdv->loadMissing(['prestations', 'institut']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '❌ Votre rendez-vous a été annulé – ' . ($this->rdv->institut?->nom ?? config('app.name')),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.rdv.annule-client');
    }
}
