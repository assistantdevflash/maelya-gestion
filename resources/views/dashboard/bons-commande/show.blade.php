<x-dashboard-layout>
    <x-slot name="title">Bon de commande {{ $bon->numero }}</x-slot>

    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-display font-bold text-gray-900">Bon de commande {{ $bon->numero }}</h1>
                <p class="text-sm text-gray-500 mt-1">
                    {{ $bon->fournisseur->nom ?? 'Sans fournisseur' }} •
                    {{ $bon->date_commande->format('d/m/Y') }} •
                    <span class="badge-{{ ['brouillon'=>'warning','envoye'=>'info','recu_partiel'=>'warning','recu'=>'success','annule'=>'danger'][$bon->statut] ?? 'primary' }}">
                        {{ $bon->statut_label }}
                    </span>
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('dashboard.bons-commande.pdf', $bon) }}" class="btn-outline text-xs py-1.5 px-3" title="Telecharger PDF">
                    🖨️ PDF
                </a>
                @if($bon->fournisseur?->email)
                <form method="POST" action="{{ route('dashboard.bons-commande.envoyer-email', $bon) }}" class="inline">
                    @csrf
                    <button type="submit" class="btn-outline text-xs py-1.5 px-3" title="Envoyer par email au fournisseur">
                        📧 Email
                    </button>
                </form>
                @endif
                @if($bon->fournisseur?->telephone)
                @php
                    $waTel = preg_replace('/[^0-9+]/', '', $bon->fournisseur->telephone);
                    $waTel = ltrim($waTel, '+');
                    if (str_starts_with($waTel, '0')) $waTel = '225' . $waTel;
                    $pdfUrl = route('dashboard.bons-commande.pdf', $bon);
                    $waMsg = rawurlencode("Bonjour " . ($bon->fournisseur->contact_principal ?: $bon->fournisseur->nom) . ",\n\n"
                        . "Veuillez trouver notre bon de commande " . $bon->numero . " du " . $bon->date_commande->format('d/m/Y') . " :\n"
                        . "Montant : " . number_format($bon->total_ht, 0, ',', ' ') . " FCFA\n\n"
                        . "Telecharger le PDF : " . $pdfUrl . "\n\n"
                        . "Merci,\n" . ($bon->institut?->nom ?? 'Maelya Gestion'));
                @endphp
                <a href="https://wa.me/{{ $waTel }}?text={{ $waMsg }}" target="_blank" class="btn-outline text-xs py-1.5 px-3 text-green-600 border-green-200 hover:bg-green-50" title="Partager par WhatsApp">
                    💬 WhatsApp
                </a>
                @endif
                <a href="{{ route('dashboard.bons-commande.index') }}" class="btn-outline text-xs py-1.5 px-3">← Retour</a>
            </div>
        </div>

        @if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif

        <div class="card p-5">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-3 py-2 text-left">Produit / Libellé</th>
                        <th class="px-3 py-2 text-right">Qté commandée</th>
                        <th class="px-3 py-2 text-right">Qté reçue</th>
                        <th class="px-3 py-2 text-right">Prix HT</th>
                        <th class="px-3 py-2 text-right">Sous-total</th>
                        @if(in_array($bon->statut, ['envoye','recu_partiel','brouillon']))
                            <th class="px-3 py-2 text-right w-32">À recevoir</th>
                        @endif
                    </tr>
                </thead>
                <form method="POST" action="{{ route('dashboard.bons-commande.recevoir', $bon) }}">
                    @csrf
                    <tbody class="divide-y divide-gray-100">
                        @foreach($bon->lignes as $ligne)
                            <tr>
                                <td class="px-3 py-2">
                                    <div class="font-semibold">{{ $ligne->libelle }}</div>
                                    @if($ligne->produit)<div class="text-xs text-gray-500">Stock actuel : {{ $ligne->produit->stock }}</div>@endif
                                </td>
                                <td class="px-3 py-2 text-right">{{ $ligne->quantite_commandee }}</td>
                                <td class="px-3 py-2 text-right">{{ $ligne->quantite_recue }}</td>
                                <td class="px-3 py-2 text-right">{{ number_format($ligne->prix_unitaire, 0, ',', ' ') }} F</td>
                                <td class="px-3 py-2 text-right font-semibold">{{ number_format($ligne->sous_total, 0, ',', ' ') }} F</td>
                                @if(in_array($bon->statut, ['envoye','recu_partiel','brouillon']))
                                    <td class="px-3 py-2">
                                        <input type="number" name="recus[{{ $ligne->id }}]" value="0" min="0" max="{{ $ligne->quantite_commandee - $ligne->quantite_recue }}" class="form-input text-xs text-right">
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr class="font-bold">
                            <td colspan="4" class="px-3 py-3 text-right">Total HT :</td>
                            <td class="px-3 py-3 text-right text-lg">{{ number_format($bon->total_ht, 0, ',', ' ') }} F</td>
                            @if(in_array($bon->statut, ['envoye','recu_partiel','brouillon']))
                                <td class="px-3 py-3 text-right">
                                    <button type="submit" class="btn-primary text-xs">Réceptionner</button>
                                </td>
                            @endif
                        </tr>
                    </tfoot>
                </form>
            </table>
        </div>

        @if($bon->notes)
            <div class="card p-4 text-sm"><strong>Notes :</strong> {{ $bon->notes }}</div>
        @endif

        <div class="flex gap-2">
            @if($bon->statut === 'brouillon')
                <form method="POST" action="{{ route('dashboard.bons-commande.envoyer', $bon) }}">@csrf<button class="btn-outline">Marquer comme envoyé</button></form>
            @endif
            @if(!in_array($bon->statut, ['recu', 'annule']))
                <form method="POST" action="{{ route('dashboard.bons-commande.annuler', $bon) }}" id="form-annuler-bon-{{ $bon->id }}">@csrf
                    <button type="button"
                            onclick="window.dispatchEvent(new CustomEvent('confirm-action',{detail:{formId:'form-annuler-bon-{{ $bon->id }}',title:'Annuler ce bon de commande ?',message:'Le bon passera au statut annul\u00e9.',confirmLabel:'Annuler le bon',confirmClass:'!bg-amber-600 hover:!bg-amber-700',danger:true}}))"
                            class="btn-outline text-red-600">Annuler le bon</button>
                </form>
            @endif
            <form method="POST" action="{{ route('dashboard.bons-commande.destroy', $bon) }}" id="form-del-bon-{{ $bon->id }}">@csrf @method('DELETE')
                <button type="button"
                        onclick="window.dispatchEvent(new CustomEvent('confirm-delete',{detail:{formId:'form-del-bon-{{ $bon->id }}',title:'Supprimer ce bon de commande ?',message:'Ce bon sera d\u00e9finitivement supprim\u00e9.'}}))"
                        class="btn-outline text-red-600">Supprimer</button>
            </form>
        </div>
    </div>
</x-dashboard-layout>
