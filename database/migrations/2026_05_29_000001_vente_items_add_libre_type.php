<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite ne supporte pas ALTER COLUMN ENUM — on passe par une reconstruction
        // de table (Laravel `change()`), pour que le schéma de test reste aligné sur
        // celui de production (item_id nullable + type 'libre' autorisé).
        if (DB::getDriverName() !== 'mysql') {
            Schema::table('vente_items', function (Blueprint $table) {
                $table->uuid('item_id')->nullable()->change();
                $table->string('type', 20)->change();
            });

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
