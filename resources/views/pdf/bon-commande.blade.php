<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bon de commande {{ $bon->numero }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1f2937; line-height: 1.4; padding: 20px 30px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #2563eb; padding-bottom: 14px; margin-bottom: 16px; }
        .header-left h1 { font-size: 20px; font-weight: 800; color: #2563eb; margin-bottom: 2px; }
        .header-left .institut { font-size: 12px; color: #6b7280; }
        .header-right { text-align: right; }
        .badge { display: inline-block; padding: 3px 12px; border-radius: 999px; font-size: 10px; font-weight: 700; text-transform: uppercase; }
        .badge-brouillon { background: #fef3c7; color: #92400e; }
        .badge-envoye { background: #dbeafe; color: #1d4ed8; }
        .badge-recu_partiel { background: #e0e7ff; color: #4338ca; }
        .badge-recu { background: #d1fae5; color: #059669; }
        .badge-annule { background: #fee2e2; color: #dc2626; }
        .section { margin-bottom: 16px; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; }
        .section-title { background: #f9fafb; padding: 6px 12px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; border-bottom: 1px solid #e5e7eb; }
        .section-body { padding: 10px 12px; }
        .grid { display: flex; gap: 12px; }
        .grid-2 > * { flex: 1; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 9px; text-transform: uppercase; color: #6b7280; padding: 5px 8px; background: #f9fafb; border-bottom: 1px solid #e5e7eb; }
        td { padding: 5px 8px; border-bottom: 1px solid #f3f4f6; font-size: 10px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: 'DejaVu Sans Mono', monospace; font-size: 10px; }
        .font-bold { font-weight: 700; }
        .footer { margin-top: 24px; padding-top: 10px; border-top: 1px solid #e5e7eb; text-align: center; font-size: 9px; color: #9ca3af; }
    </style>
</head>
<body>

    <div class="header">
        <div class="header-left">
            <h1>Bon de commande</h1>
            <p class="institut">{{ $bon->institut?->nom ?? 'Maelya Gestion' }}{{ $bon->institut?->ville ? ' — ' . $bon->institut->ville : '' }}</p>
            <p style="font-size:9px;color:#9ca3af;">N° {{ $bon->numero }}</p>
        </div>
        <div class="header-right">
            <span class="badge badge-{{ $bon->statut }}">
                {{ $bon->statut_label }}
            </span>
            <p style="font-size:9px;color:#9ca3af;margin-top:4px;">Genere le {{ now()->format('d/m/Y a H:i') }}</p>
        </div>
    </div>

    <div class="grid grid-2" style="margin-bottom:12px;">
        <div class="section">
            <div class="section-title">FOURNISSEUR</div>
            <div class="section-body">
                @if($bon->fournisseur)
                    <p class="font-bold" style="font-size:13px;">{{ $bon->fournisseur->nom }}</p>
                    @if($bon->fournisseur->contact_principal)
                    <p style="font-size:10px;">Contact : {{ $bon->fournisseur->contact_principal }}</p>
                    @endif
                    @if($bon->fournisseur->telephone)
                    <p style="font-size:10px;">Tel : {{ $bon->fournisseur->telephone }}</p>
                    @endif
                    @if($bon->fournisseur->email)
                    <p style="font-size:10px;">Email : {{ $bon->fournisseur->email }}</p>
                    @endif
                    @if($bon->fournisseur->adresse)
                    <p style="font-size:10px;color:#6b7280;">{{ $bon->fournisseur->adresse }}</p>
                    @endif
                @else
                    <p style="font-size:10px;color:#9ca3af;">Aucun fournisseur</p>
                @endif
            </div>
        </div>
        <div class="section">
            <div class="section-title">INFORMATIONS</div>
            <div class="section-body">
                <p style="font-size:10px;">Date de commande : <strong>{{ $bon->date_commande->format('d/m/Y') }}</strong></p>
                @if($bon->date_livraison_prevue)
                <p style="font-size:10px;">Livraison prevue : <strong>{{ \Carbon\Carbon::parse($bon->date_livraison_prevue)->format('d/m/Y') }}</strong></p>
                @endif
                @if($bon->user)
                <p style="font-size:10px;">Cree par : {{ $bon->user->prenom }} {{ $bon->user->nom_famille }}</p>
                @endif
                @if($bon->notes)
                <div style="background:#f9fafb;border-radius:6px;padding:8px;margin-top:6px;font-size:9px;color:#6b7280;">
                    {{ $bon->notes }}
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Lignes</div>
        <table>
            <thead>
                <tr>
                    <th>Designation</th>
                    <th class="text-center">Qte cmd.</th>
                    <th class="text-center">Qte recue</th>
                    <th class="text-center">A recevoir</th>
                    <th class="text-right">Prix HT</th>
                    <th class="text-right">Sous-total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bon->lignes as $ligne)
                <tr>
                    <td>
                        <span style="font-weight:600;">{{ $ligne->libelle }}</span>
                        @if($ligne->produit)
                        <br><span style="font-size:9px;color:#9ca3af;">Ref : {{ $ligne->produit->reference ?? '—' }}</span>
                        @endif
                    </td>
                    <td class="text-center font-mono">{{ $ligne->quantite_commandee }}</td>
                    <td class="text-center font-mono">{{ $ligne->quantite_recue }}</td>
                    <td class="text-center" style="color:#cbd5e1;">..........</td>
                    <td class="text-right font-mono">{{ number_format($ligne->prix_unitaire, 0, ',', ' ') }} F</td>
                    <td class="text-right font-mono font-bold">{{ number_format($ligne->sous_total, 0, ',', ' ') }} F</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" class="text-right font-bold" style="font-size:13px;">Total HT</td>
                    <td class="text-right font-mono font-bold" style="font-size:14px;">{{ number_format($bon->total_ht, 0, ',', ' ') }} FCFA</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="footer">
        <p>{{ $bon->institut?->nom ?? 'Maelya Gestion' }}{{ $bon->institut?->ville ? ' — ' . $bon->institut->ville : '' }} | Document genere le {{ now()->format('d/m/Y a H:i') }}</p>
    </div>

</body>
</html>
