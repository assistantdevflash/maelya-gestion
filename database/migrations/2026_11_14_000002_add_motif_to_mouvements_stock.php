<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `type` reste la catégorie comptable imposée par l'enum d'origine
     * (entree / sortie_vente / correction / annulation_vente).
     *
     * `motif` porte le contexte métier : réservation de commande, libération,
     * facture, réception de bon de commande, inventaire… Cela évite de
     * modifier un enum existant (difficile à migrer sur SQLite) tout en
     * gardant une distinction exploitable dans l'historique.
     */
    public function up(): void
    {
        Schema::table('mouvements_stock', function (Blueprint $table) {
            $table->string('motif', 40)->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('mouvements_stock', function (Blueprint $table) {
            $table->dropColumn('motif');
        });
    }
};
