<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('avis_clients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('institut_id');
            $table->uuid('client_id')->nullable();
            $table->uuid('rdv_id')->nullable();
            $table->uuid('vente_id')->nullable();
            $table->string('token', 64)->unique();
            $table->unsignedTinyInteger('note')->nullable(); // 1..5
            $table->text('commentaire')->nullable();
            $table->string('statut', 20)->default('en_attente'); // en_attente | approuve | rejete
            $table->string('client_nom_snap', 100)->nullable();
            $table->timestamp('repondu_le')->nullable();
            $table->timestamps();

            $table->foreign('institut_id')->references('id')->on('instituts')->cascadeOnDelete();
            $table->index(['institut_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avis_clients');
    }
};
