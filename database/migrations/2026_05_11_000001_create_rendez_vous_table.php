<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rendez_vous', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('institut_id');
            $table->uuid('client_id')->nullable();
            $table->string('client_nom', 100);
            $table->string('client_telephone', 30)->nullable();
            $table->string('client_email', 255)->nullable();
            $table->uuid('employe_id')->nullable();
            $table->dateTime('debut_le');
            $table->unsignedSmallInteger('duree_minutes')->default(30);
            $table->string('statut', 20)->default('en_attente'); // en_attente | confirme | annule | termine
            $table->text('notes')->nullable();
            $table->string('prestation_libre', 150)->nullable(); // label libre si aucune prestation du catalogue
            $table->boolean('rappel_envoye')->default(false);
            $table->timestamps();

            $table->foreign('institut_id')->references('id')->on('instituts')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->nullOnDelete();
            $table->foreign('employe_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['institut_id', 'debut_le']);
            $table->index(['institut_id', 'statut']);
            $table->index(['client_id']);
        });

        Schema::create('rendez_vous_prestations', function (Blueprint $table) {
            $table->uuid('rendez_vous_id');
            $table->uuid('prestation_id');
            $table->primary(['rendez_vous_id', 'prestation_id']);
            $table->foreign('rendez_vous_id')->references('id')->on('rendez_vous')->onDelete('cascade');
            $table->foreign('prestation_id')->references('id')->on('prestations')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rendez_vous_prestations');
        Schema::dropIfExists('rendez_vous');
    }
};
