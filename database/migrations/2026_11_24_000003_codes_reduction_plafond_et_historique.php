<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Deux ajouts au module Remises & Avoirs :
 *
 *  • `plafond_remise` : pour un code en pourcentage, plafonner la remise en
 *    valeur (ex. « −20 %, mais 10 000 F maximum »). Sans plafond, un code
 *    partagé sur un gros panier coûte très cher.
 *
 *  • `code_reduction_utilisations` : l'historique. Le compteur
 *    `nb_utilisations` disait combien de fois un code a servi, jamais à qui,
 *    quand, sur quelle vente, ni pour quel montant.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('codes_reduction', 'plafond_remise')) {
            Schema::table('codes_reduction', function (Blueprint $table) {
                $table->integer('plafond_remise')->nullable();
            });
        }

        if (! Schema::hasTable('code_reduction_utilisations')) {
            Schema::create('code_reduction_utilisations', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('code_reduction_id');
                $table->uuid('institut_id');
                $table->uuid('vente_id')->nullable();
                $table->uuid('client_id')->nullable();
                $table->string('code', 50);
                $table->integer('montant_remise')->default(0);
                $table->timestamps();

                $table->foreign('code_reduction_id')->references('id')->on('codes_reduction')->onDelete('cascade');
                $table->index('code_reduction_id');
                $table->index(['institut_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('code_reduction_utilisations');

        if (Schema::hasColumn('codes_reduction', 'plafond_remise')) {
            Schema::table('codes_reduction', function (Blueprint $table) {
                $table->dropColumn('plafond_remise');
            });
        }
    }
};
