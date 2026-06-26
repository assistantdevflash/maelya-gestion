<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::create("echeances", function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->uuid("credit_id"); $table->uuid("institut_id");
            $table->integer("numero"); $table->date("date_prevue");
            $table->decimal("montant", 10, 0); $table->decimal("montant_paye", 10, 0)->default(0);
            $table->date("date_paiement")->nullable(); $table->uuid("encaisse_par")->nullable();
            $table->enum("statut", ["en_attente","payee","retard","annulee"])->default("en_attente");
            $table->timestamps();
            $table->foreign("credit_id")->references("id")->on("credits")->onDelete("cascade");
            $table->foreign("institut_id")->references("id")->on("instituts")->onDelete("cascade");
            $table->foreign("encaisse_par")->references("id")->on("users");
            $table->index(["date_prevue", "statut"]); $table->index("credit_id");
        });
    }
    public function down(): void { Schema::dropIfExists("echeances"); }
};
