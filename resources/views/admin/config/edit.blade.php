@extends('layouts.admin')
@section('page-title', 'Configuration')

@section('content')
<div class="space-y-6 max-w-2xl">

    <div>
        <h1 class="page-title">Configuration de la plateforme</h1>
        <p class="page-subtitle">Paramètres généraux et intégration de paiement.</p>
    </div>

    @if(session('success'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm">✓ {{ session('success') }}</div>
    @endif

    <form action="{{ route('admin.config.update') }}" method="POST" class="space-y-6">
        @csrf @method('PUT')

        {{-- CinetPay --}}
        <div class="card p-6 space-y-4">
            <div class="flex items-center gap-3 pb-3 border-b border-gray-100">
                <div class="w-8 h-8 bg-primary-100 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="font-bold text-gray-900 text-sm">CinetPay</h2>
                    <p class="text-xs text-gray-400">Passerelle de paiement mobile money (Côte d'Ivoire)</p>
                </div>
            </div>

            <div>
                <label class="form-label">API Key <span class="text-red-500">*</span></label>
                <input type="text" name="cinetpay_api_key" value="{{ old('cinetpay_api_key', $config->cinetpay_api_key ?? '') }}"
                       class="form-input @error('cinetpay_api_key') border-red-400 @enderror"
                       placeholder="xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                @error('cinetpay_api_key')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Site ID <span class="text-red-500">*</span></label>
                <input type="text" name="cinetpay_site_id" value="{{ old('cinetpay_site_id', $config->cinetpay_site_id ?? '') }}"
                       class="form-input @error('cinetpay_site_id') border-red-400 @enderror"
                       placeholder="123456789">
                @error('cinetpay_site_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Secret Key</label>
                <input type="password" name="cinetpay_secret" value="{{ old('cinetpay_secret', $config->cinetpay_secret ?? '') }}"
                       class="form-input" placeholder="••••••••••••••••">
            </div>
            <div>
                <label class="form-label">Environnement</label>
                <select name="cinetpay_env" class="form-input max-w-xs">
                    <option value="sandbox" @selected(($config->cinetpay_env ?? 'sandbox') === 'sandbox')>Sandbox (test)</option>
                    <option value="production" @selected(($config->cinetpay_env ?? '') === 'production')>Production</option>
                </select>
            </div>
        </div>

        {{-- Infos plateforme --}}
        <div class="card p-6 space-y-4">
            <div class="flex items-center gap-3 pb-3 border-b border-gray-100">
                <div class="w-8 h-8 bg-secondary-100 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-secondary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <div>
                    <h2 class="font-bold text-gray-900 text-sm">Informations plateforme</h2>
                    <p class="text-xs text-gray-400">Nom, email de contact, support</p>
                </div>
            </div>

            <div>
                <label class="form-label">Nom de la plateforme</label>
                <input type="text" name="nom_plateforme" value="{{ old('nom_plateforme', $config->nom_plateforme ?? 'Maëlya Gestion') }}"
                       class="form-input">
            </div>
            <div>
                <label class="form-label">Email de contact / support</label>
                <input type="email" name="email_contact" value="{{ old('email_contact', $config->email_contact ?? '') }}"
                       class="form-input">
            </div>
            <div>
                <label class="form-label">Email d'envoi (FROM)</label>
                <input type="email" name="email_from" value="{{ old('email_from', $config->email_from ?? '') }}"
                       class="form-input" placeholder="noreply@maelya.com">
            </div>
        </div>

        <div class="flex justify-end">
            <button class="btn-primary px-8">Enregistrer la configuration</button>
        </div>
    </form>
</div>
@endsection
