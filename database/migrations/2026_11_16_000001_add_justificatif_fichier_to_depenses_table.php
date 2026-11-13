<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pièce jointe du justificatif de dépense.
 *
 * La colonne `justificatif` existait déjà mais n'était alimentée par aucun
 * formulaire : le compteur « sans justificatif » valait donc toujours le
 * nombre de dépenses. On garde `justificatif` pour la référence écrite
 * (« Reçu n° 412 – Boutique Adjamé ») et on ajoute ici le chemin du fichier
 * (photo ou PDF du reçu) sur le disque public.
 *
 * Pas de `->after()` : sans effet sur SQLite, et donc jamais testé avant la
 * production.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('depenses', 'justificatif_fichier')) {
            return;
        }

        Schema::table('depenses', function (Blueprint $table) {
            $table->string('justificatif_fichier')->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('depenses', 'justificatif_fichier')) {
            return;
        }

        Schema::table('depenses', function (Blueprint $table) {
            $table->dropColumn('justificatif_fichier');
        });
    }
};
