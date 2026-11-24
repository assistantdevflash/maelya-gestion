@switch($statut)
    @case('actif')
        <span class="badge badge-success text-xs">Actif</span>
        @break
    @case('epuise')
        <span class="badge badge-warning text-xs">Épuisé</span>
        @break
    @case('expire')
        <span class="badge badge-danger text-xs">Expiré</span>
        @break
    @case('programme')
        <span class="badge badge-info text-xs">Programmé</span>
        @break
    @default
        <span class="badge text-xs bg-gray-100 text-gray-500 dark:bg-slate-700 dark:text-slate-400">Inactif</span>
@endswitch
