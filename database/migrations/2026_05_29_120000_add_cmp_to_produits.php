<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('produits', function (Blueprint $table) {
            $table->integer('cout_moyen_pondere')->default(0)->after('prix_achat');
        });

        Schema::table('mouvements_stock', function (Blueprint $table) {
            $table->integer('prix_unitaire')->nullable()->after('quantite'); // prix d'achat lors d'une entrée
        });
    }

    public function down(): void
    {
        Schema::table('produits', function (Blueprint $table) {
            $table->dropColumn('cout_moyen_pondere');
        });
        Schema::table('mouvements_stock', function (Blueprint $table) {
            $table->dropColumn('prix_unitaire');
        });
    }
};
