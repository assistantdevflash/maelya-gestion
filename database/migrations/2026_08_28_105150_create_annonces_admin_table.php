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
        // Table des annonces/messages bannières
        Schema::create('annonces_admin', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('expediteur_id')->nullable();
            $table->string('titre', 200);
            $table->text('message');
            $table->enum('type', ['info', 'warning', 'success', 'danger'])->default('info');
            $table->enum('cible', ['tous', 'selection', 'un'])->default('tous');
            $table->json('instituts_ids')->nullable(); // IDs des instituts ciblés
            $table->boolean('actif')->default(true);
            $table->timestamp('expire_le')->nullable();
            $table->timestamps();
            
            // Index sans contrainte stricte pour éviter les erreurs de compatibilité
            $table->index('expediteur_id');
            $table->index('actif');
            $table->index('expire_le');
        });

        // Table pivot pour tracker les lectures
        Schema::create('annonce_lectures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('annonce_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('lu_le');
            $table->unique(['annonce_id', 'user_id']);
            
            // Index pour les performances
            $table->index('annonce_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('annonce_lectures');
        Schema::dropIfExists('annonces_admin');
    }
};
