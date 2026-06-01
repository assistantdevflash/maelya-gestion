<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caisse_brouillons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('institut_id');
            $table->uuid('user_id');
            $table->uuid('client_id')->nullable();
            $table->string('libelle')->nullable();
            $table->json('panier');
            $table->unsignedInteger('total_indicatif')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->foreign('institut_id')->references('id')->on('instituts')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->nullOnDelete();
            $table->index(['institut_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caisse_brouillons');
    }
};
