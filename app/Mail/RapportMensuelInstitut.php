<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RapportMensuelInstitut extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed> $rapport Résultat de RapportMensuelService::construire()
     */
    public function __construct(
        public array $rapport,
        public User $destinataire,
    ) {}

    public function envelope(): Envelope
    {
        $ca = number_format($this->rapport['actuel']['ca'], 0, ',', ' ');

        return new Envelope(
            subject: '📊 Votre bilan de ' . $this->rapport['label']
                . ' — ' . $ca . ' FCFA',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.rapports.mensuel');
    }
}
