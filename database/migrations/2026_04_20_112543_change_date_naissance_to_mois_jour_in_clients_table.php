<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ajouter une colonne temporaire MM-DD
        Schema::table('clients', function (Blueprint $table) {
            $table->string('naissance_md', 5)->nullable();
        });

        // Copier jour/mois depuis la date existante
        DB::statement("UPDATE clients SET naissance_md = CASE
            WHEN date_naissance IS NOT NULL AND date_naissance != ''
            THEN substr(date_naissance, 6, 5)
            ELSE NULL
        END");

        // Supprimer l'ancienne colonne et renommer
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('date_naissance');
        });
        Schema::table('clients', function (Blueprint $table) {
            $table->renameColumn('naissance_md', 'date_naissance');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('naissance_md', 5)->nullable();
        });
        DB::statement("UPDATE clients SET naissance_md = date_naissance");
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('date_naissance');
        });
        Schema::table('clients', function (Blueprint $table) {
            $table->renameColumn('naissance_md', 'date_naissance');
        });
    }
};
