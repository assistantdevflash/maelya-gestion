<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instituts', function (Blueprint $table) {
            $table->decimal('tva_taux', 5, 2)->default(0)->after('boutique_conditions');
            $table->boolean('tva_applicable')->default(false)->after('tva_taux');
            $table->string('rccm', 50)->nullable()->after('tva_applicable');
            $table->string('numero_fiscal', 50)->nullable()->after('rccm');
            $table->string('pdf_template', 30)->default('classique')->after('numero_fiscal');
        });
    }

    public function down(): void
    {
        Schema::table('instituts', function (Blueprint $table) {
            $table->dropColumn(['tva_taux', 'tva_applicable', 'rccm', 'numero_fiscal', 'pdf_template']);
        });
    }
};
