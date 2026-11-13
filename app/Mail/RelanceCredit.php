<?php

namespace App\Mail;

use App\Models\Credit;
use App\Services\CreditService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Relance d'une échéance de crédit envoyée au client.
 */
class RelanceCredit extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * ⚠️ La propriété ne doit pas s'appeler « message » : Laravel injecte
     * toujours une variable $message (instance Illuminate\Mail\Message) dans
     * les vues Mail, ce qui masque la nôtre et casse le rendu.
     */
    public function __construct(public Credit $credit, public string $corpsMessage)
    {
        $this->credit->loadMissing(['client', 'institut', 'echeances']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Rappel de paiement – ' . ($this->credit->institut?->nom ?? config('app.name')),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.credits.relance',
            with: [
                'credit' => $this->credit,
                // « message » est réservé par Laravel dans les vues Mail.
                'corps'  => $this->corpsMessage,
                'suivi'   => app(CreditService::class)->urlSuivi($this->credit),
            ],
        );
    }
}
