<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Origine d'un parrainage commercial.
 *
 * Jusqu'ici un parrainage ne pouvait naître que d'une inscription avec le
 * code d'un commercial. Un super admin doit pouvoir rattacher un compte
 * client existant à un commercial : on garde donc la trace de l'origine
 * (`code` ou `manuel`), de l'établissement qui a servi de point d'entrée et
 * du super admin qui a fait le rattachement.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('commercial_parrainages')) {
            return;
        }

        Schema::table('commercial_parrainages', function (Blueprint $table) {
            if (!Schema::hasColumn('commercial_parrainages', 'institut_id')) {
                $table->uuid('institut_id')->nullable()
                    ->comment('Établissement utilisé pour le rattachement');
            }

            if (!Schema::hasColumn('commercial_parrainages', 'source')) {
                $table->string('source', 20)->default('code')
                    ->comment('code = inscription avec le code, manuel = attribution par un super admin');
            }

            if (!Schema::hasColumn('commercial_parrainages', 'attribue_par')) {
                $table->uuid('attribue_par')->nullable()
                    ->comment('→ users.id, super admin auteur d\'un rattachement manuel');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('commercial_parrainages')) {
            return;
        }

        Schema::table('commercial_parrainages', function (Blueprint $table) {
            foreach (['institut_id', 'source', 'attribue_par'] as $colonne) {
                if (Schema::hasColumn('commercial_parrainages', $colonne)) {
                    $table->dropColumn($colonne);
                }
            }
        });
    }
};
