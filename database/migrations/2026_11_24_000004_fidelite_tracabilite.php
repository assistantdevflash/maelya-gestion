<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Traçabilité de l'historique des points de fidélité.
 *
 *  • `user_id` : quel membre de l'équipe a fait l'ajustement. Un programme qui
 *    distribue des remises doit pouvoir dire qui a crédité qui.
 *
 *  • `code_reduction_id` : le code généré lors d'une conversion. Jusqu'ici
 *    l'historique disait « Conversion en code de réduction » sans jamais dire
 *    lequel, rendant le rapprochement impossible.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('historique_points', 'user_id')) {
            Schema::table('historique_points', function (Blueprint $table) {
                $table->uuid('user_id')->nullable();
            });
        }

        if (! Schema::hasColumn('historique_points', 'code_reduction_id')) {
            Schema::table('historique_points', function (Blueprint $table) {
                $table->uuid('code_reduction_id')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('historique_points', 'code_reduction_id')) {
            Schema::table('historique_points', function (Blueprint $table) {
                $table->dropColumn('code_reduction_id');
            });
        }

        if (Schema::hasColumn('historique_points', 'user_id')) {
            Schema::table('historique_points', function (Blueprint $table) {
                $table->dropColumn('user_id');
            });
        }
    }
};
