<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table("ventes", function (Blueprint $table) {
            $table->decimal("montant_paye", 10, 0)->default(0)->after("total");
            $table->enum("credit_statut", ["en_cours","solde","retard","defaut"])->nullable()->after("statut");
        });
    }

    public function down(): void
    {
        Schema::table("ventes", function (Blueprint $table) {
            $table->dropColumn(["montant_paye", "credit_statut"]);
        });
    }
};
