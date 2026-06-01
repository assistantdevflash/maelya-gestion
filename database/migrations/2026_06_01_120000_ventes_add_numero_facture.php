<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventes', function (Blueprint $table) {
            if (! Schema::hasColumn('ventes', 'numero_facture')) {
                $table->string('numero_facture', 30)->nullable()->after('numero');
                $table->unique(['institut_id', 'numero_facture'], 'ventes_institut_facture_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ventes', function (Blueprint $table) {
            if (Schema::hasColumn('ventes', 'numero_facture')) {
                $table->dropUnique('ventes_institut_facture_unique');
                $table->dropColumn('numero_facture');
            }
        });
    }
};
