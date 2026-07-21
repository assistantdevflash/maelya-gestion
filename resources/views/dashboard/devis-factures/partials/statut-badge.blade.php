@php
$colors = [
    'devis' => [
        'brouillon' => 'bg-gray-100 text-gray-700 dark:bg-slate-700 dark:text-slate-300',
        'envoye' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
        'accepte' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
        'refuse' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
        'expire' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
    ],
    'facture' => [
        'brouillon' => 'bg-gray-100 text-gray-700 dark:bg-slate-700 dark:text-slate-300',
        'en_attente' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
        'partiellement_payee' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
        'payee' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
        'annulee' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
    ],
];
$labels = [
    'brouillon' => 'Brouillon', 'envoye' => 'Envoyé', 'accepte' => 'Accepté', 'refuse' => 'Refusé', 'expire' => 'Expiré',
    'en_attente' => 'En attente', 'partiellement_payee' => 'Partiel', 'payee' => 'Payée', 'annulee' => 'Annulée',
];
$c = $colors[$type][$statut] ?? 'bg-gray-100 text-gray-700';
@endphp
<span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-semibold {{ $c }}">{{ $labels[$statut] ?? $statut }}</span>
