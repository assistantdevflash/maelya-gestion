<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('facebook_events_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('institut_id');
            $table->string('event_name');
            $table->string('source');        // 'browser' ou 'server'
            $table->json('payload')->nullable();
            $table->boolean('success')->default(true);
            $table->timestamps();

            $table->index(['institut_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facebook_events_logs');
    }
};
