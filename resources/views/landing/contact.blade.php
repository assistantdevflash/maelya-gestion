<x-landing-layout :title="$title ?? null" :meta-description="$metaDescription ?? null">

{{-- ═══ HERO HEADER ═══ --}}
<section class="relative pt-32 pb-20 overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-gray-950 via-primary-950 to-gray-900"></div>
    <div class="absolute inset-0 opacity-[0.03]" style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 40px 40px;"></div>
    <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-secondary-500/20 rounded-full blur-[80px]"></div>

    <div class="relative max-w-4xl mx-auto px-4 sm:px-6 text-center animate-fade-in-up">
        <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm border border-white/15 text-white/80 text-xs font-bold px-4 py-1.5 rounded-full mb-6 uppercase tracking-wider">Contactez-nous</div>
        <h1 class="text-4xl sm:text-5xl font-extrabold text-white tracking-tight">Une question ? <span class="shimmer-text">Écrivez-nous !</span></h1>
        <p class="text-lg text-white/50 mt-5">Nous répondons sous 24 heures.</p>
    </div>

    <div class="hero-fade"></div>
</section>

{{-- ═══ CONTENU ═══ --}}
<section class="py-20 bg-primary-50/30 dark:bg-gray-900">
    <div class="max-w-5xl mx-auto px-4 sm:px-6">
        <div class="grid lg:grid-cols-5 gap-10 lg:gap-14">

            {{-- Infos contact --}}
            <div class="lg:col-span-2 space-y-5">
                @foreach([
                    ['from-primary-500 to-violet-600', 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', 'Email', 'contact@maelyagestion.com'],
                    ['from-secondary-500 to-pink-600', 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z', 'WhatsApp', '07 09 87 40 67'],
                    ['from-emerald-500 to-teal-600', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'Horaires', 'Lun–Ven, 8h–18h (GMT)'],
                    ['from-amber-500 to-orange-600', 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z', 'Localisation', 'Abidjan, Côte d\'Ivoire'],
                ] as [$gradient, $icon, $title, $desc])
                <div class="group flex items-start gap-4 p-5 rounded-2xl bg-primary-50/40 dark:bg-gray-800/40 border border-primary-100/60 dark:border-gray-700 hover:bg-white dark:hover:bg-gray-800 hover:shadow-lg hover:shadow-primary-100/50 hover:-translate-y-0.5 transition-all duration-300">
                    <div class="w-11 h-11 bg-gradient-to-br {{ $gradient }} rounded-xl flex items-center justify-center flex-shrink-0 shadow-lg shadow-gray-200/50 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $icon }}"/></svg>
                    </div>
                    <div>
                        <p class="font-bold text-gray-900 dark:text-white text-sm">{{ $title }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ $desc }}</p>
                    </div>
                </div>
                @endforeach

                <div class="pt-3">
                    <p class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-3">Suivez-nous</p>
                    <div class="flex gap-3">
                        @foreach([
                            ['Facebook', '#'],
                            ['Instagram', '#'],
                            ['TikTok', '#'],
                        ] as [$social, $url])
                        <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $social }}"
                           class="w-10 h-10 bg-primary-50 rounded-xl flex items-center justify-center text-primary-400 hover:bg-primary-100 hover:text-primary-600 transition-colors duration-300">
                            <span class="text-xs font-bold">{{ strtoupper(substr($social, 0, 2)) }}</span>
                        </a>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Formulaire --}}
            <div class="lg:col-span-3">
                <div class="bg-white/80 dark:bg-gray-800 rounded-3xl p-7 sm:p-8 border border-primary-100/60 dark:border-gray-700 shadow-sm">
                    @if(session('success'))
                        <div class="flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-2xl px-5 py-4 mb-6">
                            <div class="w-8 h-8 bg-emerald-100 rounded-xl flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            Votre message a été envoyé. Nous reviendrons vers vous rapidement !
                        </div>
                    @endif

                    <form method="POST" action="{{ route('contact.send') }}" class="space-y-5">
                        @csrf
                        <input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off">

                        <div class="grid sm:grid-cols-2 gap-5">
                            <div>
                                <label for="nom" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Votre nom <span class="text-red-400">*</span></label>
                                <input type="text" id="nom" name="nom" required maxlength="100"
                                       value="{{ old('nom') }}"
                                       class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50/50 text-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-200 @error('nom') border-red-300 bg-red-50/30 @enderror"
                                       placeholder="Aicha Koné">
                                @error('nom') <p class="text-xs text-red-500 mt-1.5">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Email <span class="text-red-400">*</span></label>
                                <input type="email" id="email" name="email" required maxlength="150"
                                       value="{{ old('email') }}"
                                       class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50/50 text-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-200 @error('email') border-red-300 bg-red-50/30 @enderror"
                                       placeholder="aicha@exemple.com">
                                @error('email') <p class="text-xs text-red-500 mt-1.5">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label for="telephone" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Téléphone</label>
                            <input type="tel" id="telephone" name="telephone" maxlength="20"
                                   value="{{ old('telephone') }}"
                                   class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50/50 text-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-200"
                                   placeholder="+225 07 00 00 00 00">
                        </div>

                        <div>
                            <label for="message" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Message <span class="text-red-400">*</span></label>
                            <textarea id="message" name="message" required minlength="10" maxlength="2000" rows="5"
                                      class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50/50 text-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-200 resize-none @error('message') border-red-300 bg-red-50/30 @enderror"
                                      placeholder="Décrivez votre question ou besoin...">{{ old('message') }}</textarea>
                            @error('message') <p class="text-xs text-red-500 mt-1.5">{{ $message }}</p> @enderror
                        </div>

                        <button type="submit" class="group w-full inline-flex items-center justify-center gap-2.5 px-6 py-3.5 rounded-2xl text-sm font-bold text-white shadow-xl shadow-primary-200/50 transition-all duration-300 hover:shadow-2xl hover:-translate-y-0.5"
                                style="background: linear-gradient(135deg, #9333ea 0%, #ec4899 100%);">
                            Envoyer le message
                            <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

</x-landing-layout>
