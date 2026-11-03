<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Horaires d'ouverture par établissement.
 *
 * Utilisés pour refuser (ou avertir) une prise de rendez-vous hors créneaux :
 * sans ces colonnes, un client pouvait réserver la vitrine à 3 h du matin ou
 * un dimanche fermé.
 *
 * `jours_ouverture` : tableau JSON de jours ISO-8601 (1 = lundi … 7 = dimanche).
 * NULL = pas de restriction (comportement historique conservé).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instituts', function (Blueprint $table) {
            if (! Schema::hasColumn('instituts', 'heure_ouverture')) {
                $table->time('heure_ouverture')->nullable()->after('adresse');
            }
            if (! Schema::hasColumn('instituts', 'heure_fermeture')) {
                $table->time('heure_fermeture')->nullable()->after('heure_ouverture');
            }
            if (! Schema::hasColumn('instituts', 'jours_ouverture')) {
                $table->json('jours_ouverture')->nullable()->after('heure_fermeture');
            }
        });
    }

    public function down(): void
    {
        Schema::table('instituts', function (Blueprint $table) {
            $table->dropColumn(['heure_ouverture', 'heure_fermeture', 'jours_ouverture']);
        });
    }
};
