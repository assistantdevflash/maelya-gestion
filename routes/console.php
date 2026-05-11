<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Expiration quotidienne des abonnements dont la date est dépassée
Schedule::command('abonnements:expirer')->dailyAt('01:00');

// Rappels J-1 pour les rendez-vous du lendemain
Schedule::command('rdv:rappels')->dailyAt('08:00');
