{{-- Sélecteurs Jour + Mois pour date de naissance --}}
@php
    $valeur = $valeur ?? null; // format attendu : 'MM-DD' ou null
    $prefixName = $prefixName ?? 'date_naissance';
    [$mois, $jour] = $valeur ? explode('-', $valeur) : ['', ''];
    $moisFr = [
        '01' => 'Janvier', '02' => 'Février', '03' => 'Mars',
        '04' => 'Avril',   '05' => 'Mai',      '06' => 'Juin',
        '07' => 'Juillet', '08' => 'Août',     '09' => 'Septembre',
        '10' => 'Octobre', '11' => 'Novembre', '12' => 'Décembre',
    ];
@endphp

<div class="grid grid-cols-2 gap-2" x-data="{
    mois: '{{ $mois }}',
    jour: '{{ $jour }}',
    get valeur() { return this.mois && this.jour ? this.mois + '-' + this.jour : ''; }
}">
    <select name="{{ $prefixName }}_mois" x-model="mois"
            @change="$refs.hidden.value = valeur"
            class="form-input">
        <option value="">Mois</option>
        @foreach($moisFr as $num => $nom)
            <option value="{{ $num }}" {{ $mois === $num ? 'selected' : '' }}>{{ $nom }}</option>
        @endforeach
    </select>
    <select name="{{ $prefixName }}_jour" x-model="jour"
            @change="$refs.hidden.value = valeur"
            class="form-input">
        <option value="">Jour</option>
        @for($d = 1; $d <= 31; $d++)
            @php $dStr = str_pad($d, 2, '0', STR_PAD_LEFT) @endphp
            <option value="{{ $dStr }}" {{ $jour === $dStr ? 'selected' : '' }}>{{ $d }}</option>
        @endfor
    </select>
    <input type="hidden" name="{{ $prefixName }}" x-ref="hidden"
           :value="valeur" value="{{ $valeur ?? '' }}">
</div>
