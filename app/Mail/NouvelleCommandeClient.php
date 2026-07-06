<?php

namespace App\Mail;

use App\Models\Commande;
use App\Models\Institut;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NouvelleCommandeClient extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Commande $commande,
        public Institut $institut
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Confirmation de commande {$this->commande->numero}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.commande.nouvelle-client',
        );
    }
}
