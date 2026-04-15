<?php

namespace Database\Seeders;

use App\Models\Institut;
use App\Models\PlanAbonnement;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // ── Plans d'abonnement ──────────────────────────────────────────────────────────────────
        $plans = [
            [
                'nom'           => 'Essai gratuit',
                'slug'          => 'essai',
                'prix'          => 0,
                'duree_type'    => 'essai',
                'duree_jours'   => 14,
                'max_employes'  => 1,
                'max_instituts' => 1,
                'description'   => '14 jours pour découvrir toutes les fonctionnalités gratuitement.',
                'mis_en_avant'  => false,
                'actif'         => true,
                'ordre'         => 0,
            ],
            [
                'nom'           => 'Premium',
                'slug'          => 'premium',
                'prix'          => 4900,
                'duree_type'    => 'mensuel',
                'duree_jours'   => 30,
                'max_employes'  => 3,
                'max_instituts' => 1,
                'description'   => 'Idéal pour un institut avec une petite équipe.',
                'mis_en_avant'  => false,
                'actif'         => true,
                'ordre'         => 1,
            ],
            [
                'nom'           => 'Entreprise',
                'slug'          => 'entreprise',
                'prix'          => 9900,
                'duree_type'    => 'mensuel',
                'duree_jours'   => 30,
                'max_employes'  => null,
                'max_instituts' => null,
                'description'   => 'Multi-instituts, employés illimités, support prioritaire.',
                'mis_en_avant'  => true,
                'actif'         => true,
                'ordre'         => 2,
            ],
        ];

        foreach ($plans as $plan) {
            PlanAbonnement::updateOrCreate(['slug' => $plan['slug']], $plan);
        }

        // ── Super administrateur de la plateforme ──────────────────────────────────────────
        User::firstOrCreate(
            ['email' => 'admin@maelya-gestion.com'],
            [
                'prenom'       => 'Admin',
                'nom_famille'  => 'Maelya',
                'name'         => 'Admin Maelya',
                'email'        => 'admin@maelya-gestion.com',
                'password'     => Hash::make('AdminSecure@2026!'),
                'role'         => 'super_admin',
                'actif'        => true,
            ]
        );

        $this->command->info('✓ Plans d\'abonnement créés (' . count($plans) . ').');
        $this->command->info('✓ Super admin créé : admin@maelya-gestion.com');
    }
}
