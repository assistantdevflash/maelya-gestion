<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('etablissement_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->unique()->comment('Identifiant technique (ex: salon_coiffure)');
            $table->string('libelle', 200)->comment('Libellé affiché (ex: Salon de coiffure)');
            $table->boolean('actif')->default(true)->comment('Type actif ou archivé');
            $table->integer('position')->default(0)->comment('Ordre d\'affichage');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('etablissement_types');
    }
};
