<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('devis', 'titre')) {
            Schema::table('devis', function (Blueprint $table) {
                $table->string('titre', 200)->nullable()->after('numero');
            });
        }
        if (!Schema::hasColumn('factures', 'titre')) {
            Schema::table('factures', function (Blueprint $table) {
                $table->string('titre', 200)->nullable()->after('numero');
            });
        }
    }

    public function down(): void
    {
        Schema::table('devis', fn (Blueprint $t) => $t->dropColumn('titre'));
        Schema::table('factures', fn (Blueprint $t) => $t->dropColumn('titre'));
    }
};
