<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('depenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('institut_id');
            $table->uuid('user_id')->nullable();
            $table->string('description');
            $table->enum('categorie', ['loyer', 'salaires', 'fournitures', 'produits', 'equipement', 'marketing', 'autres'])->default('autres');
            $table->decimal('montant', 10, 0);
            $table->date('date');
            $table->string('justificatif')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->foreign('institut_id')->references('id')->on('instituts')->onDelete('cascade');
            $table->index(['institut_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depenses');
    }
};
