{{-- Card a griglia, circa metà della riga orizzontale usata in home — stesso hover standard
     (sollevamento + velo rosso su tutta la card) di card-link/news-items. Estratto a parte così lo stesso
     markup serve sia al primo caricamento (posts/archive.blade.php) sia alla risposta AJAX della
     paginazione (PostController::archive()). Paginazione duplicata sopra e sotto la griglia — stesso $posts->links(),
     entrambe dentro data-pagination così il click viene intercettato in entrambi i punti. --}}
<div class="flex justify-end mb-6" data-pagination>
    {{ $posts->links() }}
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
    @foreach ($posts as $post)
        <a href="{{ route('posts.show', [$post->category, $post]) }}"
           class="group relative block overflow-hidden rounded-lg transition-all duration-200 hover:-translate-y-1 hover:shadow-lg">
            <div class="relative overflow-hidden rounded">
                <img src="{{ $post->cover_url }}" alt="{{ $post->title }}" class="w-full aspect-[16/9] object-cover">
            </div>
            <div class="pt-3">
                <h3 class="text-lg font-semibold text-gray-900 group-hover:text-[#cc0000] transition-colors duration-200">{{ $post->title }}</h3>
                <p class="flex items-center gap-1.5 text-xs text-gray-500 mt-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    {{ $post->published_at?->format('d/m/Y') }}
                </p>
                @if ($post->excerpt)
                    <p class="text-sm text-gray-600 mt-2">{{ \Illuminate\Support\Str::limit(strip_tags($post->excerpt), 120) }}</p>
                @endif
            </div>
            <div class="absolute inset-0 bg-[#cc0000]/0 group-hover:bg-[#cc0000]/5 transition-colors duration-200 pointer-events-none"></div>
        </a>
    @endforeach
</div>
<div class="flex justify-end mt-10" data-pagination>
    {{ $posts->links() }}
</div>
