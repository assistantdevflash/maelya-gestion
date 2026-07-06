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
        Schema::table('clients', function (Blueprint $table) {
            // Type de client : personne physique (défaut) ou entreprise
            $table->enum('type_client', ['personne_physique', 'entreprise'])->default('personne_physique')->after('institut_id');
            
            // Pour personne physique : est-ce un patient ?
            $table->boolean('est_patient')->default(false)->after('type_client');
            
            // Champs spécifiques aux entreprises
            $table->string('raison_sociale', 255)->nullable()->after('est_patient');
            $table->string('numero_registre_commerce', 100)->nullable()->after('raison_sociale');
            $table->text('adresse_entreprise')->nullable()->after('numero_registre_commerce');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'type_client',
                'est_patient',
                'raison_sociale',
                'numero_registre_commerce',
                'adresse_entreprise'
            ]);
        });
    }
};
