/**
 * Point d'entrée Alpine « autonome ».
 *
 * Historiquement, Alpine était fourni par Livewire (`@livewireScripts`), ce qui
 * imposait de télécharger ~237 Ko de JavaScript Livewire sur TOUTES les pages du
 * dashboard alors qu'un seul écran (la caisse) en a besoin.
 *
 * Ce fichier fournit Alpine (3.x, via npm) aux pages qui n'utilisent pas
 * Livewire. La page caisse, elle, continue de charger `@livewireScripts`
 * (qui embarque sa propre instance d'Alpine) — il ne faut jamais charger les
 * deux en même temps.
 *
 * ⚠️ Ce script doit être chargé APRÈS `@stack('scripts')` : les vues y
 * enregistrent leurs composants via `document.addEventListener('alpine:init')`,
 * et ces écouteurs doivent exister avant `Alpine.start()`.
 */
import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();
