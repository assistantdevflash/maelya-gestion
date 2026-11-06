<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Paramètres de facturation de l'établissement ────────────────
        Schema::table('instituts', function (Blueprint $table) {
            // 'journaliere' = FAC-20261106-000001, 'annuelle' = FAC-2026-000001
            $table->string('facturation_sequence', 20)->default('journaliere');
            $table->boolean('facture_relance_active')->default(false);
            $table->text('facture_relance_message')->nullable();
        });

        // ── Journal des relances de factures (anti-doublon) ─────────────
        Schema::create('facture_relances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('facture_id');
            $table->uuid('institut_id');
            $table->string('canal', 20);
            $table->string('palier', 10);
            $table->timestamp('envoye_le');
            $table->timestamps();

            $table->foreign('facture_id')->references('id')->on('factures')->onDelete('cascade');
            $table->foreign('institut_id')->references('id')->on('instituts')->onDelete('cascade');
            $table->unique(['facture_id', 'palier', 'canal']);
            $table->index(['institut_id', 'envoye_le']);
        });

        // ── Avoirs : rattachement à une facture ────────────────────────
        Schema::table('avoirs', function (Blueprint $table) {
            $table->uuid('facture_id')->nullable()->after('vente_id');
            $table->index('facture_id');
        });
    }

    public function down(): void
    {
        Schema::table('avoirs', function (Blueprint $table) {
            $table->dropIndex(['facture_id']);
            $table->dropColumn('facture_id');
        });

        Schema::dropIfExists('facture_relances');

        Schema::table('instituts', function (Blueprint $table) {
            $table->dropColumn(['facturation_sequence', 'facture_relance_active', 'facture_relance_message']);
        });
    }
};
