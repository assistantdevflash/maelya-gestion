<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instituts', function (Blueprint $table) {
            $table->boolean('boutique_active')->default(false)->after('reservation_en_ligne');
            $table->decimal('boutique_frais_livraison', 10, 2)->default(0)->after('boutique_active');
            $table->json('boutique_zones_livraison')->nullable()->after('boutique_frais_livraison');
            $table->string('boutique_delai_livraison')->nullable()->after('boutique_zones_livraison');
            $table->text('boutique_conditions')->nullable()->after('boutique_delai_livraison');
        });
    }

    public function down(): void
    {
        Schema::table('instituts', function (Blueprint $table) {
            $table->dropColumn([
                'boutique_active',
                'boutique_frais_livraison',
                'boutique_zones_livraison',
                'boutique_delai_livraison',
                'boutique_conditions',
            ]);
        });
    }
};
