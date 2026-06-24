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
        // Migrer les instituts avec les types supprimés vers 'autre'
        DB::table('instituts')
            ->whereIn('type', ['nail_bar', 'spa', 'hammam', 'soins_capillaires', 'tatouage'])
            ->update(['type' => 'autre']);

        // Modifier l'enum pour la nouvelle liste
        DB::statement("ALTER TABLE instituts MODIFY COLUMN type ENUM(
            'salon_coiffure','institut_beaute','barbier','centre_esthetique','boutique_mode',
            'imprimerie','lavage_auto','pressing','business_center','depot_gaz','commerce','autre'
        ) NOT NULL DEFAULT 'autre'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE instituts MODIFY COLUMN type ENUM(
            'salon_coiffure','institut_beaute','nail_bar','spa','barbier','hammam',
            'centre_esthetique','soins_capillaires','tatouage','boutique_mode','autre'
        ) NOT NULL DEFAULT 'autre'");
    }
};
