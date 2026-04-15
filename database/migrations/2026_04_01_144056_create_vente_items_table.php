<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vente_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vente_id');
            $table->enum('type', ['prestation', 'produit']);
            $table->uuid('item_id');
            $table->string('nom_snapshot', 150);
            $table->decimal('prix_snapshot', 10, 0);
            $table->integer('quantite')->default(1);
            $table->decimal('sous_total', 10, 0);
            $table->timestamps();
            $table->foreign('vente_id')->references('id')->on('ventes')->onDelete('cascade');
            $table->index('vente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vente_items');
    }
};
