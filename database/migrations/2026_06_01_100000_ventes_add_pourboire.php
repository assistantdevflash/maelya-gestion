<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventes', function (Blueprint $table) {
            if (! Schema::hasColumn('ventes', 'pourboire')) {
                $table->unsignedInteger('pourboire')->default(0)->after('remise');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ventes', function (Blueprint $table) {
            if (Schema::hasColumn('ventes', 'pourboire')) {
                $table->dropColumn('pourboire');
            }
        });
    }
};
