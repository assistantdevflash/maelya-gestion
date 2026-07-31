<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instituts', function (Blueprint $table) {
            $table->string('couleur_primaire', 7)->default('#7c3aed')->after('boutique_conditions');
            $table->string('couleur_secondaire', 7)->default('#ec4899')->after('couleur_primaire');
            $table->string('couleur_accent', 7)->default('#f59e0b')->after('couleur_secondaire');
        });
    }

    public function down(): void
    {
        Schema::table('instituts', function (Blueprint $table) {
            $table->dropColumn(['couleur_primaire', 'couleur_secondaire', 'couleur_accent']);
        });
    }
};
