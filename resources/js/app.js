import './bootstrap';
import Alpine from 'alpinejs';
import caisseApp from './caisse';

// Expose le composant Caisse POS en global pour Alpine x-data
window.caisseApp = caisseApp;

// Expose Alpine globalement.
// Sur les pages avec Livewire v4 : Livewire va overwrite window.Alpine avec son
// propre bundle et le démarrer via DOMContentLoaded (en setant __fromLivewire = true).
// Sur les pages sans Livewire (landing, auth) : on démarre Alpine manuellement.
window.Alpine = Alpine;

document.addEventListener('DOMContentLoaded', () => {
    if (!window.Alpine.__fromLivewire) {
        // Pas de Livewire v4 sur cette page → on démarre Alpine nous-mêmes
        Alpine.start();
    }
    // Sinon Livewire a pris la main et démarre son Alpine — on ne touche à rien
});
