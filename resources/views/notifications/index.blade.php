{{--
    La page est partagée : un commercial n'a pas d'institut et n'a donc rien à
    faire dans le layout du tableau de bord. On choisit le layout selon le rôle.
--}}
@php
    $estCommercial = auth()->user()?->role === 'commercial';
@endphp

@if($estCommercial)
    <x-commercial-layout title="Notifications">
        @include('notifications._contenu')
    </x-commercial-layout>
@else
    <x-dashboard-layout>
        @include('notifications._contenu')
    </x-dashboard-layout>
@endif
