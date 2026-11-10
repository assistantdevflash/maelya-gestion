<?php

namespace App\Services;

use App\Models\Vente;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Génération et mise en cache du ticket de caisse au format PDF.
 *
 * Dompdf coûte cher au premier rendu (chargement des métriques de police,
 * parsing du CSS, calcul de la mise en page) : sur un hébergement mutualisé
 * cela représente facilement une à trois secondes. Comme un ticket ne change
 * jamais après la vente, le PDF est écrit une fois sur disque puis servi
 * directement.
 *
 * Le ticket est en plus pré-généré au moment de l'encaissement : quand la
 * caisse demande l'impression, le fichier existe déjà et la fenêtre
 * d'impression s'ouvre presque instantanément.
 */
class TicketPdfService
{
    /** Dimensions du rouleau 80 mm (226,77 pt de large). */
    private const PAPIER = [0, 0, 226.77, 600];

    /**
     * Chemin du PDF en cache pour cette vente.
     */
    public function chemin(Vente $vente): string
    {
        return storage_path('app/private/tickets/' . $vente->id . '.pdf');
    }

    /**
     * Retourne le chemin du PDF, en le générant si nécessaire.
     *
     * @param  bool  $forcer  Régénère même si un fichier récent existe.
     */
    public function generer(Vente $vente, bool $forcer = false): string
    {
        $chemin = $this->chemin($vente);

        $aJour = is_file($chemin)
            && $vente->updated_at !== null
            && filemtime($chemin) >= $vente->updated_at->getTimestamp();

        if (! $forcer && $aJour) {
            return $chemin;
        }

        $dossier = dirname($chemin);
        if (! is_dir($dossier)) {
            @mkdir($dossier, 0775, true);
        }

        // Sans ce dossier, dompdf recalcule les métriques de police à chaque
        // appel : c'est la principale cause de lenteur.
        $dossierPolices = storage_path('fonts');
        if (! is_dir($dossierPolices)) {
            @mkdir($dossierPolices, 0775, true);
        }

        $vente->loadMissing('items', 'client', 'institut', 'user');

        $pdf = Pdf::loadView('pdf.ticket', ['vente' => $vente])
            ->setPaper(self::PAPIER, 'portrait')
            // Tout est local (logo compris) : couper l'accès distant évite
            // les tentatives de résolution d'URL et les délais réseau.
            ->setOption('isRemoteEnabled', false)
            ->setOption('enable_remote', false)
            ->setOption('isFontSubsettingEnabled', false)
            ->setOption('defaultFont', 'DejaVu Sans')
            ->setOption('dpi', 96);

        file_put_contents($chemin, $pdf->output());

        return $chemin;
    }

    /**
     * Réponse HTTP servant le ticket (affichage direct dans le navigateur).
     */
    public function reponse(Vente $vente)
    {
        $chemin = $this->generer($vente);

        return response()->file($chemin, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="ticket-' . $vente->numero . '.pdf"',
        ]);
    }

    /**
     * Supprime le PDF en cache (vente annulée ou modifiée).
     */
    public function oublier(Vente $vente): void
    {
        $chemin = $this->chemin($vente);

        if (is_file($chemin)) {
            @unlink($chemin);
        }
    }
}
