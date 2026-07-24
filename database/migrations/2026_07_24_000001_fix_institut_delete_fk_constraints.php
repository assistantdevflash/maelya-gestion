<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Corrige les FK qui bloquent la suppression d'un institut.
     * Chaîne : institut → categories_prestations → prestations (FK RESTRICT)
     */
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'])) {
            // 1. Supprimer l'ancienne FK prestations.categorie_id
            try { DB::statement('ALTER TABLE prestations DROP FOREIGN KEY prestations_categorie_id_foreign'); } catch (\Exception $e) {}
            // 2. Recréer avec ON DELETE SET NULL
            DB::statement('ALTER TABLE prestations MODIFY categorie_id CHAR(36) NULL');
            DB::statement('ALTER TABLE prestations ADD CONSTRAINT prestations_categorie_id_foreign FOREIGN KEY (categorie_id) REFERENCES categories_prestations(id) ON DELETE SET NULL');

            // 3. Idem pour produits → categories_produits
            try { DB::statement('ALTER TABLE produits DROP FOREIGN KEY produits_categorie_id_foreign'); } catch (\Exception $e) {}
            DB::statement('ALTER TABLE produits MODIFY categorie_id CHAR(36) NULL');
            DB::statement('ALTER TABLE produits ADD CONSTRAINT produits_categorie_id_foreign FOREIGN KEY (categorie_id) REFERENCES categories_produits(id) ON DELETE SET NULL');
        }
    }

    public function down(): void
    {
        // Pas de rollback
    }
};
