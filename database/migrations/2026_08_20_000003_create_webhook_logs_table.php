<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('webhook_logs')) {
            return;
        }

        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 50);
            $table->string('event', 100)->nullable();
            $table->string('webhook_id', 100)->nullable()->index();
            $table->uuid('transaction_id')->nullable()->index();
            $table->text('payload');
            $table->text('headers')->nullable();
            $table->string('signature', 255)->nullable();
            $table->boolean('signature_valid')->default(false);
            $table->enum('status', ['pending', 'processed', 'failed', 'ignored'])->default('pending');
            $table->text('processing_error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['provider', 'event']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};
