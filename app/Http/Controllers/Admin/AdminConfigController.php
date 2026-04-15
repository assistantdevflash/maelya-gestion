<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class AdminConfigController extends Controller
{
    public function edit()
    {
        return view('admin.config', [
            'config' => [
                'cinetpay_api_key' => config('cinetpay.api_key', ''),
                'cinetpay_site_id' => config('cinetpay.site_id', ''),
                'cinetpay_mode' => config('cinetpay.mode', 'sandbox'),
                'inscriptions_ouvertes' => config('app.inscriptions_ouvertes', true),
            ]
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'cinetpay_api_key' => ['nullable', 'string'],
            'cinetpay_site_id' => ['nullable', 'string'],
            'cinetpay_mode' => ['required', 'in:sandbox,production'],
        ]);

        // Mettre à jour le fichier .env
        $this->updateEnv([
            'CINETPAY_API_KEY' => $request->cinetpay_api_key ?? '',
            'CINETPAY_SITE_ID' => $request->cinetpay_site_id ?? '',
            'CINETPAY_MODE' => $request->cinetpay_mode,
        ]);

        Artisan::call('config:clear');

        return back()->with('success', 'Configuration mise à jour.');
    }

    private function updateEnv(array $data): void
    {
        $envPath = base_path('.env');
        $envContent = file_get_contents($envPath);

        foreach ($data as $key => $value) {
            $pattern = "/^{$key}=.*/m";
            $replacement = "{$key}={$value}";

            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $replacement, $envContent);
            } else {
                $envContent .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envPath, $envContent);
    }
}
