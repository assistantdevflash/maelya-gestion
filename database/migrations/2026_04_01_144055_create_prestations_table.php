<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prestations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('institut_id');
            $table->uuid('categorie_id');
            $table->string('nom', 150);
            $table->decimal('prix', 10, 0);
            $table->integer('duree')->nullable()->comment('Durée en minutes');
            $table->text('description')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('institut_id')->references('id')->on('instituts')->onDelete('cascade');
            $table->foreign('categorie_id')->references('id')->on('categories_prestations');
            $table->index(['institut_id', 'actif']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestations');
    }
};
