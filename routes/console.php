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

// Rappels J-7 des anniversaires clients
Schedule::command('clients:rappel-anniversaire')->dailyAt('09:00');

// Détection quotidienne des anomalies (stock, RDV doublons, ventes inhabituelles)
Schedule::command('maelya:anomalies')->dailyAt('07:00');

// Détection quotidienne des crédits en retard (8h)
Schedule::command('credits:detecter-retards')->dailyAt('08:00');

// Rappels J-1 pour les échéances de crédit du lendemain
Schedule::command('credits:rappels-echeances')->dailyAt('08:00');

// Cascade de relances : J-3, J+1, J+7, J+15, J+30
Schedule::command('credits:relances')->dailyAt('08:30');

// Purge hebdomadaire du journal d'activité (conserve 90 jours)
Schedule::command('audit:purge --days=90')->weeklyOn(0, '02:00');

// Bilan financier du mois écoulé, envoyé le 1er de chaque mois
Schedule::command('rapports:mensuel')->monthlyOn(1, '07:00');
