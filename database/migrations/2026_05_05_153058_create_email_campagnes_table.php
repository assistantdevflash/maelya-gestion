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
        if (Schema::hasTable('email_campagnes')) {
            return;
        }

        Schema::create('email_campagnes', function (Blueprint $table) {
            $table->id();
            $table->uuid('envoye_par')->nullable();
            $table->foreign('envoye_par')->references('id')->on('users')->nullOnDelete();
            $table->string('sujet');
            $table->longText('corps');
            $table->string('mode'); // tous | selection | un | personnalise
            $table->json('destinataires_emails'); // liste des emails envoyés
            $table->unsignedInteger('nb_envoyes')->default(0);
            $table->unsignedInteger('nb_echecs')->default(0);
            $table->text('erreurs')->nullable(); // erreurs éventuelles
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_campagnes');
    }
};
