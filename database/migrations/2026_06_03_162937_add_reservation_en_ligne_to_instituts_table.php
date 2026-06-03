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
        Schema::table('instituts', function (Blueprint $table) {
            $table->boolean('reservation_en_ligne')->default(false)->after('vitrine_active');
        });
    }

    public function down(): void
    {
        Schema::table('instituts', function (Blueprint $table) {
            $table->dropColumn('reservation_en_ligne');
        });
    }
};
