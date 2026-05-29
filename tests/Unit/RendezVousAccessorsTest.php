<?php

namespace Tests\Unit;

use App\Models\RendezVous;
use Carbon\Carbon;
use Tests\TestCase;

class RendezVousAccessorsTest extends TestCase
{
    public function test_fin_le_ajoute_la_duree_au_debut(): void
    {
        $rdv = new RendezVous(['duree_minutes' => 45]);
        $rdv->debut_le = Carbon::parse('2026-06-01 10:00:00');

        $this->assertSame('2026-06-01 10:45:00', $rdv->fin_le->format('Y-m-d H:i:s'));
    }

    public function test_audit_label_contient_le_nom_et_la_date(): void
    {
        $rdv = new RendezVous(['client_nom' => 'Marie', 'duree_minutes' => 30]);
        $rdv->debut_le = Carbon::parse('2026-06-01 14:30:00');

        $label = $rdv->auditLabel();
        $this->assertStringContainsString('Marie', $label);
        $this->assertStringContainsString('01/06/2026', $label);
    }
}
