<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('institut_id')->constrained('instituts')->cascadeOnDelete();
            $table->foreignUuid('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('commercial_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('numero', 30)->unique();
            $table->string('statut', 20)->default('brouillon');
            $table->string('token', 64)->nullable()->unique();
            $table->date('date_creation');
            $table->date('date_expiration');
            $table->dateTime('date_acceptation')->nullable();
            $table->text('signature_client')->nullable();
            $table->string('client_prenom', 100)->nullable();
            $table->string('client_nom', 100)->nullable();
            $table->string('client_email', 255)->nullable();
            $table->string('client_telephone', 30)->nullable();
            $table->text('client_adresse')->nullable();
            $table->integer('sous_total')->default(0);
            $table->string('remise_globale_type', 20)->nullable();
            $table->integer('remise_globale_valeur')->default(0);
            $table->integer('total_ht')->default(0);
            $table->boolean('tva_applicable')->default(false);
            $table->decimal('tva_taux', 5, 2)->default(0);
            $table->integer('total_ttc')->default(0);
            $table->text('notes')->nullable();
            $table->text('conditions')->nullable();
            $table->foreignUuid('facture_id')->nullable();
            $table->timestamps();
            $table->index(['institut_id', 'statut']);
            $table->index('date_creation');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devis');
    }
};
