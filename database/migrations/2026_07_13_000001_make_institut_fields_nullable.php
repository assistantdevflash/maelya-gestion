<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instituts', function (Blueprint $table) {
            $table->string('telephone', 30)->nullable()->change();
            $table->string('email')->nullable()->change();
            $table->string('ville', 100)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('instituts', function (Blueprint $table) {
            $table->string('telephone', 30)->nullable(false)->change();
            $table->string('email')->nullable(false)->change();
            $table->string('ville', 100)->nullable(false)->change();
        });
    }
};
