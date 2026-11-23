<?php

namespace Tests\Feature\Views;

use Tests\TestCase;

/**
 * Garde-fou sur la syntaxe Blade.
 *
 * `@php(...)` en une ligne ne fonctionne que s'il n'existe AUCUN `@endphp`
 * plus loin dans le fichier : Blade apparie alors la forme courte avec le
 * `@endphp` d'un autre bloc et avale tout le HTML intermédiaire. Le symptôme
 * est un 500 « Undefined variable » à des centaines de lignes de là.
 *
 * Règle : dans un fichier qui contient déjà `@endphp`, on écrit toujours
 * `@php … @endphp`, jamais la forme courte.
 */
class BladeHygieneTest extends TestCase
{
    /** @return array<int, string> */
    private function vues(): array
    {
        return glob(resource_path('views/**/*.blade.php')) ?: [];
    }

    public function test_aucune_forme_courte_php_dans_un_fichier_avec_endphp(): void
    {
        $fautives = [];

        foreach ($this->vues() as $fichier) {
            $contenu = file_get_contents($fichier);

            if (! str_contains($contenu, '@endphp')) {
                continue;
            }

            if (preg_match('/@php\s*\(/', $contenu)) {
                $fautives[] = str_replace(resource_path('views/').'', '', $fichier);
            }
        }

        $this->assertSame(
            [],
            $fautives,
            'Ces vues mélangent @php(...) et @endphp : Blade va mal apparier les blocs.'
        );
    }

    public function test_les_vues_se_compilent_toutes(): void
    {
        $compilateur = app('blade.compiler');
        $cassees = [];

        foreach ($this->vues() as $fichier) {
            try {
                $compilateur->compileString(file_get_contents($fichier));
            } catch (\Throwable $e) {
                $cassees[] = basename($fichier) . ' : ' . $e->getMessage();
            }
        }

        $this->assertSame([], $cassees);
    }
}
