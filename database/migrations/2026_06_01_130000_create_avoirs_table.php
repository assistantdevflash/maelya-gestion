<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avoirs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('institut_id');
            $table->uuid('vente_id')->nullable();
            $table->uuid('client_id')->nullable();
            $table->uuid('user_id');
            $table->uuid('code_reduction_id')->nullable();
            $table->string('numero', 30);
            $table->integer('montant');
            $table->string('motif', 255)->nullable();
            $table->enum('statut', ['emis', 'utilise', 'annule'])->default('emis');
            $table->timestamps();

            $table->foreign('institut_id')->references('id')->on('instituts')->onDelete('cascade');
            $table->unique(['institut_id', 'numero']);
            $table->index(['institut_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avoirs');
    }
};
