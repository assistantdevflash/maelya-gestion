<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payment_transactions')) {
            return;
        }

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('reference', 50)->unique();

            $table->uuid('user_id');
            $table->uuid('institut_id')->nullable();
            $table->uuid('abonnement_id')->nullable();

            $table->enum('type', [
                'abonnement',
                'renouvellement',
                'boutique_activation',
                'boutique_renouvellement',
                'upgrade',
            ]);

            $table->decimal('amount', 10, 0);
            $table->decimal('fees', 10, 0)->default(0);
            $table->decimal('net_amount', 10, 0)->default(0);
            $table->string('currency', 3)->default('XOF');

            $table->unsignedBigInteger('payment_method_id')->nullable();
            $table->string('payment_method_code', 50)->default('bank_transfer');

            // GeniusPay / gateway data
            $table->string('gateway_reference', 100)->nullable()->index();
            $table->string('gateway_status', 30)->nullable();
            $table->text('gateway_response')->nullable();
            $table->string('checkout_url')->nullable();

            $table->enum('status', [
                'pending',
                'processing',
                'completed',
                'failed',
                'cancelled',
                'expired',
            ])->default('pending')->index();

            $table->json('metadata')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'status']);
            $table->index(['type', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
