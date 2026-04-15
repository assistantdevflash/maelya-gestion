<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('institut_id');
            $table->uuid('client_id')->nullable();
            $table->uuid('user_id');
            $table->string('numero')->unique();
            $table->decimal('total', 10, 0);
            $table->enum('mode_paiement', ['cash', 'mobile_money', 'mixte'])->default('cash');
            $table->string('reference_paiement')->nullable();
            $table->decimal('montant_cash', 10, 0)->default(0);
            $table->decimal('montant_mobile', 10, 0)->default(0);
            $table->enum('statut', ['validee', 'annulee'])->default('validee');
            $table->text('notes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->foreign('institut_id')->references('id')->on('instituts')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->nullOnDelete();
            $table->index(['institut_id', 'created_at']);
            $table->index(['institut_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventes');
    }
};
