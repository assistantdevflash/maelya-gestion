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
        // Index critiques manquants — voir docs/analyse-performance.md
        Schema::table('users', function (Blueprint $table) {
            $table->index('institut_id');
            $table->index(['role', 'actif']);
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->index('date_naissance');
        });

        Schema::table('ventes', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('client_id');
        });

        Schema::table('plans_abonnement', function (Blueprint $table) {
            $table->index(['actif', 'ordre']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['institut_id']);
            $table->dropIndex(['role', 'actif']);
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex(['date_naissance']);
        });

        Schema::table('ventes', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['client_id']);
        });

        Schema::table('plans_abonnement', function (Blueprint $table) {
            $table->dropIndex(['actif', 'ordre']);
        });
    }
};
