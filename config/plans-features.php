<?php

/**
 * Matrice des fonctionnalités accessibles par plan d'abonnement.
 *
 * Clés = slug du plan (PlanAbonnement::slug).
 * Valeurs = liste des features autorisées.
 * '*' signifie "accès à toutes les fonctionnalités".
 *
 * Pour ajouter une feature : ajoute le slug dans le plan voulu, puis utilise
 * $user->aFonctionnalite('ma_feature') ou le middleware feature:ma_feature.
 */
return [

    // ── Liste des plans → features ──────────────────────────────────────────
    'plans' => [

        // Essai gratuit : accès complet pendant 14 jours
        'essai' => ['*'],

        // Basic : petit salon de coiffure / onglerie individuelle
        'basic' => [
            'dashboard',
            'mon_institut',
            'prestations',
            'caisse',
            'historique',
            'historique_export_pdf',
            'abonnement',
            'mes_transactions',
            'parrainage',
        ],

        // Premium : institut complet
        'premium' => [
            'dashboard',
            'dashboard_complet',
            'mon_institut',
            'prestations',
            'caisse',
            'caisse_client',
            'caisse_code_promo',
            'caisse_impression',
            'historique',
            'historique_export_pdf',
            'clients',
            'codes_reduction',
            'fidelite',
            'finances',
            'stock',
            'produits',
            'equipe',
            'abonnement',
            'mes_transactions',
            'parrainage',
        ],

        // Premium+ : tout + multi-instituts
        'premium-plus' => ['*'],

        // Ancien alias "entreprise" (rétrocompatibilité)
        'entreprise' => ['*'],
    ],

    // ── Métadonnées pour la page d'upgrade ──────────────────────────────────
    // Affichées sur /abonnement/upgrade?feature=clients
    'meta' => [

        'clients' => [
            'titre' => 'Fichier client',
            'plan_requis' => 'premium',
            'icon' => 'users',
            'accroche' => 'Construisez une vraie relation avec votre clientèle',
            'description' => 'Gardez l\'historique de vos clientes, suivez leurs visites, fêtez leurs anniversaires automatiquement et créez une vraie fidélisation.',
            'avantages' => [
                'Fiche complète : prénom, contact, anniversaire, notes',
                'Historique des visites et du chiffre d\'affaires par cliente',
                'Cadeaux d\'anniversaire automatiques',
                'Recherche rapide depuis la caisse',
            ],
        ],

        'codes_reduction' => [
            'titre' => 'Codes de réduction',
            'plan_requis' => 'premium',
            'icon' => 'tag',
            'accroche' => 'Boostez vos ventes avec des promotions ciblées',
            'description' => 'Créez des codes promo personnalisés (pourcentage ou montant fixe), limitez leur utilisation, et fidélisez vos clientes avec des avantages exclusifs.',
            'avantages' => [
                'Codes en pourcentage ou montant fixe',
                'Date de validité et limite d\'utilisation',
                'Codes nominatifs (par cliente) ou globaux',
                'Impression sur ticket pour vos clientes',
            ],
        ],

        'fidelite' => [
            'titre' => 'Programme de fidélité',
            'plan_requis' => 'premium',
            'icon' => 'star',
            'accroche' => 'Récompensez vos meilleures clientes',
            'description' => 'Mettez en place un programme de points : chaque visite rapporte des points, et au seuil atteint vos clientes reçoivent un cadeau.',
            'avantages' => [
                'Points automatiques à chaque encaissement',
                'Seuil et récompense personnalisables',
                'Génération automatique du code cadeau',
                'Suivi des clientes proches du seuil',
            ],
        ],

        'finances' => [
            'titre' => 'Point financier & Dépenses',
            'plan_requis' => 'premium',
            'icon' => 'chart',
            'accroche' => 'Pilotez votre rentabilité au quotidien',
            'description' => 'Suivez vos revenus, vos dépenses (loyer, fournitures, salaires…) et votre bénéfice réel mois par mois. Exportez vos rapports en PDF.',
            'avantages' => [
                'Saisie des dépenses par catégorie',
                'Calcul automatique du bénéfice net',
                'Rapports mensuels et annuels',
                'Export PDF pour votre comptable',
            ],
        ],

        'stock' => [
            'titre' => 'Gestion des stocks',
            'plan_requis' => 'premium',
            'icon' => 'box',
            'accroche' => 'Ne perdez plus une vente à cause d\'une rupture',
            'description' => 'Suivez votre stock de produits en temps réel, recevez des alertes en cas de rupture imminente, et corrigez vos inventaires en quelques clics.',
            'avantages' => [
                'Stock mis à jour automatiquement à chaque vente',
                'Alertes seuil critique en temps réel',
                'Entrées de stock et corrections d\'inventaire',
                'Catalogue produits par catégorie',
            ],
        ],

        'produits' => [
            'titre' => 'Catalogue produits',
            'plan_requis' => 'premium',
            'icon' => 'box',
            'accroche' => 'Vendez aussi des produits, pas seulement des prestations',
            'description' => 'Créez un catalogue de produits (shampoings, faux ongles, cosmétiques…) pour les vendre directement en caisse en plus de vos prestations.',
            'avantages' => [
                'Catalogue illimité par catégorie',
                'Stock mis à jour automatiquement',
                'Vendable en caisse en un clic',
                'Suivi des marges et des bestsellers',
            ],
        ],

        'equipe' => [
            'titre' => 'Mon équipe',
            'plan_requis' => 'premium',
            'icon' => 'users-group',
            'accroche' => 'Faites grandir votre salon',
            'description' => 'Créez des comptes pour vos employées avec leurs propres identifiants. Elles encaissent en caisse et vous gardez le contrôle des données sensibles.',
            'avantages' => [
                'Comptes employées avec rôle limité',
                'Accès uniquement à la caisse',
                'Suivi des ventes par employée',
                'Désactivation en un clic',
            ],
        ],

        'caisse_client' => [
            'titre' => 'Sélection cliente en caisse',
            'plan_requis' => 'premium',
            'icon' => 'users',
            'accroche' => 'Associez chaque encaissement à une cliente',
            'description' => 'Pour utiliser cette fonctionnalité, vous avez besoin du fichier client, inclus dans le plan Premium.',
            'avantages' => [
                'Recherche rapide depuis la caisse',
                'Création de cliente à la volée',
                'Historique de visites par cliente',
                'Ouvre l\'accès aux codes promo et fidélité',
            ],
        ],

        'caisse_code_promo' => [
            'titre' => 'Codes promo en caisse',
            'plan_requis' => 'premium',
            'icon' => 'tag',
            'accroche' => 'Appliquez des réductions au moment du paiement',
            'description' => 'Saisissez un code promo en caisse pour appliquer instantanément une réduction sur le ticket de votre cliente.',
            'avantages' => [
                'Validation en temps réel',
                'Pourcentage ou montant fixe',
                'Anti-fraude (limites & validité)',
                'Trace conservée sur le ticket',
            ],
        ],

        'caisse_impression' => [
            'titre' => 'Impression du ticket',
            'plan_requis' => 'premium',
            'icon' => 'printer',
            'accroche' => 'Donnez un ticket de caisse professionnel à vos clientes',
            'description' => 'Générez et imprimez un ticket de caisse PDF immédiatement après l\'encaissement, avec le détail du panier, les modes de paiement et le code promo appliqué.',
            'avantages' => [
                'PDF prêt à imprimer en un clic',
                'Logo et coordonnées de votre institut',
                'Détail panier + modes de paiement',
                'Réimpression depuis l\'historique',
            ],
        ],

        'multi_instituts' => [
            'titre' => 'Multi-instituts',
            'plan_requis' => 'premium-plus',
            'icon' => 'building',
            'accroche' => 'Gérez tous vos salons depuis un seul compte',
            'description' => 'Créez plusieurs instituts, basculez de l\'un à l\'autre en un clic, et consolidez vos statistiques globales. Idéal pour les chaînes de salons.',
            'avantages' => [
                'Instituts illimités',
                'Bascule instantanée entre instituts',
                'Statistiques consolidées',
                'Équipe dédiée par institut',
            ],
        ],
    ],
];
