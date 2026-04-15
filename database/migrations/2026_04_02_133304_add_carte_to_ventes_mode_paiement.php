<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Sur MySQL, ALTER TABLE suffit pour modifier l'ENUM
        DB::statement("ALTER TABLE ventes MODIFY COLUMN mode_paiement ENUM('cash','mobile_money','carte','mixte') NOT NULL DEFAULT 'cash'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE ventes MODIFY COLUMN mode_paiement ENUM('cash','mobile_money','mixte') NOT NULL DEFAULT 'cash'");
    }
};
