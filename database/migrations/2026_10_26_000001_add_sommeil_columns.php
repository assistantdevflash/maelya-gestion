<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mise en sommeil pour dépassement de quota après une rétrogradation de plan.
 *
 * Distinct de `actif` : une désactivation par l'admin coupe l'accès, alors
 * qu'une mise en sommeil est automatique, réversible et n'efface rien.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instituts', function (Blueprint $table) {
            $table->boolean('en_sommeil')->default(false)->after('actif');
            $table->string('sommeil_motif', 255)->nullable()->after('en_sommeil');
            $table->index('en_sommeil');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('en_sommeil')->default(false)->after('actif');
            $table->string('sommeil_motif', 255)->nullable()->after('en_sommeil');
        });
    }

    public function down(): void
    {
        Schema::table('instituts', function (Blueprint $table) {
            $table->dropIndex(['en_sommeil']);
            $table->dropColumn(['en_sommeil', 'sommeil_motif']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['en_sommeil', 'sommeil_motif']);
        });
    }
};
