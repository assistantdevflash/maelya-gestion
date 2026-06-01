<?php

namespace App\Mail;

use App\Models\AvisClient;
use App\Models\RendezVous;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AvisDemande extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public AvisClient $avis, public RendezVous $rdv) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Donnez-nous votre avis 🌸');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.avis-demande',
            with: [
                'avis' => $this->avis,
                'rdv'  => $this->rdv,
                'lien' => route('public.avis.show', $this->avis->token),
            ],
        );
    }
}
