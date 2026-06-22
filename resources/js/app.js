import './bootstrap';
import Alpine from 'alpinejs';
import caisseApp from './caisse';

// Expose le composant Caisse POS en global pour Alpine x-data
window.caisseApp = caisseApp;

// Composant Alpine pour le layout dashboard (sidebar, thème)
Alpine.data('dashboardLayout', () => ({
    sidebarOpen: false,
    themeMenu: false,
    theme: localStorage.getItem('maelya-theme') || 'system',
    get isDark() {
        return this.theme === 'dark' || (this.theme === 'system' && matchMedia('(prefers-color-scheme: dark)').matches);
    },
    setTheme(t) {
        this.theme = t;
        localStorage.setItem('maelya-theme', t);
        if (t === 'dark') document.documentElement.classList.add('dark');
        else if (t === 'light') document.documentElement.classList.remove('dark');
        else document.documentElement.classList.toggle('dark', matchMedia('(prefers-color-scheme: dark)').matches);
        this.themeMenu = false;
    }
}));

// Livewire gère Alpine.start() via @livewireScripts.
// La meta livewire:inject-alpine=false l'empêche d'injecter sa propre copie.
window.Alpine = Alpine;

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
