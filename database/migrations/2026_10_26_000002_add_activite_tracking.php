<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Suivi de l'activité réelle d'un établissement.
 *
 * `derniere_activite_le` est mis à jour à chaque opération métier
 * (vente, RDV, client, commande…) — pas à chaque requête HTTP — afin de
 * distinguer un établissement réellement exploité d'un compte simplement
 * connecté.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instituts', function (Blueprint $table) {
            $table->timestamp('derniere_activite_le')->nullable()->after('en_sommeil');
            $table->string('derniere_activite_type', 50)->nullable()->after('derniere_activite_le');
            $table->string('derniere_activite_user_id', 36)->nullable()->after('derniere_activite_type');
            $table->index('derniere_activite_le');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('derniere_connexion_le')->nullable()->after('en_sommeil');
        });
    }

    public function down(): void
    {
        Schema::table('instituts', function (Blueprint $table) {
            $table->dropIndex(['derniere_activite_le']);
            $table->dropColumn([
                'derniere_activite_le',
                'derniere_activite_type',
                'derniere_activite_user_id',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('derniere_connexion_le');
        });
    }
};
