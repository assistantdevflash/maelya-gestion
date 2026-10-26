<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adresse postale de l'établissement.
 *
 * Colonne déjà référencée par les PDF (factures, devis, rapport financier,
 * facture boutique) mais jamais stockée : elle s'affichera dès qu'elle sera
 * renseignée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instituts', function (Blueprint $table) {
            $table->string('adresse', 255)->nullable()->after('ville');
        });
    }

    public function down(): void
    {
        Schema::table('instituts', function (Blueprint $table) {
            $table->dropColumn('adresse');
        });
    }
};
