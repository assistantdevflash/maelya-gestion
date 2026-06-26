<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE ventes MODIFY COLUMN mode_paiement ENUM('cash','mobile_money','carte','mixte','credit') DEFAULT 'cash'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE ventes MODIFY COLUMN mode_paiement ENUM('cash','mobile_money','carte','mixte') DEFAULT 'cash'");
    }
};
