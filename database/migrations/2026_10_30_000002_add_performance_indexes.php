<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index composites pour les requêtes exécutées à CHAQUE chargement de page.
 *
 * - `ventes(client_id, statut, created_at)` : `withCount('ventes')` sur la liste
 *   des clients et les filtres « VIP / fidèle / inactif » (whereHas + created_at).
 * - `clients(institut_id, date_naissance)` : badge anniversaires de la sidebar.
 * - `clients(institut_id, points_fidelite)` : badge fidélité de la sidebar.
 * - `echeances(statut, date_prevue)` : trésorerie prévisionnelle + relances.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventes', function (Blueprint $table) {
            if (! Schema::hasIndex('ventes', ['client_id', 'statut', 'created_at'])) {
                $table->index(['client_id', 'statut', 'created_at'], 'ventes_client_statut_created_index');
            }
        });

        Schema::table('clients', function (Blueprint $table) {
            if (! Schema::hasIndex('clients', ['institut_id', 'date_naissance'])) {
                $table->index(['institut_id', 'date_naissance'], 'clients_institut_naissance_index');
            }

            if (! Schema::hasIndex('clients', ['institut_id', 'points_fidelite'])) {
                $table->index(['institut_id', 'points_fidelite'], 'clients_institut_points_index');
            }
        });

        Schema::table('echeances', function (Blueprint $table) {
            if (! Schema::hasIndex('echeances', ['statut', 'date_prevue'])) {
                $table->index(['statut', 'date_prevue'], 'echeances_statut_date_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ventes', function (Blueprint $table) {
            $table->dropIndex('ventes_client_statut_created_index');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex('clients_institut_naissance_index');
            $table->dropIndex('clients_institut_points_index');
        });

        Schema::table('echeances', function (Blueprint $table) {
            $table->dropIndex('echeances_statut_date_index');
        });
    }
};
