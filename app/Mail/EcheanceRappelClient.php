<?php

namespace App\Mail;

use App\Models\Echeance;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EcheanceRappelClient extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Echeance $echeance)
    {
        $this->echeance->loadMissing(['credit.client', 'credit.vente.items', 'credit.institut']);
    }

    public function envelope(): Envelope
    {
        $client = $this->echeance->credit->client;
        return new Envelope(
            subject: 'Rappel – echeance de credit demain – ' . ($this->echeance->credit->institut?->nom ?? config('app.name')),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.credits.rappel-client');
    }
}
