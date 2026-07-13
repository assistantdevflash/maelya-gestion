<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produits', function (Blueprint $table) {
            $table->boolean('visible_boutique')->default(true)->after('actif');
            $table->boolean('featured')->default(false)->after('visible_boutique');
            $table->string('description_courte', 255)->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('produits', function (Blueprint $table) {
            $table->dropColumn(['visible_boutique', 'featured', 'description_courte']);
        });
    }
};
