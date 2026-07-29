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
            $table->string('facebook_pixel_id')->nullable()->after('boutique_zones_livraison');
            $table->text('facebook_access_token')->nullable()->after('facebook_pixel_id');
            $table->string('facebook_test_code')->nullable()->after('facebook_access_token');
            $table->string('facebook_pixel_name')->nullable()->after('facebook_test_code');
            $table->timestamp('facebook_connected_at')->nullable()->after('facebook_pixel_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instituts', function (Blueprint $table) {
            $table->dropColumn(['facebook_pixel_id', 'facebook_access_token', 'facebook_test_code', 'facebook_pixel_name', 'facebook_connected_at']);
        });
    }
};
