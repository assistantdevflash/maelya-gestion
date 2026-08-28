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
            $table->foreignId('expediteur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('titre', 200);
            $table->text('message');
            $table->enum('type', ['info', 'warning', 'success', 'danger'])->default('info');
            $table->enum('cible', ['tous', 'selection', 'un'])->default('tous');
            $table->json('instituts_ids')->nullable(); // IDs des instituts ciblés
            $table->boolean('actif')->default(true);
            $table->timestamp('expire_le')->nullable();
            $table->timestamps();
        });

        // Table pivot pour tracker les lectures
        Schema::create('annonce_lectures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annonce_id')->constrained('annonces_admin')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('lu_le');
            $table->unique(['annonce_id', 'user_id']);
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
