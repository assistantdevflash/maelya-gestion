<?php

namespace App\Mail;

use App\Models\PaymentTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NouveauPaiementRecu extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PaymentTransaction $transaction)
    {
        $this->transaction->loadMissing(['user', 'abonnement', 'abonnement.plan']);
    }

    /** Libellé lisible du type de transaction */
    public static function typeLabel(string $type): string
    {
        return match ($type) {
            'abonnement'            => 'Abonnement',
            'renouvellement'        => 'Renouvellement d\'abonnement',
            'upgrade'               => 'Mise à niveau d\'abonnement',
            'boutique_activation'   => 'Activation boutique en ligne',
            'boutique_renouvellement' => 'Renouvellement boutique en ligne',
            default                 => 'Paiement',
        };
    }

    public function envelope(): Envelope
    {
        $client = $this->transaction->user;

        return new Envelope(
            subject: '💳 Nouveau paiement reçu – '
                . ($client?->nom_complet ?? 'Client')
                . ' (' . number_format($this->transaction->amount, 0, ',', ' ') . ' FCFA)',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.nouveau-paiement-recu',
        );
    }
}
