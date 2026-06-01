<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite ne supporte pas ALTER COLUMN ENUM — on saute (tests en m\u00e9moire)
        if (DB::getDriverName() !== 'mysql') {
            return;
        }
        DB::statement('ALTER TABLE vente_items MODIFY COLUMN item_id CHAR(36) NULL');
        DB::statement("ALTER TABLE vente_items MODIFY COLUMN type ENUM('prestation', 'produit', 'libre') NOT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }
        DB::statement("ALTER TABLE vente_items MODIFY COLUMN type ENUM('prestation', 'produit') NOT NULL");
        DB::statement('ALTER TABLE vente_items MODIFY COLUMN item_id CHAR(36) NOT NULL');
    }
};
