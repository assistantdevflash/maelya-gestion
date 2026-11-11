@php
    // Permet <x-commercial-layout title="Notifications">…</x-commercial-layout>
    // startSection avec un contenu définit la section en une passe : inutile
    // (et impossible via la façade) d'appeler stopSection().
    if (! empty($title)) {
        \Illuminate\Support\Facades\View::startSection('title', $title);
    }
@endphp
@include('layouts.commercial')
