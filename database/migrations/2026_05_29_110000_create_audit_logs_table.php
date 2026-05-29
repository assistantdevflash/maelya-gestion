<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('institut_id')->nullable()->index();
            $table->uuid('user_id')->nullable()->index();
            $table->string('action', 50)->index();                  // created, updated, deleted, login, custom
            $table->string('subject_type', 100)->nullable();        // App\Models\Vente
            $table->uuid('subject_id')->nullable();
            $table->string('label', 255)->nullable();               // libellé lisible (ex: "Vente V-1234")
            $table->json('changes')->nullable();                    // ['old' => [...], 'new' => [...]]
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
            $table->index(['institut_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
