<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Configuration du programme de fidélité par institut
        Schema::create('programme_fidelite', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('institut_id')->unique();
            $table->boolean('actif')->default(false);
            $table->integer('tranche_fcfa')->default(1000)->comment('Montant en FCFA par tranche');
            $table->integer('points_par_tranche')->default(1)->comment('Points gagnés par tranche');
            $table->integer('seuil_recompense')->default(100)->comment('Points requis pour une récompense');
            $table->string('type_recompense', 20)->default('pourcentage')->comment('pourcentage | montant_fixe');
            $table->integer('valeur_recompense')->default(10)->comment('% ou FCFA de la récompense');
            $table->timestamps();

            $table->foreign('institut_id')->references('id')->on('instituts')->onDelete('cascade');
        });

        // Ajouter les points de fidélité aux clients
        Schema::table('clients', function (Blueprint $table) {
            $table->integer('points_fidelite')->default(0)->after('notes');
        });

        // Historique des mouvements de points
        Schema::create('historique_points', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('institut_id');
            $table->uuid('client_id');
            $table->uuid('vente_id')->nullable();
            $table->integer('points')->comment('Positif = gain, négatif = utilisation');
            $table->string('type', 20)->comment('gain | recompense | ajustement');
            $table->string('description')->nullable();
            $table->timestamps();

            $table->foreign('institut_id')->references('id')->on('instituts')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->index(['institut_id', 'client_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historique_points');

        if (Schema::hasColumn('clients', 'points_fidelite')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->dropColumn('points_fidelite');
            });
        }

        Schema::dropIfExists('programme_fidelite');
    }
};
