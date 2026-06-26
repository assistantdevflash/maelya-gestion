<?php

namespace App\Mail;

use App\Models\Echeance;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EcheanceRappelEtablissement extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Echeance $echeance)
    {
        $this->echeance->loadMissing(['credit.client', 'credit.vente.items']);
    }

    public function envelope(): Envelope
    {
        $client = $this->echeance->credit->client;
        return new Envelope(
            subject: 'Rappel – echeance credit demain – ' . ($client?->nom_complet ?? 'Client') . ' · ' . number_format($this->echeance->montant, 0, ',', ' ') . ' FCFA',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.credits.rappel-etablissement');
    }
}
