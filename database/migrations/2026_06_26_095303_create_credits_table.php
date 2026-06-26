<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("credits", function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->uuid("institut_id");
            $table->uuid("vente_id")->unique();
            $table->uuid("client_id");
            $table->decimal("montant_total", 10, 0);
            $table->decimal("apport_initial", 10, 0)->default(0);
            $table->decimal("reste_a_payer", 10, 0);
            $table->integer("nb_echeances")->default(1);
            $table->enum("frequence", ["hebdomadaire","mensuelle"])->default("mensuelle");
            $table->enum("statut", ["en_cours","solde","retard","defaut"])->default("en_cours");
            $table->date("date_debut");
            $table->date("date_fin_prevue")->nullable();
            $table->text("notes")->nullable();
            $table->timestamps();
            $table->foreign("vente_id")->references("id")->on("ventes")->onDelete("cascade");
            $table->foreign("client_id")->references("id")->on("clients");
            $table->foreign("institut_id")->references("id")->on("instituts")->onDelete("cascade");
            $table->index(["institut_id", "statut"]);
            $table->index("client_id");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("credits");
    }
};
