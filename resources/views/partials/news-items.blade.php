@foreach ($news as $item)
    {{-- Stesso pattern standard di card-link: sollevamento (traslazione + ombra) + velo rosso trasparente
         su tutta la card (non solo sulla foto) — overlay come ultimo figlio così sta sopra sia
         all'immagine che al testo. px/-mx compensano il padding aggiunto così la riga non si sposta
         orizzontalmente rispetto alle altre. --}}
    <a href="{{ route('posts.show', [$item->category, $item]) }}"
       class="group relative flex flex-col sm:flex-row gap-6 py-7 px-3 -mx-3 overflow-hidden rounded-lg border-t border-gray-100 first:border-t-0 transition-all duration-200 hover:-translate-y-1 hover:shadow-lg hover:border-transparent">
        <div class="sm:w-[420px] flex-shrink-0 relative overflow-hidden rounded">
            <img src="{{ $item->cover_url }}" alt="{{ $item->title }}" class="w-full aspect-[16/9] object-cover">
        </div>
        <div class="flex-1">
            <h3 class="text-2xl font-semibold text-gray-900 group-hover:text-[#cc0000] transition-colors duration-200">{{ $item->title }}</h3>
            <p class="flex items-center gap-1.5 text-sm text-gray-500 mt-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                {{ $item->published_at?->format('d/m/Y') }}
            </p>
            @if ($item->excerpt)
                <p class="text-base text-gray-600 mt-3">{{ \Illuminate\Support\Str::limit(strip_tags($item->excerpt), 220) }}</p>
            @endif
        </div>
        <div class="absolute inset-0 bg-[#cc0000]/0 group-hover:bg-[#cc0000]/5 transition-colors duration-200 pointer-events-none"></div>
    </a>
@endforeach
