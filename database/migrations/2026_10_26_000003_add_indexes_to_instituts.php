<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index sur les colonnes de filtrage de la liste admin des établissements.
 *
 * `proprietaire_id` est utilisé à chaque requête (groupement, sous-requêtes
 * « plan »), `type` et `derniere_activite_le` alimentent les filtres de la page.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instituts', function (Blueprint $table) {
            $table->index('proprietaire_id');
            $table->index('type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('instituts', function (Blueprint $table) {
            $table->dropIndex(['proprietaire_id']);
            $table->dropIndex(['type']);
            $table->dropIndex(['created_at']);
        });
    }
};
