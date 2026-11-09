<?php

namespace App\Mail;

use App\Models\Commande;
use App\Models\Institut;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

/**
 * Renvoi des liens de suivi au client qui a perdu son email de confirmation.
 *
 * Aucune donnée de commande n'est affichée à l'écran lors de la demande : tout
 * transite par les liens signés déjà envoyés à la création de la commande. Un
 * visiteur ne peut donc rien apprendre en testant des numéros de téléphone.
 */
class LiensCommandesClient extends Mailable
{
    use Queueable, SerializesModels;

    /** @var Collection<int, array{commande: Commande, lien: string}> */
    public Collection $lignes;

    /**
     * @param  Collection<int, Commande>  $commandes
     */
    public function __construct(public Institut $institut, Collection $commandes)
    {
        $this->lignes = $commandes->map(fn (Commande $commande) => [
            'commande' => $commande,
            'lien'     => URL::signedRoute('shop.suivi', [
                'slug'   => $this->institut->slug,
                'numero' => $commande->numero,
            ]),
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Vos commandes chez ' . $this->institut->nom,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.commande.liens-client',
            with: [
                'institut' => $this->institut,
                'lignes'   => $this->lignes,
                'boutique' => route('shop.index', $this->institut->slug),
            ],
        );
    }
}
