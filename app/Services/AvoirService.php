<?php

namespace App\Services;

use App\Models\Avoir;
use App\Models\CodeReduction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AvoirService
{
    /**
     * Crée un avoir pour une vente et génère automatiquement un code de réduction
     * de type montant_fixe utilisable par le client.
     *
     * @param array $data ['institut_id','vente_id','client_id','user_id','montant','motif']
     */
    public function creer(array $data): Avoir
    {
        return DB::transaction(function () use ($data) {
            $institutId = $data['institut_id'];
            $numero = $this->generateNumero($institutId);

            $code = CodeReduction::create([
                'institut_id'        => $institutId,
                'client_id'          => $data['client_id'] ?? null,
                'code'               => 'AVOIR-' . strtoupper(Str::random(8)),
                'description'        => 'Avoir ' . $numero . ' - ' . ($data['motif'] ?? 'Retour'),
                'type'               => 'montant_fixe',
                'valeur'             => (int) $data['montant'],
                'date_debut'         => Carbon::today(),
                'date_fin'           => Carbon::today()->addYear(),
                'limite_utilisation' => 1,
                'nb_utilisations'    => 0,
                'actif'              => true,
            ]);

            return Avoir::create([
                'institut_id'       => $institutId,
                'vente_id'          => $data['vente_id'] ?? null,
                'client_id'         => $data['client_id'] ?? null,
                'user_id'           => $data['user_id'],
                'code_reduction_id' => $code->id,
                'numero'            => $numero,
                'montant'           => (int) $data['montant'],
                'motif'             => $data['motif'] ?? null,
                'statut'            => 'emis',
            ]);
        });
    }

    public function generateNumero(string $institutId, ?Carbon $date = null): string
    {
        $annee = ($date ?? Carbon::now())->format('Y');
        $prefix = "AV-{$annee}-";

        $dernier = Avoir::withoutGlobalScopes()
            ->where('institut_id', $institutId)
            ->where('numero', 'like', $prefix . '%')
            ->lockForUpdate()
            ->max('numero');

        $seq = $dernier ? ((int) substr($dernier, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
