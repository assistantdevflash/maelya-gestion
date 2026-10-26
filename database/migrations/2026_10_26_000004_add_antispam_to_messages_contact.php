<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Détection du spam sur le formulaire de contact.
 *
 * Les messages suspects sont conservés (et non supprimés) afin de pouvoir
 * contrôler les faux positifs, mais marqués `est_spam` pour ne plus polluer
 * le compteur de non-lus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages_contact', function (Blueprint $table) {
            $table->boolean('est_spam')->default(false)->after('lu');
            $table->unsignedTinyInteger('spam_score')->default(0)->after('est_spam');
            $table->string('spam_raison', 255)->nullable()->after('spam_score');
            $table->string('user_agent', 255)->nullable()->after('ip_address');
            $table->index('est_spam');
        });
    }

    public function down(): void
    {
        Schema::table('messages_contact', function (Blueprint $table) {
            $table->dropIndex(['est_spam']);
            $table->dropColumn(['est_spam', 'spam_score', 'spam_raison', 'user_agent']);
        });
    }
};
