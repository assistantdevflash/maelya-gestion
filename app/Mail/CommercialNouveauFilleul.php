<?php

namespace App\Mail;

use App\Models\CommercialParrainage;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CommercialNouveauFilleul extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $commercial,
        public User $newUser,
        public CommercialParrainage $parrainage
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "🎉 Nouvel établissement inscrit avec votre code – Maëlya Gestion",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.commercial.nouveau-filleul');
    }
}
