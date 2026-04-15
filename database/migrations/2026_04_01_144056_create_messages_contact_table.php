<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages_contact', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nom');
            $table->string('email');
            $table->string('telephone', 30)->nullable();
            $table->text('message');
            $table->boolean('lu')->default(false);
            $table->string('honeypot')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages_contact');
    }
};
