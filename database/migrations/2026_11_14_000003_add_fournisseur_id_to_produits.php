<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sans fournisseur sur le produit, impossible de regrouper les besoins en
     * réapprovisionnement par fournisseur : la génération automatique de bons
     * de commande n'aurait aucun point d'ancrage.
     */
    public function up(): void
    {
        if (Schema::hasColumn('produits', 'fournisseur_id')) {
            return;
        }

        Schema::table('produits', function (Blueprint $table) {
            // Simple index, comme les autres tables du module (bons_commande,
            // inventaires) : aucune contrainte FK dans ce schéma, ce qui évite
            // les échecs d'ALTER sur les hébergements mutualisés.
            $table->uuid('fournisseur_id')->nullable()->after('categorie_id')->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('produits', 'fournisseur_id')) {
            return;
        }

        Schema::table('produits', function (Blueprint $table) {
            $table->dropColumn('fournisseur_id');
        });
    }
};
