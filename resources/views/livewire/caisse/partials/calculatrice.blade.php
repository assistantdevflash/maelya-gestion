    @if($simple)
    {{-- ═══ CALCULATRICE FLOTTANTE (caisse simplifiée) ═══ --}}
    <div x-data="{
            calOuverte: false,
            expr: '',
            resultat: '',

            /** Traduit les symboles affichés (×, ÷, −) en opérateurs JS. */
            normalise(v) {
                return String(v)
                    .replace(/[\u2212\u2013\u2014]/g, '-')
                    .replace(/\u00d7/g, '*')
                    .replace(/\u00f7/g, '/')
                    .replace(/,/g, '.')
                    .replace(/\s+/g, '');
            },

            /** Aperçu calculé en direct : '' tant qu'il n'y a pas d'opération. */
            get valeur() {
                const p = this.normalise(this.expr);
                if (p === '' || !/[+\-*/]/.test(p) || !/^[0-9+\-*/().]+$/.test(p)) return '';
                try {
                    const r = Function('return (' + p + ')')();
                    return (typeof r === 'number' && isFinite(r))
                        ? String(Math.round(r * 100) / 100)
                        : '';
                } catch (e) {
                    return '';
                }
            },

            touche(t) {
                if (t === 'C') { this.expr = ''; this.resultat = ''; return; }
                if (t === '⌫') { this.expr = this.expr.slice(0, -1); this.resultat = ''; return; }
                if (t === '=') {
                    const v = this.valeur;
                    if (v !== '') { this.resultat = v; this.expr = v; }
                    return;
                }
                // Un seul séparateur décimal par nombre
                if (t === '.') {
                    const morceau = this.expr.split(/[+\-×÷(]/).pop();
                    if (morceau.includes('.')) return;
                    this.resultat = '';
                    this.expr += (morceau === '' ? '0.' : '.');
                    return;
                }
                this.resultat = '';
                this.expr += t;
            },

            /** Le focus est-il dans un champ de saisie ? (on ne détourne pas la frappe) */
            champSaisie(el) {
                if (!el || !el.tagName) return false;
                const tag = el.tagName;
                return tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || el.isContentEditable === true;
            },

            /** Touches physiques du clavier, actives uniquement quand la calculatrice est ouverte. */
            toucheClavier(e) {
                if (!this.calOuverte) return;
                if (e.metaKey || e.ctrlKey || e.altKey) return;
                // Focus dans un champ (recherche, client, remise…) : on laisse la frappe au champ
                if (this.champSaisie(e.target)) return;

                const k = e.key;
                let t = null;

                if (/^[0-9]$/.test(k)) t = k;
                else if (k === '.' || k === ',') t = '.';
                else if (k === '+') t = '+';
                else if (k === '-' || k === '−') t = '−';
                else if (k === '*' || k === 'x' || k === 'X') t = '×';
                else if (k === '/' || k === ':') t = '÷';
                else if (k === '=' || k === 'Enter') t = '=';
                else if (k === 'Backspace') t = '⌫';
                else if (k === 'Delete' || k === 'c' || k === 'C') t = 'C';
                else if (k === 'Escape') { e.preventDefault(); this.calOuverte = false; return; }

                if (t === null) return;
                e.preventDefault();
                this.touche(t);
            }
         }"
         x-on:keydown.window="toucheClavier($event)"
         x-init="$watch('calOuverte', v => { if (v) { $nextTick(() => $refs.panneau && $refs.panneau.focus()); } else { const i = document.querySelector('[data-recherche]'); if (i) i.focus(); } })"
         class="fixed bottom-24 lg:bottom-5 right-4 lg:right-5 z-40 flex flex-col items-end gap-2 print:hidden">

        {{-- Panneau calculateur --}}
        <div x-show="calOuverte" x-cloak x-transition.origin.bottom.right
             x-ref="panneau" tabindex="-1"
             class="w-60 rounded-2xl bg-white dark:bg-slate-800 shadow-2xl border border-gray-200 dark:border-slate-600 p-3 focus:outline-none"
             :class="calOuverte ? 'ring-1 ring-primary-200 dark:ring-primary-800' : ''">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Calculatrice</span>
                <button type="button" @click="calOuverte = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Écran --}}
            <div class="mb-2 px-3 py-2 rounded-xl bg-gray-50 dark:bg-slate-900/60 border border-gray-100 dark:border-slate-700 text-right overflow-hidden">
                <p class="text-[11px] text-gray-400 h-4 truncate" x-text="expr || '0'"></p>
                <p class="text-xl font-bold text-gray-900 dark:text-white truncate" x-text="resultat || valeur"></p>
            </div>

            {{-- Touches --}}
            <div class="grid grid-cols-4 gap-1.5 text-sm font-semibold" @click="$refs.panneau.focus()">
                <button type="button" @click="touche('C')" class="py-2 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition">C</button>
                <button type="button" @click="touche('⌫')" class="py-2 rounded-lg bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 transition">⌫</button>
                <button type="button" @click="touche('÷')" class="py-2 rounded-lg bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-200 hover:bg-gray-200 transition">÷</button>
                <button type="button" @click="touche('×')" class="py-2 rounded-lg bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-200 hover:bg-gray-200 transition">×</button>

                <button type="button" @click="touche('7')" class="py-2 rounded-lg bg-white dark:bg-slate-700/50 border border-gray-200 dark:border-slate-600 hover:bg-gray-50 transition">7</button>
                <button type="button" @click="touche('8')" class="py-2 rounded-lg bg-white dark:bg-slate-700/50 border border-gray-200 dark:border-slate-600 hover:bg-gray-50 transition">8</button>
                <button type="button" @click="touche('9')" class="py-2 rounded-lg bg-white dark:bg-slate-700/50 border border-gray-200 dark:border-slate-600 hover:bg-gray-50 transition">9</button>
                <button type="button" @click="touche('−')" class="py-2 rounded-lg bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-200 hover:bg-gray-200 transition">−</button>

                <button type="button" @click="touche('4')" class="py-2 rounded-lg bg-white dark:bg-slate-700/50 border border-gray-200 dark:border-slate-600 hover:bg-gray-50 transition">4</button>
                <button type="button" @click="touche('5')" class="py-2 rounded-lg bg-white dark:bg-slate-700/50 border border-gray-200 dark:border-slate-600 hover:bg-gray-50 transition">5</button>
                <button type="button" @click="touche('6')" class="py-2 rounded-lg bg-white dark:bg-slate-700/50 border border-gray-200 dark:border-slate-600 hover:bg-gray-50 transition">6</button>
                <button type="button" @click="touche('+')" class="py-2 rounded-lg bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-200 hover:bg-gray-200 transition">+</button>

                <button type="button" @click="touche('1')" class="py-2 rounded-lg bg-white dark:bg-slate-700/50 border border-gray-200 dark:border-slate-600 hover:bg-gray-50 transition">1</button>
                <button type="button" @click="touche('2')" class="py-2 rounded-lg bg-white dark:bg-slate-700/50 border border-gray-200 dark:border-slate-600 hover:bg-gray-50 transition">2</button>
                <button type="button" @click="touche('3')" class="py-2 rounded-lg bg-white dark:bg-slate-700/50 border border-gray-200 dark:border-slate-600 hover:bg-gray-50 transition">3</button>
                <button type="button" @click="touche('=')"
                        class="py-2 rounded-lg text-white font-bold transition hover:shadow-lg"
                        style="background: linear-gradient(135deg, #9333ea, #ec4899);">=</button>

                <button type="button" @click="touche('0')" class="col-span-2 py-2 rounded-lg bg-white dark:bg-slate-700/50 border border-gray-200 dark:border-slate-600 hover:bg-gray-50 transition">0</button>
                <button type="button" @click="touche('.')" class="py-2 rounded-lg bg-white dark:bg-slate-700/50 border border-gray-200 dark:border-slate-600 hover:bg-gray-50 transition">.</button>
                <button type="button" @click="touche('C')" class="py-2 rounded-lg bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-gray-400 hover:bg-gray-200 transition text-xs">RAZ</button>
            </div>

            {{-- Aide clavier (desktop) --}}
            <p class="hidden lg:block text-[10px] leading-tight text-gray-400 dark:text-gray-500 text-center mt-2">
                Chiffres, + − × ÷, Entrée pour calculer, Échap pour fermer
            </p>
        </div>

        {{-- Bouton flottant --}}
        <button type="button" @click="calOuverte = !calOuverte"
                class="w-12 h-12 rounded-full text-white shadow-lg hover:shadow-xl active:scale-95 transition-all flex items-center justify-center"
                style="background: linear-gradient(135deg, #9333ea, #ec4899);"
                :class="calOuverte ? 'ring-2 ring-white ring-offset-2 ring-offset-gray-100' : ''"
                aria-label="Calculatrice" title="Calculatrice">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m-6 4h6m-6 4h3M7 3h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z"/>
            </svg>
        </button>
    </div>
    @endif