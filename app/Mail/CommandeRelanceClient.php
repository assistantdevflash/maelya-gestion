<?php

namespace App\Mail;

use App\Models\Commande;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/**
 * Relance d'une commande restée sans suite (panier abandonné).
 */
class CommandeRelanceClient extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Commande $commande)
    {
        $this->commande->loadMissing(['institut', 'items']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre commande ' . $this->commande->numero . ' est en attente – '
                . ($this->commande->institut?->nom ?? config('app.name')),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.commande.relance-client',
            with: [
                'commande' => $this->commande,
                'lien'     => URL::signedRoute('shop.suivi', [
                    'slug'   => $this->commande->institut->slug,
                    'numero' => $this->commande->numero,
                ]),
            ],
        );
    }
}
