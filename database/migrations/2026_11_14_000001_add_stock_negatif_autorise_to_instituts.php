<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ⚠️ Pas de `->after('seuil_alerte')` : cette colonne existe sur
        // `produits`, pas sur `instituts`. SQLite ignore `after()` (les tests
        // passaient), MySQL l'applique et la migration échouait en production
        // avec « Unknown column 'seuil_alerte' in 'instituts' ».
        if (Schema::hasColumn('instituts', 'stock_negatif_autorise')) {
            return;
        }

        Schema::table('instituts', function (Blueprint $table) {
            // Par défaut on refuse une vente sans stock : c'est le
            // comportement attendu d'une gestion de stock. Certains
            // établissements (coiffure, prestations à la commande) vendent
            // parfois un article non encore réceptionné et doivent pouvoir
            // passer outre, en connaissance de cause.
            $table->boolean('stock_negatif_autorise')->default(false);
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('instituts', 'stock_negatif_autorise')) {
            return;
        }

        Schema::table('instituts', function (Blueprint $table) {
            $table->dropColumn('stock_negatif_autorise');
        });
    }
};
