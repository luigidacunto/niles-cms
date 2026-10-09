@php
    // Menu dinamico dal DB: pagine pubblicate con "in_menu" attivo, di primo livello, coi rispettivi
    // figli anch'essi in_menu (Page::menuTree()). Vuoto finché nessuna pagina ha l'interruttore "Mostra nel menu" attivo.
    $mainNav ??= \App\Models\Page::menuTree();

    // Pagine di sistema Privacy Policy / Cookie Policy, linkate dal footer e dal banner cookie
    // (FirstInstallSeeder le semina pubblicate). Lookup lazy, stesso pattern di $mainNav.
    $privacyPage ??= \App\Models\Page::where('slug', 'privacy')->first();
    $cookiePage ??= \App\Models\Page::where('slug', 'cookie-policy')->first();

    // Dati/loghi del comitato (nav + footer).
    $committeeInfo ??= \App\Models\CommitteeInfo::current();

    // Area soci: attiva da Dati comitato; il menu "Area Soci" compare solo a un socio loggato. Le pagine future
    // (materiali, ecc.) si aggiungono qui come ['titolo', url].
    $areaSociAttiva = (bool) $committeeInfo->area_soci_attiva;
    $areaSociNav = ($areaSociAttiva && auth('member')->check()) ? [
        ['Comunicazioni', route('soci.comunicazioni')],
    ] : [];
@endphp
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @if (! app()->isProduction() || View::hasSection('noindex'))
        <meta name="robots" content="noindex, nofollow">
    @endif
    <title>@yield('title', config('app.public_name'))</title>
    <meta name="description" content="@yield('meta_description', config('app.public_name'))">
    @if ($committeeInfo->favicon_url)
        <link rel="icon" href="{{ $committeeInfo->favicon_url }}" type="image/png">
    @else
        <link rel="icon" href="{{ asset('favicons/favicon.ico') }}" sizes="any">
        <link rel="icon" href="{{ asset('favicons/favicon.svg') }}" type="image/svg+xml">
        <link rel="shortcut icon" href="{{ asset('favicons/favicon.ico') }}">
    @endif

    {{-- Open Graph. og:image ha un fallback sul
         logo orizzontale del comitato quando la pagina/post non ha una copertina propria (mai l'SVG
         placeholder generico: i crawler social lo gestiscono male). --}}
    <meta property="og:site_name" content="{{ config('app.public_name') }}">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:title" content="@yield('title', config('app.public_name'))">
    <meta property="og:description" content="@yield('meta_description', config('app.public_name'))">
    <meta property="og:url" content="{{ url()->current() }}">
    @php($ogImage = $__env->yieldContent('og_image', $committeeInfo->logo_orizzontale_url ?: ''))
    @if ($ogImage)
        {{-- URL assoluto (non relativo): i crawler Open Graph/social spesso non risolvono un path
             relativo come "/storage/...". --}}
        <meta property="og:image" content="{{ url($ogImage) }}">
    @endif

    {{-- JSON-LD Organization, sempre presente (dati da CommitteeInfo, vuoto = niente proprietà). Le pagine possono aggiungere altro JSON-LD (es.
         NewsArticle sui post) con @push('jsonld')/@stack qui sotto. --}}
    <script type="application/ld+json">
        {!! json_encode(array_filter([
            '@@context' => 'https://schema.org',
            '@type' => 'NGO',
            'name' => $committeeInfo->denominazione ?: config('app.public_name'),
            'url' => url('/'),
            'logo' => $committeeInfo->logo_orizzontale_url ? url($committeeInfo->logo_orizzontale_url) : null,
            'telephone' => $committeeInfo->telefono,
            'email' => $committeeInfo->email,
            'address' => $committeeInfo->indirizzo ? [
                '@type' => 'PostalAddress',
                'streetAddress' => $committeeInfo->indirizzo,
            ] : null,
            // Profili social compilati in "Dati del comitato" (array vuoto = proprietà omessa).
            'sameAs' => array_values(array_filter([
                $committeeInfo->facebook_url, $committeeInfo->instagram_url, $committeeInfo->youtube_url, $committeeInfo->x_url,
            ])),
        ]), JSON_UNESCAPED_SLASHES) !!}
    </script>
    @stack('jsonld')
    {{-- Icone <i class="fas ...">: stesso vendor locale già usato dall'admin, qui serve solo per le
         icone-documento riconoscibili negli Allegati (Document::faIcon()). Servono ENTRAMBI i file:
         fontawesome.min.css ha le regole base + il mapping icona→glifo, solid.min.css ha solo il
         @font-face del font "solid" (peso 900) — solid.min.css da solo non basta, l'icona non compare. --}}
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/fontawesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/solid.min.css') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- Pagine con un token personale nell'URL (@section('noanalytics')): niente statistiche, il token finirebbe nei report. --}}
    @unless (View::hasSection('noanalytics'))
        @include('partials.analytics')
    @endunless
