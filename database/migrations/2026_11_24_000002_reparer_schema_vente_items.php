<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Réparation du schéma SQLite de `vente_items`.
 *
 * La migration du 29/05/2026 voulait rendre `item_id` facultatif et autoriser
 * `type = 'libre'`. Sur MySQL elle passe par un `ALTER TABLE`, sans problème.
 * Sur SQLite, Laravel ne sait pas retirer une contrainte CHECK : la table est
 * restée avec `item_id NOT NULL` et un CHECK limité à `prestation` / `produit`.
 * Résultat, en local, toute vente ou tout crédit contenant une ligne libre
 * échouait sur une erreur de contrainte.
 *
 * On reconstruit donc la table à l'identique, sans la contrainte fautive, en
 * conservant les données, la clé étrangère et les index. L'opération ne fait
 * rien si le schéma est déjà correct, et rien du tout sur MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite' || ! Schema::hasTable('vente_items')) {
            return;
        }

        $definition = (string) (DB::selectOne(
            "SELECT sql FROM sqlite_master WHERE type = 'table' AND name = 'vente_items'"
        )->sql ?? '');

        $checkTropStrict = str_contains($definition, 'check ("type"');
        $itemIdObligatoire = (bool) preg_match('/"item_id"\s+varchar\s+not\s+null/i', $definition);

        if (! $checkTropStrict && ! $itemIdObligatoire) {
            return; // schéma déjà conforme
        }

        Schema::disableForeignKeyConstraints();

        Schema::create('vente_items_reparation', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vente_id');
            $table->string('type', 20);
            $table->string('type_libre', 20)->nullable();
            $table->uuid('categorie_id')->nullable();
            $table->uuid('item_id')->nullable();
            $table->string('nom_snapshot', 150);
            $table->decimal('prix_snapshot', 10, 0);
            $table->integer('quantite')->default(1);
            $table->decimal('sous_total', 10, 0);
            $table->timestamps();

            $table->foreign('vente_id')->references('id')->on('ventes')->onDelete('cascade');
            $table->index('vente_id');
            $table->index('categorie_id');
        });

        DB::statement(
            'INSERT INTO vente_items_reparation
                (id, vente_id, type, type_libre, categorie_id, item_id,
                 nom_snapshot, prix_snapshot, quantite, sous_total, created_at, updated_at)
             SELECT id, vente_id, type, type_libre, categorie_id, item_id,
                    nom_snapshot, prix_snapshot, quantite, sous_total, created_at, updated_at
             FROM vente_items'
        );

        Schema::drop('vente_items');
        Schema::rename('vente_items_reparation', 'vente_items');

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Réparation de schéma : rien à défaire. Revenir à une contrainte
        // interdisant `type = 'libre'` casserait de nouveau la caisse.
    }
};
