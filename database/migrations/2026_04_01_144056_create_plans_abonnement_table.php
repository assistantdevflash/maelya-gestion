<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans_abonnement', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nom', 50);
            $table->enum('duree_type', ['essai', 'mensuel', 'trimestriel', 'annuel']);
            $table->integer('duree_jours');
            $table->decimal('prix', 10, 0);
            $table->integer('economie_pct')->default(0)->comment('Pourcentage economy vs mensuel');
            $table->text('description')->nullable();
            $table->boolean('actif')->default(true);
            $table->boolean('mis_en_avant')->default(false);
            $table->integer('ordre')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans_abonnement');
    }
};
