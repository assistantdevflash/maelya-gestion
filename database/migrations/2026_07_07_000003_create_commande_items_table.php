<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commande_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Relations
            $table->foreignUuid('commande_id')->constrained('commandes')->cascadeOnDelete();
            $table->foreignUuid('produit_id')->nullable()->constrained('produits')->nullOnDelete();
            
            // Snapshot du produit (pour historique)
            $table->string('nom_snapshot');
            $table->decimal('prix_snapshot', 10, 2);
            $table->integer('quantite');
            $table->decimal('sous_total', 10, 2);
            
            $table->timestamps();
            
            // Index
            $table->index('commande_id');
            $table->index('produit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commande_items');
    }
};
