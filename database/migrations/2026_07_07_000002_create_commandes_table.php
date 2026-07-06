<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commandes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Relations
            $table->foreignUuid('institut_id')->constrained('instituts')->cascadeOnDelete();
            $table->foreignUuid('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignUuid('vente_id')->nullable()->constrained('ventes')->nullOnDelete();
            
            // Numérotation
            $table->string('numero')->unique();
            
            // Snapshot des infos client (pour historique)
            $table->string('client_prenom');
            $table->string('client_nom');
            $table->string('client_telephone');
            $table->string('client_email')->nullable();
            $table->text('client_adresse');
            
            // Montants
            $table->decimal('sous_total', 10, 2);
            $table->decimal('frais_livraison', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            
            // Workflow
            $table->enum('statut', [
                'nouvelle',
                'acceptee',
                'en_preparation',
                'en_livraison',
                'livree',
                'annulee',
                'refusee'
            ])->default('nouvelle');
            
            // Dates importantes
            $table->timestamp('acceptee_at')->nullable();
            $table->timestamp('en_preparation_at')->nullable();
            $table->timestamp('en_livraison_at')->nullable();
            $table->timestamp('livree_at')->nullable();
            $table->timestamp('annulee_at')->nullable();
            
            // Paiement
            $table->boolean('payee')->default(false);
            $table->timestamp('payee_at')->nullable();
            $table->string('mode_paiement')->default('cash');
            
            // Notes
            $table->text('notes_client')->nullable();
            $table->text('notes_admin')->nullable();
            
            $table->timestamps();
            
            // Index
            $table->index('numero');
            $table->index('statut');
            $table->index('payee');
            $table->index(['institut_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commandes');
    }
};
