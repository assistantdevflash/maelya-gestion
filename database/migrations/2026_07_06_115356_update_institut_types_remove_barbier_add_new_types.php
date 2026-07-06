<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrer les instituts de type 'barbier' vers 'autre'
        DB::table('instituts')
            ->where('type', 'barbier')
            ->update(['type' => 'autre']);

        // Modifier l'enum pour retirer 'barbier' et ajouter les nouveaux types
        DB::statement("ALTER TABLE instituts MODIFY COLUMN type ENUM(
            'salon_coiffure',
            'institut_beaute',
            'centre_esthetique',
            'boutique_mode',
            'auto_ecole',
            'cabinet_medical',
            'atelier_technique',
            'centre_formation',
            'imprimerie',
            'lavage_auto',
            'pressing',
            'business_center',
            'depot_gaz',
            'commerce',
            'autre'
        ) NOT NULL DEFAULT 'autre'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Migrer les instituts avec les nouveaux types vers 'autre'
        DB::table('instituts')
            ->whereIn('type', ['auto_ecole', 'cabinet_medical', 'atelier_technique', 'centre_formation'])
            ->update(['type' => 'autre']);

        // Restaurer l'ancien enum avec 'barbier'
        DB::statement("ALTER TABLE instituts MODIFY COLUMN type ENUM(
            'salon_coiffure',
            'institut_beaute',
            'barbier',
            'centre_esthetique',
            'boutique_mode',
            'imprimerie',
            'lavage_auto',
            'pressing',
            'business_center',
            'depot_gaz',
            'commerce',
            'autre'
        ) NOT NULL DEFAULT 'autre'");
    }
};
