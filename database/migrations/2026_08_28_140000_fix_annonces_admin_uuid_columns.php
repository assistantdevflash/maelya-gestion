<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Corriger les colonnes UUID : les IDs utilisateurs sont des UUID, pas des entiers
        Schema::table('annonces_admin', function (Blueprint $table) {
            $table->dropIndex(['expediteur_id']);
            $table->string('expediteur_id', 36)->nullable()->change();
            $table->index('expediteur_id');
        });

        Schema::table('annonce_lectures', function (Blueprint $table) {
            $table->dropUnique(['annonce_id', 'user_id']);
            $table->dropIndex(['user_id']);
            $table->string('user_id', 36)->change();
            $table->unique(['annonce_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('annonces_admin', function (Blueprint $table) {
            $table->dropIndex(['expediteur_id']);
            $table->unsignedBigInteger('expediteur_id')->nullable()->change();
            $table->index('expediteur_id');
        });

        Schema::table('annonce_lectures', function (Blueprint $table) {
            $table->dropUnique(['annonce_id', 'user_id']);
            $table->dropIndex(['user_id']);
            $table->unsignedBigInteger('user_id')->change();
            $table->unique(['annonce_id', 'user_id']);
            $table->index('user_id');
        });
    }
};
