<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            // Anti-doublon de la relance « panier abandonné »
            $table->timestamp('relance_envoyee_at')->nullable()->after('stock_decremente_at');
        });
    }

    public function down(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->dropColumn('relance_envoyee_at');
        });
    }
};
