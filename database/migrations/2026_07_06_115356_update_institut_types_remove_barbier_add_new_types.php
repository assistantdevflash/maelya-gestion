<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Migrer les instituts de type 'barbier' vers 'autre'
        DB::table('instituts')
            ->where('type', 'barbier')
            ->update(['type' => 'autre']);
    }

    public function down(): void
    {
        // Pas de rollback nécessaire
    }
};
