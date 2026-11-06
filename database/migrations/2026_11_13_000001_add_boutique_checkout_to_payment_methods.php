<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            // Autorise ce moyen de paiement au moment de la commande boutique.
            // Distinct de is_active, qui autorise les paiements côté
            // établissement (abonnements, options).
            $table->boolean('boutique_checkout_enabled')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropColumn('boutique_checkout_enabled');
        });
    }
};
