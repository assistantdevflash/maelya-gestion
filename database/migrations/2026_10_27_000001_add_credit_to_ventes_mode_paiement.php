<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute « credit » au mode de paiement des ventes.
 *
 * Le module crédits écrit `mode_paiement = 'credit'` depuis sa mise en place
 * (CreditController, Livewire\Caisse) et une quinzaine de requêtes agrègent
 * `CASE WHEN mode_paiement = 'credit'`. La valeur n'avait pourtant jamais été
 * ajoutée à l'énumération : la migration prévue dans docs/credits-module.md
 * n'a été écrite que pour les colonnes de suivi (montant_paye, credit_statut).
 *
 * Sans cette valeur :
 *  - en MySQL strict, l'enregistrement d'une vente à crédit échoue ;
 *  - en mode permissif, la colonne est vidée silencieusement et tous les
 *    calculs de chiffre d'affaires comptabilisent le crédit à 100 %.
 *
 * `carte` est également (ré)ajouté : la migration d'origine utilisait une
 * requête brute MySQL, ignorée sur SQLite, où la contrainte est restée
 * limitée à cash / mobile_money / mixte.
 */
return new class extends Migration
{
    private const VALEURS = ['cash', 'mobile_money', 'carte', 'mixte', 'credit'];

    private const VALEURS_AVANT = ['cash', 'mobile_money', 'carte', 'mixte'];

    public function up(): void
    {
        $this->definirModePaiement(self::VALEURS);
    }

    public function down(): void
    {
        // Les ventes à crédit existantes doivent survivre à un retour arrière
        $this->definirModePaiement(self::VALEURS_AVANT, 'mixte');
    }

    /**
     * @param  array<int, string> $valeurs
     */
    private function definirModePaiement(array $valeurs, string $defaut = 'cash'): void
    {
        Schema::table('ventes', function (Blueprint $table) use ($valeurs, $defaut) {
            $table->enum('mode_paiement', $valeurs)->default($defaut)->change();
        });
    }
};