</head>
<body class="min-h-screen flex flex-col bg-[#efefef] text-[#4b4b51]" x-data="{ mobileNavOpen: false }">

    @if (config('app.demo'))
        <div class="bg-yellow-400 text-black text-center text-sm font-semibold px-4 py-2 leading-snug">
            Versione dimostrativa di NILES — contenuti di esempio, nulla viene salvato né inviato.
        </div>
    @endif

    @unless (app()->isProduction())
        {{-- Ambiente non di produzione (staging / locale): fascia sempre visibile così un visitatore
             capisce che non è il sito ufficiale. Sparisce solo con APP_ENV=production. --}}
        <div class="bg-yellow-400 text-black text-center text-sm font-semibold px-4 py-2 leading-snug">
            Ambiente di prova — contenuti provvisori, non è il sito ufficiale del Comitato.
        </div>
    @endunless

    <header>
        {{-- Top strip: solo IFRC, ripreso dalla struttura attuale di cri.it --}}
        {{-- relative z-30: il menu a tendina dell'utente socio deve stare sopra la nav (z-20) sotto. --}}
        <div class="bg-[#999999] relative z-30">
            <div class="max-w-6xl mx-auto px-4 py-1.5 flex items-center justify-between">
                @if ($committeeInfo->logo_ifrc_url)
                    <a href="https://www.ifrc.org" target="_blank" rel="noopener" class="inline-flex items-center">
                        <img src="{{ $committeeInfo->logo_ifrc_url }}" alt="I.F.R.C. Member" class="h-5 w-auto">
                    </a>
                @else
                    <a href="https://www.ifrc.org" target="_blank" rel="noopener" class="inline-flex items-center rounded-full bg-[#cc0000] hover:bg-[#a30000] text-white text-xs font-semibold px-3 py-1 shadow-sm transition-colors">ifrc.org</a>
                @endif

                {{-- Area soci: icona utente → login (ospite) oppure menu "I miei dati / Esci" (socio autenticato). --}}
                @if ($areaSociAttiva)
                @auth('member')
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
                        <button type="button" @click="open = !open" :aria-expanded="open" aria-label="Menu utente"
                                class="inline-flex items-center gap-1.5 rounded-full bg-[#cc0000] hover:bg-[#a30000] text-white text-xs font-semibold px-3 py-1 shadow-sm transition-colors">
                            <i class="fas fa-user-circle text-sm" aria-hidden="true"></i>
                            <span class="max-w-[8rem] truncate">{{ auth('member')->user()->nome }}</span>
                            <i class="fas fa-chevron-down text-[9px]" aria-hidden="true"></i>
                        </button>
                        <div x-show="open" x-cloak class="absolute right-0 mt-2 w-44 bg-white border border-gray-200 rounded shadow-lg text-sm">
                            <a href="{{ route('soci.profilo') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-50 hover:text-[#cc0000]">I miei dati</a>
                            <form method="POST" action="{{ route('soci.logout') }}">
                                @csrf
                                <button type="submit" class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-50 hover:text-[#cc0000]">Esci</button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('soci.login') }}" class="inline-flex items-center gap-1.5 rounded-full bg-[#cc0000] hover:bg-[#a30000] text-white text-xs font-semibold px-3 py-1 shadow-sm transition-colors">
                        <i class="fas fa-user-circle text-sm" aria-hidden="true"></i>
                        Area soci
                    </a>
                @endauth
                @endif
            </div>
        </div>

        {{-- Nav: sfondo bianco, logo a sinistra, menu a destra (struttura cri.it) --}}
        <nav class="bg-white border-b border-gray-200 relative z-20">
            <div class="max-w-6xl mx-auto px-4 flex items-center justify-between py-3">
                {{-- Logo dal pannello (Dati del comitato), non più un file cablato nel repo. Solo l'orizzontale qui.
                     Logo come background CSS (non un <img> semplice): ingrandito e centrato nel box così
                     il margine trasparente del file finisce fuori dai bordi visibili senza ritagliare il
                     PNG — stesso trucco già usato prima di questa sezione admin, ripristinato perché un
                     <img> a dimensione naturale rendeva il logo minuscolo. background-position-y sopra il
                     50% perché il contenuto visibile nel file di riferimento (700×219, vedi il testo di
                     aiuto nel form admin) non è centrato verticalmente. Senza nessun logo caricato
                     (installazione da zero), fallback sul nome del sito a testo. --}}
                <a href="{{ url('/') }}" class="flex-shrink-0">
                    @if ($committeeInfo->logo_orizzontale_url)
                        <div role="img" aria-label="{{ config('app.public_name') }}" class="h-14 w-[300px]"
                             style="background-image: url('{{ $committeeInfo->logo_orizzontale_url }}');
                                    background-size: auto 112px; background-position: center 60%; background-repeat: no-repeat;">
                        </div>
                    @else
                        <span class="text-lg font-bold text-gray-900">{{ config('app.public_name') }}</span>
                    @endif
                </a>

                <button type="button" class="lg:hidden text-gray-700 font-medium flex items-center gap-2 py-2"
                        @click="mobileNavOpen = !mobileNavOpen">
                    <span>Menu</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>

                <ul class="hidden lg:flex items-center text-sm font-semibold uppercase tracking-wide text-gray-700">
                    @foreach ($mainNav as $item)
                        <li class="group relative">
                            @if ($item->children->isNotEmpty())
                                {{-- Voce con figli: solo trigger del dropdown, non punta a una pagina propria --}}
                                <span class="flex items-center gap-1 px-2 py-3 cursor-default">
                                    {{ $item->title }}
                                    <svg class="w-3 h-3 text-gray-400 group-hover:text-[#cc0000]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </span>
                            @else
                                <a href="{{ route('pages.show', $item) }}"
                                   class="flex items-center gap-1 px-2 py-3 hover:text-[#cc0000] transition-colors">
                                    {{ $item->title }}
                                </a>
                            @endif
                            @if ($item->children->isNotEmpty())
                                <ol class="hidden group-hover:block absolute left-0 top-full min-w-[220px] bg-white shadow-lg border-t-2 border-[#cc0000] py-1 normal-case font-normal tracking-normal">
                                    @foreach ($item->children as $child)
                                        <li>
                                            <a href="{{ route('pages.show', $child) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-[#cc0000]">
                                                {{ $child->title }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ol>
                            @endif
                        </li>
                    @endforeach
                    @if ($areaSociNav)
                        <li class="group relative">
                            <span class="flex items-center gap-1 px-2 py-3 cursor-default text-[#cc0000]">
                                Area Soci
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </span>
                            <ol class="hidden group-hover:block absolute left-0 top-full min-w-[220px] bg-white shadow-lg border-t-2 border-[#cc0000] py-1 normal-case font-normal tracking-normal">
                                @foreach ($areaSociNav as [$titolo, $url])
                                    <li><a href="{{ $url }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-[#cc0000]">{{ $titolo }}</a></li>
                                @endforeach
                            </ol>
                        </li>
                    @endif
                </ul>
            </div>

            {{-- Mobile: accordion --}}
            <ul x-show="mobileNavOpen" x-cloak class="lg:hidden px-4 pb-3 text-sm font-semibold text-gray-700 border-t border-gray-100">
                @foreach ($mainNav as $item)
                    <li class="border-b border-gray-100" x-data="{ childOpen: false }">
                        <div class="flex items-center justify-between">
                            @if ($item->children->isNotEmpty())
                                <span class="flex-1 py-3">{{ $item->title }}</span>
                            @else
                                <a href="{{ route('pages.show', $item) }}" class="flex-1 py-3">{{ $item->title }}</a>
                            @endif
                            @if ($item->children->isNotEmpty())
                                <button type="button" class="p-3" @click="childOpen = !childOpen">
                                    <svg class="w-4 h-4 text-gray-400" :class="childOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                            @endif
                        </div>
                        @if ($item->children->isNotEmpty())
                            <ol x-show="childOpen" x-cloak class="pb-2 pl-4 font-normal">
                                @foreach ($item->children as $child)
                                    <li><a href="{{ route('pages.show', $child) }}" class="block py-1.5 text-gray-600">{{ $child->title }}</a></li>
                                @endforeach
                            </ol>
                        @endif
                    </li>
                @endforeach
                @if ($areaSociNav)
                    <li class="border-b border-gray-100" x-data="{ childOpen: false }">
                        <div class="flex items-center justify-between">
                            <span class="flex-1 py-3 text-[#cc0000]">Area Soci</span>
                            <button type="button" class="p-3" @click="childOpen = !childOpen">
                                <svg class="w-4 h-4 text-gray-400" :class="childOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                        </div>
                        <ol x-show="childOpen" x-cloak class="pb-2 pl-4 font-normal">
                            @foreach ($areaSociNav as [$titolo, $url])
                                <li><a href="{{ $url }}" class="block py-1.5 text-gray-600">{{ $titolo }}</a></li>
                            @endforeach
                        </ol>
                    </li>
                @endif
            </ul>
        </nav>
    </header>

    <main class="flex-1 bg-white">
        @yield('content')
    </main>

    {{-- Stile ripreso da cri.it (colori/font/link misurati dal vivo il 2026-08-18): sfondo grigio scuro unico invece delle 3 fasce
         bianco/rosso/nero precedenti, link maiuscoletto bianchi su due colonne, loghi in riquadro bianco
         per restare leggibili sullo sfondo scuro, icone social al posto del testo, copyright centrato
         sotto una riga divisoria. --}}
    <footer class="bg-[#757575] text-white font-[Roboto,Helvetica,Arial,sans-serif]">
        <div class="max-w-6xl mx-auto px-4 py-10">
            {{-- Ordine nel markup = ordine di impilamento su mobile: Contatti prima (l'informazione più
                 cercata), poi i link interni del comitato, poi quelli verso cri.it in fondo. --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-12 gap-y-8">
                <div class="space-y-3 text-sm">
                    <p class="font-semibold uppercase tracking-wide mb-1">Contatti</p>
                    @if ($committeeInfo->indirizzo)
                        <p class="text-white/90">{{ $committeeInfo->indirizzo }}</p>
                    @endif
                    @if ($committeeInfo->telefono)
                        <a href="tel:{{ $committeeInfo->telefono }}" class="block text-white/90 hover:text-white">{{ $committeeInfo->telefono }}</a>
                    @endif
                    @if ($committeeInfo->email)
                        @php($obfuscatedEmail = \App\Support\EmailObfuscator::protect(e($committeeInfo->email)))
                        <a href="mailto:{!! $obfuscatedEmail !!}" class="block text-white/90 hover:text-white">{!! $obfuscatedEmail !!}</a>
                    @endif
                    <a href="{{ route('pages.show', 'dove-trovarci') }}" class="block text-white/90 hover:text-white font-semibold uppercase tracking-wide text-xs mt-2">Dove trovarci</a>
                </div>
                <div class="space-y-3 text-sm font-semibold uppercase tracking-wide">
                    <a href="{{ route('posts.archive') }}" class="block text-white/90 hover:text-white">Archivio Notizie</a>
                    <a href="{{ route('posts.archive.comunicati') }}" class="block text-white/90 hover:text-white">Archivio Comunicati Stampa</a>
                    <a href="{{ route('trasparenza') }}" class="block text-white/90 hover:text-white">Trasparenza</a>
                    <a href="{{ $privacyPage ? route('pages.show', $privacyPage) : '#' }}" class="block text-white/90 hover:text-white">Privacy Policy</a>
                    <a href="{{ $cookiePage ? route('pages.show', $cookiePage) : '#' }}" class="block text-white/90 hover:text-white">Cookie Policy</a>
                    @if (config('tracking.ga.id') || ! app()->isProduction())
                        <a href="#cookie-preferences" class="block text-white/90 hover:text-white">Gestisci cookie</a>
                    @endif
                </div>
                <div class="space-y-3 text-sm font-semibold uppercase tracking-wide">
                    <a href="https://www.cri.it/trasparenza" target="_blank" rel="noopener" class="block text-white/90 hover:text-white">Amministrazione Trasparente (cri.it)</a>
                    <a href="https://www.cri.it" target="_blank" rel="noopener" class="block text-white/90 hover:text-white">Bandi di Gara (cri.it)</a>
                    <a href="https://www.cri.it" target="_blank" rel="noopener" class="block text-white/90 hover:text-white">Servizio Civile (cri.it)</a>
                    <a href="{{ route('pages.show', 'struttura-organizzativa') }}" class="block text-white/90 hover:text-white">Struttura Organizzativa</a>
                </div>
            </div>

            @if ($committeeInfo->denominazione || $committeeInfo->piva || $committeeInfo->codice_fiscale || $committeeInfo->pec || $committeeInfo->codice_fatturazione_elettronica)
                <div class="border-t border-white/20 mt-8 pt-6 text-xs text-white/70 space-y-0.5">
                    @if ($committeeInfo->denominazione)
                        <p>{{ $committeeInfo->denominazione }}</p>
                    @endif
                    <p>
                        @if ($committeeInfo->piva) P.IVA {{ $committeeInfo->piva }} @endif
                        @if ($committeeInfo->codice_fiscale) &middot; C.F. {{ $committeeInfo->codice_fiscale }} @endif
                        @if ($committeeInfo->pec) &middot; PEC {!! \App\Support\EmailObfuscator::protect(e($committeeInfo->pec)) !!} @endif
                        @if ($committeeInfo->codice_fatturazione_elettronica) &middot; Cod. fatturazione elettronica {{ $committeeInfo->codice_fatturazione_elettronica }} @endif
                    </p>
                </div>
            @endif

            {{-- Loghi istituzionali facoltativi (caricati dal pannello, non distribuiti con il codice): senza
                 file, un semplice link testuale a pillola rossa. Le immagini vanno direttamente sullo sfondo
                 scuro, senza riquadri forzati: chi carica un logo sceglie la versione adatta. --}}
            <div class="flex flex-wrap items-center gap-4 mt-8">
                @if ($committeeInfo->logo_ifrc_url)
                    <a href="https://www.ifrc.org" target="_blank" rel="noopener">
                        <img src="{{ $committeeInfo->logo_ifrc_url }}" alt="I.F.R.C. Member" class="h-9 w-auto">
                    </a>
                @else
                    <a href="https://www.ifrc.org" target="_blank" rel="noopener" class="inline-flex items-center rounded-full bg-[#cc0000] hover:bg-[#a30000] text-white text-xs font-semibold px-3 py-1 shadow-sm transition-colors">ifrc.org</a>
                @endif
                @if ($committeeInfo->logo_un_italia_url)
                    <a href="https://www.cri.it" target="_blank" rel="noopener">
                        <img src="{{ $committeeInfo->logo_un_italia_url }}" alt="Un'Italia che aiuta" class="h-9 w-auto">
                    </a>
                @else
                    <a href="https://www.cri.it" target="_blank" rel="noopener" class="inline-flex items-center rounded-full bg-[#cc0000] hover:bg-[#a30000] text-white text-xs font-semibold px-3 py-1 shadow-sm transition-colors">cri.it</a>
                @endif
            </div>

            {{-- Ultima riga a tre zone: social a sinistra, crediti al centro, copyright a destra. --}}
            <div class="border-t border-white/20 mt-8 pt-6 flex flex-col sm:grid sm:grid-cols-3 items-center gap-4">
                {{-- Ogni icona è facoltativa: compare solo se il relativo link è compilato in "Dati del
                     comitato" (admin).
                     Niente fallback su profili di un comitato specifico: su un'installazione nuova,
                     senza dati compilati, questo blocco resta vuoto. --}}
                @if ($committeeInfo->facebook_url || $committeeInfo->instagram_url || $committeeInfo->youtube_url || $committeeInfo->x_url)
                    <div class="flex items-center gap-4">
                        @if ($committeeInfo->facebook_url)
                            <a href="{{ $committeeInfo->facebook_url }}" target="_blank" rel="noopener" aria-label="Facebook" class="text-white/80 hover:text-white">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06c0 5 3.66 9.15 8.44 9.94v-7.03H7.9v-2.91h2.54V9.85c0-2.51 1.49-3.9 3.77-3.9 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56v1.87h2.78l-.44 2.91h-2.34V22c4.78-.79 8.44-4.94 8.44-9.94Z"/></svg>
                            </a>
                        @endif
                        @if ($committeeInfo->instagram_url)
                            <a href="{{ $committeeInfo->instagram_url }}" target="_blank" rel="noopener" aria-label="Instagram" class="text-white/80 hover:text-white">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.16c3.2 0 3.58.01 4.85.07 1.17.05 1.8.24 2.22.41.56.21.96.47 1.38.89.42.42.68.82.89 1.38.17.42.36 1.05.41 2.22.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.05 1.17-.24 1.8-.41 2.22-.21.56-.47.96-.89 1.38-.42.42-.82.68-1.38.89-.42.17-1.05.36-2.22.41-1.27.06-1.65.07-4.85.07s-3.58-.01-4.85-.07c-1.17-.05-1.8-.24-2.22-.41a3.7 3.7 0 0 1-1.38-.89 3.7 3.7 0 0 1-.89-1.38c-.17-.42-.36-1.05-.41-2.22-.06-1.27-.07-1.65-.07-4.85s.01-3.58.07-4.85c.05-1.17.24-1.8.41-2.22.21-.56.47-.96.89-1.38.42-.42.82-.68 1.38-.89.42-.17 1.05-.36 2.22-.41 1.27-.06 1.65-.07 4.85-.07M12 0C8.74 0 8.33.01 7.05.07c-1.28.06-2.15.26-2.91.56a5.87 5.87 0 0 0-2.13 1.38A5.87 5.87 0 0 0 .63 4.14c-.3.76-.5 1.63-.56 2.91C.01 8.33 0 8.74 0 12s.01 3.67.07 4.95c.06 1.28.26 2.15.56 2.91.31.79.72 1.46 1.38 2.13.67.66 1.34 1.07 2.13 1.38.76.3 1.63.5 2.91.56C8.33 23.99 8.74 24 12 24s3.67-.01 4.95-.07c1.28-.06 2.15-.26 2.91-.56a5.87 5.87 0 0 0 2.13-1.38 5.87 5.87 0 0 0 1.38-2.13c.3-.76.5-1.63.56-2.91.06-1.28.07-1.69.07-4.95s-.01-3.67-.07-4.95c-.06-1.28-.26-2.15-.56-2.91a5.87 5.87 0 0 0-1.38-2.13A5.87 5.87 0 0 0 19.86.63c-.76-.3-1.63-.5-2.91-.56C15.67.01 15.26 0 12 0Zm0 5.84A6.16 6.16 0 1 0 18.16 12 6.16 6.16 0 0 0 12 5.84Zm0 10.16A4 4 0 1 1 16 12a4 4 0 0 1-4 4Zm6.41-10.4a1.44 1.44 0 1 1-1.44-1.44 1.44 1.44 0 0 1 1.44 1.44Z"/></svg>
                            </a>
                        @endif
                        @if ($committeeInfo->youtube_url)
                            <a href="{{ $committeeInfo->youtube_url }}" target="_blank" rel="noopener" aria-label="YouTube" class="text-white/80 hover:text-white">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.5 6.19a3.02 3.02 0 0 0-2.12-2.14C19.51 3.5 12 3.5 12 3.5s-7.51 0-9.38.55A3.02 3.02 0 0 0 .5 6.19 31.6 31.6 0 0 0 0 12a31.6 31.6 0 0 0 .5 5.81 3.02 3.02 0 0 0 2.12 2.14C4.49 20.5 12 20.5 12 20.5s7.51 0 9.38-.55a3.02 3.02 0 0 0 2.12-2.14A31.6 31.6 0 0 0 24 12a31.6 31.6 0 0 0-.5-5.81ZM9.75 15.5v-7l6.5 3.5Z"/></svg>
                            </a>
                        @endif
                        @if ($committeeInfo->x_url)
                            <a href="{{ $committeeInfo->x_url }}" target="_blank" rel="noopener" aria-label="X" class="text-white/80 hover:text-white">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.24 2.25h3.31l-7.23 8.26 8.5 11.24h-6.66l-5.22-6.83-5.97 6.83H1.66l7.74-8.85L1.24 2.25h6.83l4.72 6.24 5.45-6.24Zm-1.16 17.52h1.83L7.02 4.13H5.06l12.02 15.64Z"/></svg>
                            </a>
                        @endif
                    </div>
                @else
                    <div></div>
                @endif
                {{-- Crediti: la versione permette di vedere a colpo d'occhio a che punto è un'installazione. --}}
                <p class="text-xs text-white/60 text-center">
                    Realizzato con
                    <a href="https://github.com/luigidacunto/niles-cms" target="_blank" rel="noopener" class="font-semibold text-white/80 hover:text-white">NILES</a>
                    <span class="ml-1">v{{ config('app.version') }}</span>
                </p>
                <p class="text-xs text-white/60 text-center sm:text-right">
                    &copy; {{ date('Y') }} {{ $committeeInfo->denominazione ?: config('app.public_name') }}
                </p>
            </div>
        </div>
    </footer>

    {{-- Banner cookie: solo se c'è qualcosa da consentire (GA4 configurato) o fuori produzione
         (così su staging resta sempre rivedibile). Vedi partials/cookie-consent.blade.php. --}}
    @if (config('tracking.ga.id') || ! app()->isProduction())
        @include('partials.cookie-consent')
    @endif

</body>
</html>
