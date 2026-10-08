@extends('layouts.public')

@section('title', config('app.public_name'))

@section('content')

    {{-- Hero: il post pubblicato più recente. ponytail: "più recente" è un placeholder, la vera logica
         di cosa va in evidenza si rivede quando costruiamo il CRUD post (vedi HomeController). --}}
    @if ($hero)
        <div class="relative h-[420px] sm:h-[520px] overflow-hidden bg-gray-900"
             style="background-image: linear-gradient(to top, rgba(0,0,0,.55), rgba(0,0,0,.05) 40%), url('{{ $hero->cover_url }}');
                    background-size: cover; background-position: center;">
            <div class="absolute left-6 sm:left-12 top-1/2 -translate-y-1/2 bg-white/95 rounded shadow-lg p-6 max-w-sm">
                <h2 class="text-xl sm:text-2xl font-semibold text-gray-900">{{ $hero->title }}</h2>
                @if ($hero->excerpt)
                    <p class="text-sm text-gray-600 mt-3">{{ \Illuminate\Support\Str::limit(strip_tags($hero->excerpt), 160) }}</p>
                @endif
                <x-cta :href="route('posts.show', [$hero->category, $hero])" class="mt-4">Scopri di più</x-cta>
            </div>
        </div>
    @endif

    {{-- Come fare per richiedere... — caricato dal DB (figlie pubblicate della pagina "servizi"), vedi
         HomeController::services(). Vuoto se non ci sono servizi pubblicati, invece di mostrare un blocco
         vuoto. --}}
    @if ($services->isNotEmpty())
        <div class="max-w-6xl mx-auto px-4 mt-12">
            <x-section-title center>Come fare per richiedere ...</x-section-title>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-center sm:text-left">
                @foreach ($services as $service)
                    <x-card-link :href="route('pages.show', $service)" :title="$service->title" :excerpt="$service->excerpt" />
                @endforeach
            </div>
        </div>
    @endif

    {{-- News: colonna unica, stile cri.it, caricate dal DB. "Carica altre" pesca via async le 5
         successive (route home.news.more) finché non arriva alla più vecchia. --}}
    <div class="max-w-6xl mx-auto px-4 mt-12" x-data="{
        offset: {{ $nextOffset }},
        hasMore: {{ $hasMore ? 'true' : 'false' }},
        loading: false,
        loadMore() {
            this.loading = true;
            fetch('{{ route('home.news.more') }}?offset=' + this.offset)
                .then(r => r.json())
                .then(data => {
                    this.$refs.newsList.insertAdjacentHTML('beforeend', data.html);
                    this.offset += 5;
                    this.hasMore = data.hasMore;
                    this.loading = false;
                });
        }
    }">
        <x-section-title>News</x-section-title>
        <div x-ref="newsList">
            @include('partials.news-items', ['news' => $news])
        </div>
        <div class="flex items-center justify-center gap-3 mt-4">
            <button type="button" @click="loadMore()" :disabled="loading" x-show="hasMore" x-cloak
                    class="inline-block bg-[#cc0000] text-white text-sm font-medium px-5 py-2 rounded hover:bg-[#a30000] disabled:opacity-50">
                <span x-show="!loading">Carica altre notizie</span>
                <span x-show="loading" x-cloak>Caricamento...</span>
            </button>
            {{-- Verso l'archivio completo (paginato) — freccia a indicare che porta ad un'altra pagina,
                 non un caricamento in-place come il bottone sopra. --}}
            <a href="{{ route('posts.archive') }}"
               class="inline-flex items-center gap-1.5 border border-[#cc0000] text-[#cc0000] text-sm font-medium px-5 py-2 rounded hover:bg-[#cc0000] hover:text-white transition-colors">
                Archivio notizie
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </a>
        </div>
    </div>

    {{-- Comunicati Stampa: sotto le news, solo testo (nessuna immagine) — 3 più recenti reali della
         categoria comunicato-stampa (vedi HomeController::comunicatiStampa()). --}}
    @if ($comunicatiStampa->isNotEmpty())
        <div class="max-w-6xl mx-auto px-4 mt-12 mb-16">
            <x-section-title>Comunicati Stampa</x-section-title>
            <div class="divide-y divide-gray-100">
                @foreach ($comunicatiStampa as $comunicato)
                    <x-card-link :href="route('posts.show', [$comunicato->category, $comunicato])"
                                 :title="$comunicato->title"
                                 :excerpt="$comunicato->published_at?->format('d/m/Y')" class="-mx-3" />
                @endforeach
            </div>
            <div class="text-center mt-4">
                <x-cta :href="route('posts.archive.comunicati')">Vedi tutti</x-cta>
            </div>
        </div>
    @endif

@endsection
