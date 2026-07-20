<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute le type "Informatique/Téléphonie" et convertit le champ
     * ENUM → VARCHAR pour plus de flexibilité future.
     */
    public function up(): void
    {
        // Convertir la colonne 'type' de ENUM vers VARCHAR(50)
        // Cela préserve toutes les données existantes
        DB::statement("ALTER TABLE instituts MODIFY COLUMN type VARCHAR(50) NOT NULL DEFAULT 'autre'");
    }

    public function down(): void
    {
        // Restaurer l'ENUM avec les valeurs connues (attention : les types
        // ajoutés depuis seront perdus côté contrainte mais pas en données)
        DB::statement("ALTER TABLE instituts MODIFY COLUMN type ENUM(
            'salon_coiffure','institut_beaute','centre_esthetique','boutique_mode',
            'auto_ecole','cabinet_medical','atelier_technique','centre_formation',
            'imprimerie','lavage_auto','pressing','business_center','depot_gaz',
            'commerce','informatique_telephonie','autre'
        ) NOT NULL DEFAULT 'autre'");
    }
};
