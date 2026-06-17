<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE vente_items ADD COLUMN type_libre ENUM('prestation', 'produit') NULL AFTER type");
            DB::statement('ALTER TABLE vente_items ADD COLUMN categorie_id CHAR(36) NULL AFTER type_libre');
            DB::statement('ALTER TABLE vente_items ADD INDEX vente_items_categorie_id_index (categorie_id)');
        } else {
            // SQLite / autres : syntaxe standard
            Schema::table('vente_items', function (Blueprint $table) {
                $table->string('type_libre', 20)->nullable()->after('type');
                $table->uuid('categorie_id')->nullable()->after('type_libre');
                $table->index('categorie_id');
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE vente_items DROP INDEX vente_items_categorie_id_index');
            DB::statement('ALTER TABLE vente_items DROP COLUMN categorie_id');
            DB::statement('ALTER TABLE vente_items DROP COLUMN type_libre');
        } else {
            Schema::table('vente_items', function (Blueprint $table) {
                $table->dropIndex(['categorie_id']);
                $table->dropColumn(['type_libre', 'categorie_id']);
            });
        }
    }
};
