<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('institut_id');
            $table->uuid('categorie_id')->nullable();
            $table->string('nom', 150);
            $table->string('reference', 50)->nullable();
            $table->decimal('prix_achat', 10, 0)->default(0);
            $table->decimal('prix_vente', 10, 0);
            $table->integer('stock')->default(0);
            $table->integer('seuil_alerte')->default(5);
            $table->string('unite', 30)->default('pièce');
            $table->text('description')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('institut_id')->references('id')->on('instituts')->onDelete('cascade');
            $table->foreign('categorie_id')->references('id')->on('categories_produits')->nullOnDelete();
            $table->index(['institut_id', 'actif']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produits');
    }
};
