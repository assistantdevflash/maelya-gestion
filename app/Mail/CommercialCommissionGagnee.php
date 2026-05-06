<?php

namespace App\Mail;

use App\Models\CommercialCommission;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CommercialCommissionGagnee extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $commercial,
        public CommercialCommission $commission
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "💰 Nouvelle commission générée – Maëlya Gestion",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.commercial.commission-gagnee');
    }
}
