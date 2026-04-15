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
            $table->uuid('proprietaire_id')->nullable()->after('id');
            $table->foreign('proprietaire_id')->references('id')->on('users')->nullOnDelete();
        });

        // Relier les instituts existants à leur admin
        \DB::table('instituts')->get()->each(function ($institut) {
            $admin = \DB::table('users')
                ->where('institut_id', $institut->id)
                ->where('role', 'admin')
                ->first();
            if ($admin) {
                \DB::table('instituts')
                    ->where('id', $institut->id)
                    ->update(['proprietaire_id' => $admin->id]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('instituts', function (Blueprint $table) {
            $table->dropForeign(['proprietaire_id']);
            $table->dropColumn('proprietaire_id');
        });
    }
};
