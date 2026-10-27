<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class RappelAnniversaireAdmin extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Collection $clients,
        public int $jours,
        public string $institutNom,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🎂 ' . \App\Helpers\TexteHelper::pluriel($this->clients->count(), 'anniversaire', 'anniversaires') . ' dans ' . \App\Helpers\TexteHelper::pluriel($this->jours, 'jour', 'jours') . ' – ' . $this->institutNom,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.clients.rappel-anniversaire');
    }
}
