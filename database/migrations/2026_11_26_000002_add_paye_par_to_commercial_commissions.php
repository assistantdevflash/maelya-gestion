<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trace du paiement d'une commission commerciale.
 *
 * On savait qu'une commission était payée, pas par qui. `notes_paiement`
 * existait déjà mais n'était jamais renseigné.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('commercial_commissions')) {
            return;
        }

        if (!Schema::hasColumn('commercial_commissions', 'paye_par')) {
            Schema::table('commercial_commissions', function (Blueprint $table) {
                $table->uuid('paye_par')->nullable()
                    ->comment('→ users.id, super admin qui a marqué la commission payée');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('commercial_commissions')
            && Schema::hasColumn('commercial_commissions', 'paye_par')) {
            Schema::table('commercial_commissions', function (Blueprint $table) {
                $table->dropColumn('paye_par');
            });
        }
    }
};
