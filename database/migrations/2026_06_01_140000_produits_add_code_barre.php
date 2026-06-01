<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produits', function (Blueprint $table) {
            if (! Schema::hasColumn('produits', 'code_barre')) {
                $table->string('code_barre', 50)->nullable()->after('reference');
                $table->index(['institut_id', 'code_barre'], 'produits_institut_codebarre_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('produits', function (Blueprint $table) {
            if (Schema::hasColumn('produits', 'code_barre')) {
                $table->dropIndex('produits_institut_codebarre_idx');
                $table->dropColumn('code_barre');
            }
        });
    }
};
