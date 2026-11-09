<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instituts', function (Blueprint $table) {
            // Par défaut on refuse une vente sans stock : c'est le
            // comportement attendu d'une gestion de stock. Certains
            // établissements (coiffure, prestations à la commande) vendent
            // parfois un article non encore réceptionné et doivent pouvoir
            // passer outre, en connaissance de cause.
            $table->boolean('stock_negatif_autorise')->default(false)->after('seuil_alerte');
        });
    }

    public function down(): void
    {
        Schema::table('instituts', function (Blueprint $table) {
            $table->dropColumn('stock_negatif_autorise');
        });
    }
};
