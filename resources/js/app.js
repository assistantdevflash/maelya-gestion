import './bootstrap';
import Alpine from 'alpinejs';
import caisseApp from './caisse';

// Expose le composant Caisse POS en global pour Alpine x-data
window.caisseApp = caisseApp;

// ═══════════════════════════════════════════════════════════════
//  RECHERCHE GLOBALE (Cmd+K)
// ═══════════════════════════════════════════════════════════════
Alpine.data('globalSearch', (searchRoute) => ({
    searchOpen: false,
    searchQuery: '',
    searchResults: null,
    searchLoading: false,
    searchIndex: -1,

    openSearch() {
        this.searchOpen = true;
        this.searchQuery = '';
        this.searchResults = null;
        this.searchIndex = -1;
        this.$nextTick(() => {
            const el = this.$refs.searchInput;
            if (el) { el.focus(); el.select(); }
        });
    },

    closeSearch() {
        this.searchOpen = false;
        this.searchQuery = '';
        this.searchResults = null;
        this.searchIndex = -1;
    },

    async doSearch() {
        const q = this.searchQuery.trim();
        if (q.length < 2) { this.searchResults = null; return; }
        this.searchLoading = true;
        this.searchIndex = -1;
        try {
            const res = await fetch(searchRoute + '?q=' + encodeURIComponent(q), {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }
            });
            if (res.ok) {
                this.searchResults = await res.json();
            } else {
                this.searchResults = null;
            }
        } catch (e) {
            this.searchResults = null;
        } finally {
            this.searchLoading = false;
        }
    },

    get searchFlatResults() {
        if (!this.searchResults || !this.searchResults.groupes) return [];
        const flat = [];
        this.searchResults.groupes.forEach(g => {
            g.resultats.forEach(r => { flat.push({ ...r, groupeTitre: g.titre, groupeCle: g.cle }); });
        });
        return flat;
    },

    searchNavigate(ev) {
        const flat = this.searchFlatResults;
        if (flat.length === 0) return;
        if (ev.key === 'ArrowDown') { ev.preventDefault(); this.searchIndex = Math.min(this.searchIndex + 1, flat.length - 1); }
        else if (ev.key === 'ArrowUp') { ev.preventDefault(); this.searchIndex = Math.max(this.searchIndex - 1, 0); }
        else if (ev.key === 'Enter' && this.searchIndex >= 0) {
            ev.preventDefault();
            const item = flat[this.searchIndex];
            if (item && item.url) window.location = item.url;
        }
    },

    init() {
        const self = this;
        // Raccourci clavier Cmd+K / Ctrl+K
        document.addEventListener('keydown', function (ev) {
            if ((ev.metaKey || ev.ctrlKey) && ev.key === 'k') {
                ev.preventDefault();
                self.openSearch();
            }
        });
        // Ouverture depuis les boutons de la topbar (via event custom)
        window.addEventListener('global-search:open', () => self.openSearch());
    }
}));

// Fonction utilitaire pour ouvrir la recherche depuis n'importe où
window.openGlobalSearch = function () {
    window.dispatchEvent(new CustomEvent('global-search:open'));
};

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

// ═══════════════════════════════════════════════════════════════
//  GLOBAL FORM LOADING — disable bouton + spinner au submit
// ═══════════════════════════════════════════════════════════════
document.addEventListener('submit', (e) => {
    const form = e.target;
    if (!(form instanceof HTMLFormElement)) return;

    // Ignorer si déjà en cours de soumission (double-click protection)
    if (form.classList.contains('is-submitting')) {
        e.preventDefault();
        return;
    }

    form.classList.add('is-submitting');

    // Trouver tous les boutons submit du formulaire
    const buttons = form.querySelectorAll('button[type="submit"], button:not([type])');
    buttons.forEach(btn => {
        btn.disabled = true;
        // Sauvegarder le contenu original
        btn.dataset.originalHtml = btn.innerHTML;
        // Injecter le spinner en préservant le texte
        const spinnerHtml = '<span class="spinner spinner-sm" aria-hidden="true"></span>';
        const textContent = btn.textContent.trim();
        if (textContent) {
            btn.innerHTML = spinnerHtml + ' ' + textContent;
        } else {
            // Bouton icône seul (ex: logout)
            btn.innerHTML = spinnerHtml;
        }
    });

    // Afficher la barre de progression
    showProgressBar();
});

// ═══════════════════════════════════════════════════════════════
//  BARRE DE PROGRESSION — navigation & soumission
// ═══════════════════════════════════════════════════════════════
let progressBar = null;
let progressTimer = null;

function showProgressBar() {
    if (progressBar) return;
    progressBar = document.createElement('div');
    progressBar.className = 'page-progress-bar';
    document.body.appendChild(progressBar);
    // Animer la barre
    let width = 15;
    progressTimer = setInterval(() => {
        if (width < 90) {
            width += Math.random() * 10;
            progressBar.style.width = Math.min(width, 90) + '%';
        }
    }, 300);
}

function hideProgressBar() {
    if (progressTimer) clearInterval(progressTimer);
    if (progressBar) {
        progressBar.style.width = '100%';
        setTimeout(() => {
            if (progressBar) {
                progressBar.remove();
                progressBar = null;
            }
        }, 200);
    }
}

// Afficher la barre au clic sur un lien interne
document.addEventListener('click', (e) => {
    const link = e.target.closest('a[href]');
    if (!link) return;
    const href = link.getAttribute('href');
    if (!href || href.startsWith('#') || href.startsWith('javascript:') || link.target === '_blank' || link.hasAttribute('download')) return;
    // Lien interne seulement
    try {
        const url = new URL(href, window.location.origin);
        if (url.origin === window.location.origin) {
            showProgressBar();
        }
    } catch(e) { /* URL invalide, ignorer */ }
});

// Restaurer les boutons en cas de navigation arrière (bfcache)
window.addEventListener('pageshow', (e) => {
    hideProgressBar();
    if (e.persisted) {
        document.querySelectorAll('form.is-submitting').forEach(form => {
            form.classList.remove('is-submitting');
            form.querySelectorAll('button[disabled]').forEach(btn => {
                if (btn.dataset.originalHtml) {
                    btn.innerHTML = btn.dataset.originalHtml;
                    delete btn.dataset.originalHtml;
                }
                btn.disabled = false;
            });
        });
    }
});
