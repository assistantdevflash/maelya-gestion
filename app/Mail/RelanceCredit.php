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

    public function __construct(public Credit $credit, public string $message)
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
                'credit'  => $this->credit,
                'message' => $this->message,
                'suivi'   => app(CreditService::class)->urlSuivi($this->credit),
            ],
        );
    }
}
