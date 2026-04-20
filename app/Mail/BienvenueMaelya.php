<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BienvenueMaelya extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        $prenom = $this->user->prenom ?: $this->user->name;

        return new Envelope(
            subject: "🎉 Bienvenue sur " . config('app.name') . ", {$prenom} !",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.bienvenue',
        );
    }
}
