<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facture_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('facture_id')->constrained('factures')->cascadeOnDelete();
            $table->foreignUuid('produit_id')->nullable()->constrained('produits')->nullOnDelete();
            $table->foreignUuid('prestation_id')->nullable()->constrained('prestations')->nullOnDelete();
            $table->string('designation', 255);
            $table->integer('quantite')->default(1);
            $table->integer('prix_unitaire');
            $table->string('remise_type', 20)->nullable();
            $table->integer('remise_valeur')->default(0);
            $table->decimal('tva_taux', 5, 2)->nullable();
            $table->integer('total_ligne');
            $table->integer('ordre')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facture_items');
    }
};
