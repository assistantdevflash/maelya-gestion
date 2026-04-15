<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mouvements_stock', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('institut_id');
            $table->uuid('produit_id');
            $table->uuid('user_id')->nullable();
            $table->uuid('vente_id')->nullable();
            $table->enum('type', ['entree', 'sortie_vente', 'correction', 'annulation_vente']);
            $table->integer('quantite');
            $table->integer('stock_avant');
            $table->integer('stock_apres');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->foreign('institut_id')->references('id')->on('instituts')->onDelete('cascade');
            $table->foreign('produit_id')->references('id')->on('produits')->onDelete('cascade');
            $table->index(['institut_id', 'produit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mouvements_stock');
    }
};
