<?php

namespace App\Http\Controllers;

use App\Models\MessageContact;
use App\Models\PlanAbonnement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class LandingController extends Controller
{
    public function index()
    {
        $plans = PlanAbonnement::where('actif', true)->orderBy('ordre')->get();
        return view('landing.index', [
            'plans' => $plans,
            'title' => 'Maëlya Gestion — La solution de gestion des indépendants et PME de service',
            'metaDescription' => 'Caisse, clients, rendez-vous, stocks et finances dans une seule application. Sans engagement, sans complexité. Découvrez pourquoi +500 établissements font confiance à Maëlya Gestion.',
        ]);
    }

    public function apropos()
    {
        return view('landing.apropos', [
            'title' => 'À propos',
            'metaDescription' => 'Découvrez Maëlya Gestion, la solution conçue pour simplifier la gestion des établissements de service en Côte d\'Ivoire. Notre mission : vous faire gagner du temps.',
        ]);
    }

    public function faq()
    {
        return view('landing.faq', [
            'title' => 'Questions fréquentes',
            'metaDescription' => 'Trouvez les réponses à vos questions sur Maëlya Gestion : inscription, abonnements, fonctionnalités et sécurité.',
        ]);
    }

    public function contact()
    {
        return view('landing.contact', [
            'title' => 'Contact',
            'metaDescription' => 'Contactez l\'équipe Maëlya Gestion. Nous répondons sous 24h. Email, WhatsApp ou formulaire de contact.',
        ]);
    }

    public function sendContact(Request $request)
    {
        // Honeypot anti-spam
        if ($request->filled('website')) {
            return redirect()->route('contact')->with('success', 'Message envoyé !');
        }

        $data = $request->validate([
            'nom'       => ['required', 'string', 'max:150', 'not_regex:/[\r\n<>{}|]/'],
            'email'     => ['required', 'email:rfc,dns', 'max:255', 'not_regex:/[\r\n]/'],
            'telephone' => ['nullable', 'string', 'max:30', 'regex:/^[+\d\s().\-]*$/'],
            'message'   => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'nom.required'      => 'Votre nom est requis.',
            'nom.not_regex'     => 'Le nom contient des caractères non autorisés.',
            'email.required'    => 'Votre email est requis.',
            'email.not_regex'   => 'L\'adresse email contient des caractères non autorisés.',
            'telephone.regex'   => 'Le numéro de téléphone contient des caractères non autorisés.',
            'message.required'  => 'Votre message est requis.',
            'message.min'       => 'Votre message doit contenir au moins 10 caractères.',
        ]);

        // Sanitisation : supprimer toute balise HTML résiduelle
        $nom     = Str::limit(strip_tags($data['nom']), 150);
        $message = Str::limit(strip_tags($data['message']), 2000);
        $email   = filter_var($data['email'], FILTER_SANITIZE_EMAIL);

        MessageContact::create([
            'nom'       => $nom,
            'email'     => $email,
            'telephone' => $data['telephone'] ?? null,
            'message'   => $message,
            'honeypot'  => $request->input('website'),
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('contact')->with('success', 'Votre message a bien été envoyé ! Nous vous répondrons sous 24h.');
    }

    public function mentionsLegales()
    {
        return view('landing.mentions', [
            'title' => 'Mentions légales',
            'metaDescription' => 'Mentions légales de Maëlya Gestion : éditeur, hébergement, propriété intellectuelle et protection des données.',
            'noindex' => true,
        ]);
    }

    public function sitemap()
    {
        $pages = [
            ['url' => route('home'),     'priority' => '1.0', 'changefreq' => 'weekly', 'lastmod' => now()->toIso8601String()],
            ['url' => route('about'),    'priority' => '0.8', 'changefreq' => 'monthly', 'lastmod' => now()->toIso8601String()],
            ['url' => route('faq'),      'priority' => '0.8', 'changefreq' => 'monthly', 'lastmod' => now()->toIso8601String()],
            ['url' => route('contact'),  'priority' => '0.7', 'changefreq' => 'monthly', 'lastmod' => now()->toIso8601String()],
        ];

        // Ajouter toutes les pages vitrine actives
        $instituts = \App\Models\Institut::where('vitrine_active', true)
            ->where('actif', true)
            ->get(['slug', 'updated_at']);

        foreach ($instituts as $institut) {
            $pages[] = [
                'url' => route('vitrine.show', $institut->slug),
                'priority' => '0.8',
                'changefreq' => 'daily',
                'lastmod' => $institut->updated_at->toIso8601String(),
            ];
        }

        return response()
            ->view('landing.sitemap', compact('pages'))
            ->header('Content-Type', 'application/xml');
    }
}
