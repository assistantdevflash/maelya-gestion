<?php

/**
 * Contenu éditorial des pages « secteur » du site public.
 *
 * Une entrée par code présent dans la table `etablissement_types`. Un type
 * ajouté depuis l'espace super admin sans entrée ici reste publié : il reçoit
 * alors le contenu générique `_defaut`, complété par son libellé. Le
 * référencement n'est donc jamais bloqué par une liste figée dans le code.
 */
return [

    /**
     * Équivalences de codes.
     *
     * Le code d'un type est dérivé du libellé saisi dans l'administration. Un
     * même métier peut donc arriver sous plusieurs codes selon la formulation
     * choisie. Cette table renvoie vers l'entrée éditoriale canonique, pour
     * qu'aucune page ne tombe dans le contenu générique par simple différence
     * d'orthographe.
     */
    '_equivalences' => [
        'salle_de_sport'            => 'salle_de_sport_et_fitness',
        'salle_de_sport_fitness'    => 'salle_de_sport_et_fitness',
        'salle_sport_fitness'       => 'salle_de_sport_et_fitness',
        'sport_fitness'             => 'salle_de_sport_et_fitness',
        'salle_de_sport_et_fitness' => 'salle_de_sport_et_fitness',
        'fitness'                   => 'salle_de_sport_et_fitness',
        'sport'                     => 'salle_de_sport_et_fitness',
        'coiffure'                  => 'salon_coiffure',
        'salon_de_coiffure'         => 'salon_coiffure',
        'institut_de_beaute'        => 'institut_beaute',
        'auto_ecoles'               => 'auto_ecole',
        'cabinet_medical_paramedical' => 'cabinet_medical',
        'atelier_service_technique' => 'atelier_technique',
        'pressing_laverie'          => 'pressing',
        'informatique'              => 'informatique_telephonie',
        'telephonie'                => 'informatique_telephonie',
        'optique'                   => 'optiquelunetterie',
        'optique_lunetterie'        => 'optiquelunetterie',
        'boutique_de_mode'          => 'boutique_mode',
        'centre_de_formation'       => 'centre_formation',
        'business_centre'           => 'business_center',
        'depot_de_gaz'              => 'depot_gaz',
    ],

    // ── Contenu de repli pour tout type non décrit ci-dessous ───────────────
    '_defaut' => [
        'emoji' => '🏢',
        'intro' => "Caisse, clients, rendez-vous, stocks et finances : Maëlya Gestion réunit tout ce qu'il faut pour piloter un établissement de service, sans cahier ni tableur.",
        'points' => [
            "Encaissez en quelques secondes, en espèces ou par Mobile Money, avec un ticket remis au client.",
            "Retrouvez l'historique complet de chaque client : visites, achats, crédits en cours.",
            "Suivez vos stocks et recevez une alerte avant la rupture.",
            "Connaissez votre chiffre d'affaires et votre résultat du jour, sans attendre la fin du mois.",
        ],
        'question' => null,
        'reponse' => null,
    ],

    // ── Beauté et bien-être ─────────────────────────────────────────────────
    'salon_coiffure' => [
        'emoji' => '✂️',
        'intro' => "Coupes, colorations, brushings : un salon vit au rythme de ses rendez-vous. Maëlya Gestion réunit la caisse, l'agenda, la clientèle et les stocks de produits capillaires dans une seule application, utilisable depuis le téléphone du salon.",
        'points' => [
            "Encaissez une prestation en moins de 30 secondes, en espèces ou par Mobile Money.",
            "Partagez l'agenda entre coiffeuses et prévenez automatiquement le client la veille.",
            "Suivez la consommation de shampooings et de colorations, avec alerte avant la rupture.",
            "Rattachez chaque vente à une coiffeuse pour connaître sa production réelle.",
            "Fidélisez grâce à une carte de fidélité et à l'historique des visites.",
        ],
        'question' => "Comment suivre la production de chaque coiffeuse ?",
        'reponse' => "Chaque vente est rattachée à l'employée qui l'a réalisée. Vous comparez les prestations, le chiffre d'affaires et le panier moyen de chacune, sans aucun calcul.",
    ],

    'barbier' => [
        'emoji' => '💈',
        'intro' => "Un barbershop tourne vite : peu de prestations, beaucoup de passages. Maëlya Gestion vous fait encaisser en quelques secondes et garder la trace de chaque client, sans ralentir la chaise.",
        'points' => [
            "Une caisse ultra rapide, pensée pour les salons à fort débit.",
            "Une carte de fidélité numérique : le 10e passage offert, sans carton à tamponner.",
            "Agenda des coupes et rappels automatiques au client.",
            "Suivi des produits vendus (cires, huiles, soins) et alertes de stock.",
            "Recette du jour et du mois visible d'un seul coup d'œil.",
        ],
        'question' => "La caisse est-elle utilisable entre deux coupes ?",
        'reponse' => "Oui. Elle est conçue pour le mobile : trois tapes suffisent et vous restez au poste, sans passer derrière un ordinateur.",
    ],

    'institut_beaute' => [
        'emoji' => '💅',
        'intro' => "Soins du visage, manucure, épilation : un institut vend des prestations à durée variable et des produits. Maëlya Gestion gère les deux, avec les rendez-vous et l'historique de chaque cliente.",
        'points' => [
            "Un agenda des soins avec durée par prestation et rappels automatiques.",
            "Une fiche cliente complète : type de peau, allergies, soins réalisés, produits achetés.",
            "Une caisse mixte prestations + produits, en espèces ou Mobile Money.",
            "Un stock de produits suivi, avec alerte de rupture et coût moyen pondéré.",
            "Des rapports de chiffre d'affaires par prestation et par esthéticienne.",
        ],
        'question' => "Puis-je vendre un produit en même temps qu'une prestation ?",
        'reponse' => "Oui. Une même vente peut contenir plusieurs prestations et plusieurs produits : le ticket les détaille et les rapports les séparent.",
    ],

    'centre_esthetique' => [
        'emoji' => '💆',
        'intro' => "Un centre esthétique combine des actes techniques, des forfaits et des séries de séances. Maëlya Gestion suit les forfaits entamés, les rendez-vous et la rentabilité de chaque cabine.",
        'points' => [
            "Suivi des forfaits et des séries de séances, avec le solde restant par cliente.",
            "Agenda par cabine et par praticienne.",
            "Acomptes et soldes encaissables en plusieurs fois.",
            "Historique des actes réalisés, exportable pour un médecin ou une assurance.",
            "Rapports de rentabilité par acte et par praticienne.",
        ],
        'question' => "Comment gérer un forfait de dix séances payé d'avance ?",
        'reponse' => "Le forfait est enregistré une seule fois. Chaque séance consommée décrémente le solde, et la cliente comme vous voyez à tout moment le nombre de séances restantes.",
    ],

    'onglerie' => [
        'emoji' => '💅',
        'intro' => "Une onglerie vit de la régularité : les clientes reviennent toutes les trois semaines. Maëlya Gestion gère les rendez-vous, la fidélité et la vente de produits sans tenir de cahier.",
        'points' => [
            "Agenda avec durée par prestation et rappels automatiques.",
            "Historique des poses par cliente, avec références et photos.",
            "Carte de fidélité et offres sur les passages réguliers.",
            "Caisse rapide, en espèces ou Mobile Money.",
            "Stock de produits et alertes avant rupture.",
        ],
        'question' => "Comment faire revenir mes clientes régulièrement ?",
        'reponse' => "La carte de fidélité et l'historique des visites vous indiquent qui revient et à quelle fréquence. Vous relancez ainsi au bon moment, sans tenir de cahier.",
    ],

    // ── Sport et remise en forme ───────────────────────────────────────────
    'salle_de_sport_et_fitness' => [
        'emoji' => '💪',
        'intro' => "Séances à l'unité, abonnements, cours collectifs : une salle de sport vit de la régularité de ses membres. Maëlya Gestion encaisse les formules, suit chaque membre, ses paiements et le stock de la boutique, sans cahier ni tableur.",
        'points' => [
            "Encaissez une séance, un mois d'abonnement ou un cours en quelques secondes, en espèces ou par Mobile Money.",
            "Une fiche membre complète : historique des passages, des paiements et des formules souscrites.",
            "Les abonnements payés en plusieurs fois passent par une vente à crédit, avec échéancier et suivi des impayés.",
            "Un agenda pour les cours collectifs et les séances de coaching, avec rappel envoyé aux participants.",
            "Le stock des boissons, compléments et accessoires suivi, avec alerte avant la rupture.",
            "Une carte de fidélité et des codes de réduction pour les parrainages et les séances d'essai.",
        ],
        'question' => "Puis-je gérer un abonnement payé en plusieurs fois ?",
        'reponse' => "Oui. L'abonnement est enregistré comme une vente à crédit : l'échéancier se crée automatiquement, chaque versement est tracé, et le tableau de bord signale les impayés à relancer.",
    ],

    // ── Commerce ───────────────────────────────────────────────────────────
    'boutique_mode' => [
        'emoji' => '👗',
        'intro' => "Tailles, couleurs, modèles : une boutique de mode vit de son stock. Maëlya Gestion suit chaque article, la valeur du stock et les ventes du jour, avec une boutique en ligne activable en un clic.",
        'points' => [
            "Un catalogue avec variantes de taille et de couleur, stock par article.",
            "Une caisse rapide, par code-barres ou par recherche du nom.",
            "Une boutique en ligne activable : vos clients commandent, vous gérez les commandes.",
            "Des alertes avant rupture sur les modèles qui tournent.",
            "La marge réelle par article, une fois le coût d'achat renseigné.",
        ],
        'question' => "Puis-je ouvrir une boutique en ligne en plus de ma caisse ?",
        'reponse' => "Oui. La boutique en ligne se configure depuis votre espace, avec votre logo et vos couleurs. Les commandes arrivent dans le même tableau de bord que les ventes en magasin.",
    ],

    'commerce' => [
        'emoji' => '🛒',
        'intro' => "Alimentation, quincaillerie, dépôt : un commerce vit de son stock et de sa caisse. Maëlya Gestion tient les deux ensemble, avec le coût d'achat pour connaître la vraie marge.",
        'points' => [
            "Un catalogue avec code-barres, stock et prix d'achat.",
            "Une caisse rapide et un ticket remis au client.",
            "Des alertes de rupture sur les articles qui tournent.",
            "Ventes à crédit et suivi des impayés.",
            "La marge réelle par article grâce au coût moyen pondéré.",
        ],
        'question' => "Puis-je utiliser un lecteur de code-barres ?",
        'reponse' => "Oui. Le champ de recherche accepte la saisie d'un scanner USB ou Bluetooth : l'article est ajouté immédiatement à la vente.",
    ],

    // ── Services ───────────────────────────────────────────────────────────
    'auto_ecole' => [
        'emoji' => '🚗',
        'intro' => "Élèves, leçons, examens : une auto-école jongle avec des plannings serrés et des paiements échelonnés. Maëlya Gestion suit chaque élève, son solde et ses heures de conduite.",
        'points' => [
            "Une fiche élève avec solde de leçons et historique des paiements.",
            "Le planning des moniteurs et des véhicules, avec rappels de leçon.",
            "L'encaissement échelonné et le suivi des impayés.",
            "Le suivi du parcours : code, conduite, présentation à l'examen.",
            "Des rapports de chiffre d'affaires par moniteur et par formule.",
        ],
        'question' => "Comment suivre un élève qui paie en plusieurs fois ?",
        'reponse' => "Chaque versement est enregistré sur la fiche de l'élève. Le solde restant et le nombre de leçons consommées se mettent à jour automatiquement, avec une alerte sur les impayés.",
    ],

    'cabinet_medical' => [
        'emoji' => '🩺',
        'intro' => "Consultations, actes, dossiers : un cabinet médical ou paramédical a besoin d'ordre et de discrétion. Maëlya Gestion organise l'agenda, les honoraires et le suivi des patients sans alourdir la consultation.",
        'points' => [
            "Un agenda par praticien, avec durée de consultation paramétrable.",
            "Un dossier patient : antécédents, actes réalisés, documents joints.",
            "L'encaissement des honoraires et le suivi des restes à charge.",
            "Des devis et factures conformes, exportables en PDF.",
            "Des rapports d'activité par acte et par praticien.",
        ],
        'question' => "Les dossiers patients sont-ils cloisonnés par cabinet ?",
        'reponse' => "Oui. Chaque établissement ne voit que ses propres données. Un cabinet voisin, même hébergé sur la même plateforme, n'y a aucun accès.",
    ],

    'optiquelunetterie' => [
        'emoji' => '👓',
        'intro' => "Montures, verres, ordonnances : l'optique exige de la précision, dans le dossier client comme dans le stock. Maëlya Gestion tient les deux, avec les devis et la prise en charge mutuelle.",
        'points' => [
            "Une fiche client avec ordonnance et historique des corrections.",
            "Un stock de montures et de verres avec alerte de rupture.",
            "Des devis et factures intégrant la prise en charge mutuelle.",
            "Le suivi des commandes fournisseurs et des livraisons.",
            "La marge réelle par monture et par paire.",
        ],
        'question' => "Puis-je joindre une ordonnance au dossier du client ?",
        'reponse' => "Oui. Une photo ou un PDF se joint directement à la fiche du client et reste consultable à chaque visite.",
    ],

    'atelier_technique' => [
        'emoji' => '🔧',
        'intro' => "Réparations, devis, pièces détachées : un atelier travaille sur des ordres de réparation et des délais. Maëlya Gestion relie l'accueil du client, le devis, la pièce utilisée et la facture.",
        'points' => [
            "Des devis et factures reliés à l'intervention, avec le statut de réparation.",
            "Un stock de pièces détachées avec alerte de rupture.",
            "Le suivi du temps passé et de la marge par intervention.",
            "L'historique des réparations par client et par appareil.",
            "Une caisse pour les interventions réglées immédiatement.",
        ],
        'question' => "Puis-je envoyer un devis avant de commencer la réparation ?",
        'reponse' => "Oui. Le devis est créé depuis le dossier du client, envoyé en PDF ou par WhatsApp, puis converti en facture une fois validé.",
    ],

    'informatique_telephonie' => [
        'emoji' => '💻',
        'intro' => "Vente de matériel, réparation, installation : l'activité informatique mêle stock, service et garanties. Maëlya Gestion relie l'intervention, la pièce utilisée et la facture.",
        'points' => [
            "Un stock de matériel et de pièces avec alerte de rupture.",
            "Une fiche d'intervention : diagnostic, pièces, garantie.",
            "Des devis et factures reliés à la réparation.",
            "Le suivi des garanties et des retours en garantie.",
            "La marge par intervention et par vente.",
        ],
        'question' => "Comment suivre une garantie de trois mois ?",
        'reponse' => "La fiche d'intervention porte la durée de garantie. La date de fin est calculée automatiquement et reste consultable depuis le dossier du client.",
    ],

    'lavage_auto' => [
        'emoji' => '🚿',
        'intro' => "Un lavage auto enchaîne les passages et les formules. Maëlya Gestion encaisse vite, compte les véhicules et suit les abonnements sans papier.",
        'points' => [
            "Une caisse rapide par formule de lavage, en espèces ou Mobile Money.",
            "Des abonnements et carnets de lavages avec solde restant.",
            "Une fiche véhicule : plaque, modèle, historique des passages.",
            "Le suivi de la production par employé.",
            "La recette du jour et du mois en temps réel.",
        ],
        'question' => "Comment gérer un carnet de dix lavages payé d'avance ?",
        'reponse' => "Le carnet est enregistré sur la fiche du véhicule. Chaque lavage consommé décrémente le solde, affiché au moment de l'encaissement.",
    ],

    'pressing' => [
        'emoji' => '👕',
        'intro' => "Au pressing, chaque pièce compte : un vêtement égaré, c'est un client perdu. Maëlya Gestion suit les dépôts, les retraits et les paiements, pièce par pièce.",
        'points' => [
            "Un ticket de dépôt listant les pièces et la date de retrait promise.",
            "Le suivi des statuts : déposé, en traitement, prêt, retiré.",
            "L'encaissement à la commande ou au retrait, avec suivi des impayés.",
            "Une fiche client avec l'historique des dépôts.",
            "La recette du jour et le volume de pièces traitées.",
        ],
        'question' => "Comment savoir quels vêtements sont prêts à être retirés ?",
        'reponse' => "Chaque commande possède un statut que vous changez d'un tap. La liste des commandes prêtes s'affiche à l'accueil et le client peut être prévenu automatiquement.",
    ],

    'depot_gaz' => [
        'emoji' => '🔥',
        'intro' => "Un dépôt de gaz vit de la rotation des bouteilles. Maëlya Gestion suit les entrées, les sorties, les consignes et les crédits clients, sans cahier.",
        'points' => [
            "Un stock de bouteilles par format, avec alerte avant rupture.",
            "Une caisse rapide, en espèces ou Mobile Money.",
            "Le suivi des consignes et des bouteilles prêtées.",
            "Les ventes à crédit avec échéancier et relances.",
            "La recette du jour et la marge par format.",
        ],
        'question' => "Comment suivre les bouteilles consignées chez les clients ?",
        'reponse' => "Chaque sortie est rattachée au client avec le nombre de bouteilles consignées. Le solde se met à jour au retour et le tableau de bord liste les consignes en cours.",
    ],

    // ── Formation, édition, événementiel ───────────────────────────────────
    'centre_formation' => [
        'emoji' => '📚',
        'intro' => "Inscriptions, sessions, présences : un centre de formation suit des promotions entières. Maëlya Gestion gère les apprenants, les encaissements et les attestations, sans tableur.",
        'points' => [
            "Une fiche apprenant avec formation, session et solde des frais.",
            "L'encaissement échelonné et les relances des impayés.",
            "Le suivi des présences et des attestations remises.",
            "Des devis et factures pour les entreprises clientes.",
            "Des rapports de chiffre d'affaires par formation et par session.",
        ],
        'question' => "Puis-je facturer une entreprise qui envoie plusieurs salariés ?",
        'reponse' => "Oui. Une facture unique est rattachée à l'entreprise, en y listant les apprenants concernés, et son règlement se suit comme celui de n'importe quel client.",
    ],

    'imprimerie' => [
        'emoji' => '🖨️',
        'intro' => "Un travail d'impression se chiffre au devis avant d'exister. Maëlya Gestion relie devis, commande, consommation de papier et facture, pour ne plus travailler à perte.",
        'points' => [
            "Un devis détaillé avec quantités, options et délai de livraison.",
            "La conversion du devis validé en facture, en un clic.",
            "Un stock de consommables (papier, encre) avec alerte de rupture.",
            "Le suivi des travaux en cours et des livraisons.",
            "La marge par commande, coût matière inclus.",
        ],
        'question' => "Puis-je envoyer un devis par WhatsApp ?",
        'reponse' => "Oui. Le devis est généré en PDF et partageable directement depuis l'application, sur WhatsApp ou par email.",
    ],

    'business_center' => [
        'emoji' => '💼',
        'intro' => "Salles, postes, services : un business center vend du temps et de l'espace. Maëlya Gestion facture les prestations, suit les contrats et les encaissements récurrents.",
        'points' => [
            "Des devis et factures pour les entreprises clientes.",
            "Le suivi des contrats récurrents et des échéances.",
            "Une caisse pour les prestations ponctuelles : impression, scan, salle.",
            "Des relances sur les factures impayées.",
            "Des rapports de chiffre d'affaires par service.",
        ],
        'question' => "Puis-je suivre des factures récurrentes ?",
        'reponse' => "Oui. Devis et factures conservent leur historique, et le tableau de bord signale les factures non réglées à relancer.",
    ],

    'evenementiel' => [
        'emoji' => '🎉',
        'intro' => "Un événement se prépare par devis, se livre par prestation et se solde par facture. Maëlya Gestion suit les trois étapes, avec la marge de chaque projet.",
        'points' => [
            "Un devis détaillé par prestation, avec options et délais.",
            "Acomptes et soldes encaissés en plusieurs fois.",
            "Le suivi des projets : confirmé, en cours, livré, facturé.",
            "Un stock de matériel et de consommables.",
            "La marge par événement, coûts inclus.",
        ],
        'question' => "Puis-je encaisser un acompte à la réservation ?",
        'reponse' => "Oui. L'acompte est enregistré sur le devis, qui affiche le solde restant dû jusqu'à la facturation finale.",
    ],

];
