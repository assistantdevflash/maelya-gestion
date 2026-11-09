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
        Schema::table('produits', function (Blueprint $table) {
            $table->foreignUuid('fournisseur_id')->nullable()->after('categorie_id')
                ->constrained('fournisseurs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('produits', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fournisseur_id');
        });
    }
};
