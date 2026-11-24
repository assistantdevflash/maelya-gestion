<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Titre libre d'un crédit, sur le modèle de celui des devis et des factures :
 * « Pack mariage », « Réparation moteur », « Abonnement annuel »…
 *
 * Facultatif : un crédit sans titre continue de fonctionner exactement comme
 * avant, et reste identifiable par son numéro de vente.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('credits', 'titre')) {
            Schema::table('credits', function (Blueprint $table) {
                $table->string('titre', 200)->nullable()->after('client_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('credits', 'titre')) {
            Schema::table('credits', function (Blueprint $table) {
                $table->dropColumn('titre');
            });
        }
    }
};
