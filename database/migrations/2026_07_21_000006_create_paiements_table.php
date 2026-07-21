<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paiements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('facture_id')->constrained('factures')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->integer('montant');
            $table->string('mode_paiement', 30)->default('cash');
            $table->string('reference', 100)->nullable();
            $table->date('date_paiement');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Ajouter la FK facture_id sur la table devis
        Schema::table('devis', function (Blueprint $table) {
            $table->foreign('facture_id')->references('id')->on('factures')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('devis', function (Blueprint $table) {
            $table->dropForeign(['facture_id']);
        });
        Schema::dropIfExists('paiements');
    }
};
