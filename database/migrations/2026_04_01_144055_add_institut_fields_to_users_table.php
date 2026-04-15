<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('institut_id')->nullable()->after('id');
            $table->string('prenom', 50)->after('name');
            $table->string('nom_famille', 50)->after('prenom');
            $table->string('telephone', 30)->nullable()->after('email');
            $table->enum('role', ['super_admin', 'admin', 'employe'])->default('admin')->after('telephone');
            $table->string('avatar')->nullable()->after('role');
            $table->boolean('actif')->default(true)->after('avatar');
            $table->foreign('institut_id')->references('id')->on('instituts')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['institut_id']);
            $table->dropColumn(['institut_id', 'prenom', 'nom_famille', 'telephone', 'role', 'avatar', 'actif']);
        });
    }
};
