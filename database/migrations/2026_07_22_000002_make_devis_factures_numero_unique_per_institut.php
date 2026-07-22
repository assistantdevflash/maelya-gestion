<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Devis : changer UNIQUE(numero) -> UNIQUE(institut_id, numero)
        Schema::table('devis', function (Blueprint $table) {
            $table->dropUnique('devis_numero_unique');
            $table->unique(['institut_id', 'numero'], 'devis_institut_numero_unique');
        });

        // Factures : idem
        Schema::table('factures', function (Blueprint $table) {
            $table->dropUnique('factures_numero_unique');
            $table->unique(['institut_id', 'numero'], 'factures_institut_numero_unique');
        });
        // Commandes : idem
        Schema::table('commandes', function (Blueprint $table) {
            $table->dropUnique('commandes_numero_unique');
            $table->unique(['institut_id', 'numero'], 'commandes_institut_numero_unique');
        });
    }

    public function down(): void
    {
        Schema::table('devis', function (Blueprint $table) {
            $table->dropUnique('devis_institut_numero_unique');
            $table->unique('numero', 'devis_numero_unique');
        });

        Schema::table('factures', function (Blueprint $table) {
            $table->dropUnique('factures_institut_numero_unique');
            $table->unique('numero', 'factures_numero_unique');
        });

        Schema::table('commandes', function (Blueprint $table) {
            $table->dropUnique('commandes_institut_numero_unique');
            $table->unique('numero', 'commandes_numero_unique');
        });
    }
};
