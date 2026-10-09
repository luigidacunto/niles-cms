@extends('layouts.public')

@section('title', $page->title.' — '.config('app.public_name'))
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($page->excerpt ?: $page->body), 160))

@push('jsonld')
    @include('partials.jsonld-breadcrumb', ['items' => array_merge(
        [['Home', url('/')]],
        $page->breadcrumbTrail()->map(fn ($crumb) => [$crumb->title, route('pages.show', $crumb)])->all()
    )])
@endpush

@section('content')

    <div class="max-w-[70rem] mx-auto px-4 py-10">
        @include('partials.breadcrumb', ['page' => $page])

        {{-- Titolo + accento rosso corto sotto, stile CRI (niente hero/banner sulle pagine di contenuto,
             solo breadcrumb + titolo + testo — verificato su cri.it, le pagine interne sono sobrie). --}}
        <div class="mb-8">
            <h1 class="text-2xl sm:text-3xl font-semibold text-gray-900">{{ $page->title }}</h1>
            <div class="w-14 h-1 bg-[#cc0000] mt-3"></div>
            @if ($page->excerpt)
                <p class="text-lg font-medium text-gray-600 mt-4">{{ $page->excerpt }}</p>
            @endif
        </div>

        {{-- .article-prose (resources/css/app.css) — condivisa con posts/show.blade.php. --}}
        <div class="article-prose">
            {!! \App\Support\EmbedGate::protect(\App\Support\EmailObfuscator::protect($page->body)) !!}
        </div>

        {{-- Blocco embed grezzo (script/form donazioni): impostabile solo da un admin, reso senza
             sanitizer. --}}
        @if (filled($page->embed_html))
            <div class="mt-8">
                {!! $page->embed_html !!}
            </div>
        @endif

        {{-- Template di pagina (`pages.template`): vista scritta a mano in pages/templates/<chiave>.blade.php,
             dati forniti da un View::composer in AppServiceProvider. --}}
        @if ($page->template && view()->exists('pages.templates.'.$page->template))
            @include('pages.templates.'.$page->template)
        @endif

        <x-attachments-list :attachments="$page->attachments" />

        {{-- Pagina padre: in fondo elenca da sola le figlie visibili nel menu (pubblicate e «Mostra nel menu»), così
             anche una voce senza testo ha un senso. $figlie è vuota per le pagine senza figlie. --}}
        @if ($figlie->isNotEmpty())
            <div class="mt-12">
                <x-section-title>Approfondisci</x-section-title>
                <div class="flex flex-col gap-3">
                    @foreach ($figlie as $figlia)
                        <x-child-link :href="route('pages.show', $figlia)" :title="$figlia->title" :excerpt="$figlia->excerpt" />
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Condivisione: solo sulle pagine di contenuto vere, non su quelle di sistema
             (privacy, cookie policy, struttura organizzativa…). --}}
        @unless ($page->system)
            <x-share :title="$page->title" />
        @endunless

        {{-- Pagina sezione (es. cosa-facciamo/*): ultime notizie della categoria collegata + link
             all'archivio completo. $news è vuota se la pagina non ha una categoria. --}}
        @if ($news->isNotEmpty())
            <div class="mt-14">
                <x-section-title>Ultime notizie</x-section-title>
                @include('partials.news-items', ['news' => $news])
                <div class="mt-10 text-center">
                    <a href="{{ route('pages.archive.category', $page) }}"
                       class="inline-block border border-[#cc0000] text-[#cc0000] font-medium px-6 py-2.5 rounded hover:bg-[#cc0000] hover:text-white transition-colors">
                        Tutte le notizie · {{ $page->title }}
                    </a>
                </div>
            </div>
        @endif
    </div>

@endsection
