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
        // Migrer les instituts avec les types supprimés vers 'autre'
        DB::table('instituts')
            ->whereIn('type', ['nail_bar', 'spa', 'hammam', 'soins_capillaires', 'tatouage'])
            ->update(['type' => 'autre']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Pas de rollback nécessaire
    }
};
