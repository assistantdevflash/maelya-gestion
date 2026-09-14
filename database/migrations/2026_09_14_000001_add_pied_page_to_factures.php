<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('factures', function (Blueprint $table) {
            $table->boolean('pied_page_afficher')->default(true)->after('conditions');
            $table->text('pied_page_texte')->nullable()->after('pied_page_afficher');
        });
    }

    public function down(): void
    {
        Schema::table('factures', function (Blueprint $table) {
            $table->dropColumn(['pied_page_afficher', 'pied_page_texte']);
        });
    }
};
