<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('client_photos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('institut_id')->index();
            $table->uuid('client_id')->index();
            $table->uuid('user_id')->nullable();
            $table->string('type', 20)->default('avant_apres');  // avant, apres, avant_apres, autre
            $table->string('path');
            $table->string('legende', 255)->nullable();
            $table->date('date_prise')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'date_prise']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_photos');
    }
};
