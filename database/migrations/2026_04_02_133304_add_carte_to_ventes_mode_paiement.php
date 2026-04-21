<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MODIFY COLUMN n'existe qu'en MySQL — SQLite ignore cette migration (ENUM = simple string)
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE ventes MODIFY COLUMN mode_paiement ENUM('cash','mobile_money','carte','mixte') NOT NULL DEFAULT 'cash'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE ventes MODIFY COLUMN mode_paiement ENUM('cash','mobile_money','mixte') NOT NULL DEFAULT 'cash'");
        }
    }
};
