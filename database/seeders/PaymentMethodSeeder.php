<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            [
                'code'                 => 'geniuspay',
                'name'                 => 'GeniusPay',
                'type'                 => 'gateway',
                'provider'             => 'geniuspay',
                'logo_url'             => null,
                'description'          => 'Wave, Orange Money, MTN MoMo, Carte bancaire',
                'is_active'            => false, // À activer manuellement depuis le super admin
                'auto_validate'        => true,
                'position'             => 1,
                'config'               => [],    // Les clés viennent du .env
                'supported_currencies' => ['XOF'],
                'supported_countries'  => ['CI', 'SN', 'ML', 'BF', 'BJ', 'TG', 'NE', 'GN', 'CM'],
            ],
            [
                'code'                 => 'bank_transfer',
                'name'                 => 'Transfert bancaire',
                'type'                 => 'manual',
                'provider'             => null,
                'logo_url'             => null,
                'description'          => 'Virement Wave / Orange Money avec envoi de reçu',
                'is_active'            => true,
                'auto_validate'        => false,
                'position'             => 2,
                'config'               => [],
                'supported_currencies' => ['XOF'],
                'supported_countries'  => ['CI', 'SN', 'ML', 'BF', 'BJ', 'TG', 'NE', 'GN', 'CM'],
            ],
        ];

        foreach ($methods as $data) {
            PaymentMethod::updateOrCreate(['code' => $data['code']], $data);
        }
    }
}
