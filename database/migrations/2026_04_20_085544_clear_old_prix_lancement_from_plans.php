<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Désactive l'ancien système prix_lancement (remplacé par OffrePromotionnelle).
        // Passe fin_offre_lancement à NULL pour tous les plans afin que prixEffectif()
        // retourne le vrai prix et non plus l'ancien prix de lancement.
        DB::table('plans_abonnement')->update([
            'fin_offre_lancement' => null,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Irréversible : les anciennes dates ne sont pas conservées.
    }
};
