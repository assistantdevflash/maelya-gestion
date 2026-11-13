<?php

namespace App\Mail;

use App\Models\Facture;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Relance d'une facture impayée envoyée au client.
 *
 * Le message est personnalisable par établissement ; à défaut, un texte
 * standard rappelle le montant restant dû et la date d'échéance.
 */
class FactureARelancer extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * ⚠️ La propriété ne doit pas s'appeler « message » : Laravel injecte
     * toujours une variable $message (instance Illuminate\Mail\Message) dans
     * les vues Mail, ce qui masque la nôtre et casse le rendu.
     */
    public function __construct(public Facture $facture, public ?string $corpsMessage = null)
    {
        $this->facture->loadMissing(['client', 'institut']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Facture ' . $this->facture->numero . ' – '
                . ($this->facture->institut?->nom ?? config('app.name')),
        );
    }

    public function content(): Content
    {
        $facture = $this->facture;

        $corps = $this->corpsMessage ?: trim(
            'Bonjour ' . ($facture->client_prenom ?: $facture->client?->prenom ?: '') . ",\n\n"
            . 'Sauf erreur de notre part, la facture ' . $facture->numero . ' reste impayée.'
            . "\nMontant restant dû : " . number_format($facture->resteAPayer, 0, ',', ' ') . ' F CFA'
            . "\nÉchéance : " . $facture->date_echeance?->format('d/m/Y') . "\n\n"
            . 'Nous vous remercions de bien vouloir procéder au règlement dans les meilleurs délais.' . "\n\n"
            . 'Bien cordialement,'
        );

        return new Content(
            view: 'emails.factures.relance',
            with: [
                'facture' => $facture,
                // ⚠️ Ne PAS nommer cette variable « message » : Laravel
                // réserve ce nom dans les vues Mail (il y injecte l'instance
                // Illuminate\Mail\Message, ce qui provoquait une erreur 500
                // « htmlspecialchars(): Argument #1 must be of type string »).
                'corps'   => $corps,
                'lien'    => $facture->token
                    ? route('facture.public', ['id' => $facture->id, 'token' => $facture->token])
                    : route('facture.public', ['id' => $facture->id]),
            ],
        );
    }
}
