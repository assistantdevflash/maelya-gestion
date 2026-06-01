<?php

namespace App\Services;

use App\Models\Vente;
use Carbon\Carbon;

class FactureNumeroService
{
    /**
     * Génère un numéro de facture au format FAC-{année}-{seq}
     * où {seq} est une séquence à 4 chiffres remise à zéro chaque année,
     * scope par institut.
     *
     * À appeler dans une transaction DB pour garantir l'atomicité
     * (avec lockForUpdate sur le max existant).
     */
    public function generate(string $institutId, ?Carbon $date = null): string
    {
        $annee = ($date ?? Carbon::now())->format('Y');
        $prefix = "FAC-{$annee}-";

        // Lock pessimiste sur les factures de l'année courante pour cet institut
        $dernierNumero = Vente::withoutGlobalScopes()
            ->where('institut_id', $institutId)
            ->where('numero_facture', 'like', $prefix . '%')
            ->lockForUpdate()
            ->max('numero_facture');

        $sequence = 1;
        if ($dernierNumero) {
            $derniereSeq = (int) substr($dernierNumero, strlen($prefix));
            $sequence = $derniereSeq + 1;
        }

        return $prefix . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
