<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Suivi d'envoi des campagnes d'emails administratives.
 *
 * L'envoi se fait désormais par lots successifs : la campagne existe avant le
 * premier message, sa progression est visible, et un envoi interrompu peut
 * être repris là où il s'est arrêté.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_campagnes', function (Blueprint $table) {
            if (! Schema::hasColumn('email_campagnes', 'send_mode')) {
                $table->string('send_mode', 20)->nullable();
            }
            if (! Schema::hasColumn('email_campagnes', 'statut')) {
                $table->string('statut', 20)->default('termine');
            }
            if (! Schema::hasColumn('email_campagnes', 'nb_total')) {
                $table->unsignedInteger('nb_total')->default(0);
            }
            if (! Schema::hasColumn('email_campagnes', 'cibles')) {
                $table->json('cibles')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('email_campagnes', function (Blueprint $table) {
            foreach (['send_mode', 'statut', 'nb_total', 'cibles'] as $colonne) {
                if (Schema::hasColumn('email_campagnes', $colonne)) {
                    $table->dropColumn($colonne);
                }
            }
        });
    }
};
