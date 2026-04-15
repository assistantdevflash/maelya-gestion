<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('abonnements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('institut_id');
            $table->uuid('plan_id');
            $table->decimal('montant', 10, 0);
            $table->string('reference_cinetpay')->nullable()->unique();
            $table->enum('statut', ['en_attente', 'actif', 'expire', 'annule'])->default('en_attente');
            $table->date('debut_le')->nullable();
            $table->date('expire_le')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->foreign('institut_id')->references('id')->on('instituts')->onDelete('cascade');
            $table->foreign('plan_id')->references('id')->on('plans_abonnement');
            $table->index(['institut_id', 'statut']);
            $table->index('expire_le');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abonnements');
    }
};
