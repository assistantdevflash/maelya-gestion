<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instituts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nom', 100);
            $table->string('slug')->unique();
            $table->string('email')->unique();
            $table->string('telephone', 30);
            $table->string('ville', 100);
            $table->enum('type', ['salon_coiffure', 'institut_beaute', 'nail_bar', 'spa', 'barbier', 'autre']);
            $table->string('logo')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instituts');
    }
};
