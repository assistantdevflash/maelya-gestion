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
        Schema::table('client_photos', function (Blueprint $table) {
            if (!Schema::hasColumn('client_photos', 'mime_type')) {
                $table->string('mime_type', 100)->nullable()->after('path');
            }
            if (!Schema::hasColumn('client_photos', 'extension')) {
                $table->string('extension', 10)->nullable()->after('mime_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_photos', function (Blueprint $table) {
            if (Schema::hasColumn('client_photos', 'mime_type')) {
                $table->dropColumn('mime_type');
            }
            if (Schema::hasColumn('client_photos', 'extension')) {
                $table->dropColumn('extension');
            }
        });
    }
};
