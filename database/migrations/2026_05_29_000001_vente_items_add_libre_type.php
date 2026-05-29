<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Rendre item_id nullable pour les articles libres (hors catalogue)
        DB::statement('ALTER TABLE vente_items MODIFY COLUMN item_id CHAR(36) NULL');

        // Ajouter le type "libre" à l'enum
        DB::statement("ALTER TABLE vente_items MODIFY COLUMN type ENUM('prestation', 'produit', 'libre') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE vente_items MODIFY COLUMN type ENUM('prestation', 'produit') NOT NULL");
        DB::statement('ALTER TABLE vente_items MODIFY COLUMN item_id CHAR(36) NOT NULL');
    }
};
