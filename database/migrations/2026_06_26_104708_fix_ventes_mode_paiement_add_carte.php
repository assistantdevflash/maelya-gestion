<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite ne supporte pas ALTER ENUM - la colonne est déjà créée correctement
        // dans la migration initiale create_ventes_table
    }

    public function down(): void
    {
        // Pas de rollback nécessaire
    }
};
