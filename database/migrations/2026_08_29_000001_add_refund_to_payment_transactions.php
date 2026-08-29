<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payment_transactions')) {
            return;
        }

        Schema::table('payment_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_transactions', 'refunded_at')) {
                $table->timestamp('refunded_at')->nullable()->after('expires_at');
            }
            if (!Schema::hasColumn('payment_transactions', 'refund_reference')) {
                $table->string('refund_reference', 100)->nullable()->after('refunded_at');
            }
            if (!Schema::hasColumn('payment_transactions', 'refunded_amount')) {
                $table->decimal('refunded_amount', 10, 0)->default(0)->after('refund_reference');
            }
        });

        // Ajouter 'refunded' au statut ENUM (MySQL)
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE payment_transactions MODIFY COLUMN status ENUM('pending','processing','completed','failed','cancelled','expired','refunded') NOT NULL DEFAULT 'pending'");
        } elseif ($driver === 'sqlite') {
            // SQLite ne supporte pas la modification d'ENUM — la colonne devient TEXT automatiquement
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('payment_transactions')) {
            return;
        }

        Schema::table('payment_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('payment_transactions', 'refunded_at')) {
                $table->dropColumn('refunded_at');
            }
            if (Schema::hasColumn('payment_transactions', 'refund_reference')) {
                $table->dropColumn('refund_reference');
            }
            if (Schema::hasColumn('payment_transactions', 'refunded_amount')) {
                $table->dropColumn('refunded_amount');
            }
        });

        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE payment_transactions MODIFY COLUMN status ENUM('pending','processing','completed','failed','cancelled','expired') NOT NULL DEFAULT 'pending'");
        }
    }
};
