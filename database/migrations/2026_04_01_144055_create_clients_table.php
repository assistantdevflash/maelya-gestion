<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('institut_id');
            $table->string('prenom', 50);
            $table->string('nom', 50);
            $table->string('telephone', 30);
            $table->string('email')->nullable();
            $table->date('date_naissance')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('institut_id')->references('id')->on('instituts')->onDelete('cascade');
            $table->index(['institut_id', 'actif']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
