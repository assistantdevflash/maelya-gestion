<?php

namespace App\Mail;

use App\Models\RendezVous;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NouveauRdvVitrineEtablissement extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public RendezVous $rdv)
    {
        $this->rdv->loadMissing(['prestations', 'institut']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🔔 Nouvelle demande de RDV — ' . $this->rdv->client_nom . ' · ' . $this->rdv->debut_le->format('d/m/Y à H\hi'),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.rdv.nouvelle-demande-etablissement');
    }
}
