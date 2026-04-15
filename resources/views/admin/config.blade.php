@extends('layouts.admin')
@section('page-title', 'Configuration')

@section('content')
<div class="space-y-6">

    <div>
        <h1 class="page-title">Configuration de la plateforme</h1>
        <p class="page-subtitle">Paramètres CinetPay et options globales.</p>
    </div>

    <form method="POST" action="{{ route('admin.config.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- CinetPay --}}
        <div class="card p-6 space-y-4">
            <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Paiement — CinetPay</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">API Key</label>
                    <input type="text" name="cinetpay_api_key" class="form-input"
                           value="{{ old('cinetpay_api_key', $config['cinetpay_api_key']) }}"
                           placeholder="Votre clé API CinetPay">
                </div>
                <div>
                    <label class="form-label">Site ID</label>
                    <input type="text" name="cinetpay_site_id" class="form-input"
                           value="{{ old('cinetpay_site_id', $config['cinetpay_site_id']) }}"
                           placeholder="Votre Site ID CinetPay">
                </div>
            </div>

            <div>
                <label class="form-label">Mode</label>
                <select name="cinetpay_mode" class="form-select">
                    <option value="sandbox" @selected(($config['cinetpay_mode'] ?? 'sandbox') === 'sandbox')>Sandbox (test)</option>
                    <option value="production" @selected(($config['cinetpay_mode'] ?? '') === 'production')>Production</option>
                </select>
            </div>
        </div>

        {{-- Inscriptions --}}
        <div class="card p-6 space-y-4">
            <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Inscriptions</h2>
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="hidden" name="inscriptions_ouvertes" value="0">
                <input type="checkbox" name="inscriptions_ouvertes" value="1" class="w-4 h-4 accent-primary-600"
                    {{ $config['inscriptions_ouvertes'] ? 'checked' : '' }}>
                <span class="text-sm text-gray-700">Inscriptions ouvertes au public</span>
            </label>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="btn-primary">Enregistrer la configuration</button>
        </div>
    </form>

</div>
@endsection
