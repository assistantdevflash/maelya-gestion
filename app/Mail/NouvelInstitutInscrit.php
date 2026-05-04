<?php

namespace App\Mail;

use App\Models\Institut;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NouvelInstitutInscrit extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $newUser,
        public Institut $institut
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "🏪 Nouvel établissement inscrit – {$this->institut->nom}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.nouvel-institut-inscrit',
        );
    }
}
