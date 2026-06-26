<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::create("paiements_credit", function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->uuid("credit_id"); $table->uuid("echeance_id")->nullable(); $table->uuid("institut_id");
            $table->decimal("montant", 10, 0);
            $table->enum("mode_paiement", ["cash","mobile_money","carte"]);
            $table->string("reference", 100)->nullable();
            $table->uuid("encaisse_par"); $table->text("notes")->nullable();
            $table->timestamp("created_at")->useCurrent();
            $table->foreign("credit_id")->references("id")->on("credits")->onDelete("cascade");
            $table->foreign("echeance_id")->references("id")->on("echeances")->onDelete("set null");
            $table->foreign("institut_id")->references("id")->on("instituts")->onDelete("cascade");
            $table->foreign("encaisse_par")->references("id")->on("users");
            $table->index("credit_id");
        });
    }
    public function down(): void { Schema::dropIfExists("paiements_credit"); }
};
