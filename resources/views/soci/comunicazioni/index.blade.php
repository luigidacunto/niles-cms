@extends('layouts.public')

@section('title', 'Comunicazioni — '.config('app.public_name'))

@section('noindex', '1')

@section('content')
    <div class="max-w-4xl mx-auto px-4 py-10">
        <h1 class="text-2xl font-bold text-gray-900 mb-1">Comunicazioni</h1>
        <div class="w-14 h-1 bg-[#cc0000] mb-6"></div>

        @forelse ($comunicazioni as $comunicazione)
            {{-- Stesso pattern delle card notizie della home (partials/news-items): sollevamento + ombra + velo
                 rosso sull'intera card al passaggio. Qui senza foto (le comunicazioni non hanno copertina):
                 una barra rossa a sinistra tiene il peso visivo del blocco. --}}
            <a href="{{ route('soci.comunicazioni.show', $comunicazione) }}"
               class="group relative block py-6 pl-5 pr-4 mb-3 overflow-hidden rounded-lg border border-gray-100 border-l-4 border-l-[#cc0000] bg-white transition-all duration-200 hover:-translate-y-1 hover:shadow-lg hover:border-gray-100 hover:border-l-[#cc0000]">
                <h2 class="text-xl sm:text-2xl font-semibold text-gray-900 group-hover:text-[#cc0000] transition-colors duration-200">{{ $comunicazione->title }}</h2>
                <p class="flex items-center gap-1.5 text-sm text-gray-500 mt-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    {{ $comunicazione->published_at?->format('d/m/Y') }}
                </p>
                @if ($comunicazione->excerpt)
                    <p class="text-base text-gray-600 mt-3">{{ \Illuminate\Support\Str::limit(strip_tags($comunicazione->excerpt), 220) }}</p>
                @endif
                <div class="absolute inset-0 bg-[#cc0000]/0 group-hover:bg-[#cc0000]/5 transition-colors duration-200 pointer-events-none"></div>
            </a>
        @empty
            <p class="text-gray-500">Nessuna comunicazione al momento.</p>
        @endforelse

        <div class="mt-6">{{ $comunicazioni->links() }}</div>
    </div>
@endsection
