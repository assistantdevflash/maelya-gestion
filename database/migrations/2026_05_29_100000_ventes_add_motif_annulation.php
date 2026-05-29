<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ventes', function (Blueprint $table) {
            $table->string('motif_annulation', 255)->nullable()->after('statut');
            $table->timestamp('annulee_le')->nullable()->after('motif_annulation');
            $table->uuid('annulee_par')->nullable()->after('annulee_le');
        });
    }

    public function down(): void
    {
        Schema::table('ventes', function (Blueprint $table) {
            $table->dropColumn(['motif_annulation', 'annulee_le', 'annulee_par']);
        });
    }
};
