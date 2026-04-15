<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('codes_reduction');

        Schema::create('codes_reduction', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('institut_id');
            $table->string('code', 50);
            $table->string('description', 255)->nullable();
            $table->string('type', 20)->default('pourcentage')->comment('pourcentage | montant_fixe');
            $table->integer('valeur')->default(0)->comment('% ou FCFA');
            $table->integer('montant_minimum')->nullable()->comment('Montant minimum de commande');
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->integer('limite_utilisation')->nullable()->comment('null = illimité');
            $table->integer('nb_utilisations')->default(0);
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->foreign('institut_id')->references('id')->on('instituts')->onDelete('cascade');
            $table->unique(['institut_id', 'code']);
        });

        // Ajouter remise et lien code_reduction aux ventes (sans FK pour compat SQLite)
        Schema::table('ventes', function (Blueprint $table) {
            if (!Schema::hasColumn('ventes', 'remise')) {
                $table->integer('remise')->default(0)->after('total');
            }
            if (!Schema::hasColumn('ventes', 'code_reduction_id')) {
                $table->uuid('code_reduction_id')->nullable()->after('remise');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ventes', function (Blueprint $table) {
            $table->dropColumn(['remise', 'code_reduction_id']);
        });

        Schema::dropIfExists('codes_reduction');
    }
};
