@props(['href', 'title', 'excerpt' => null])

{{-- Card cliccabile standard per aree/elenchi dentro il corpo di una pagina (niente immagine — per quello
     vedi il pattern con overlay+foto di partials/news-items.blade.php, forma diversa). Nessun bordo a
     riposo, solo lo "sollevamento" (traslazione + ombra) e un velo rosso trasparente al passaggio mouse;
     titolo sempre in rosso brand. --}}
<a href="{{ $href }}" {{ $attributes->merge(['class' => 'group relative block overflow-hidden rounded-lg p-3 transition-all duration-200 hover:-translate-y-1 hover:shadow-lg']) }}>
    <div class="absolute inset-0 bg-[#cc0000]/0 group-hover:bg-[#cc0000]/5 transition-colors duration-200"></div>
    <div class="relative">
        <h3 class="font-semibold text-[#cc0000]">{{ $title }}</h3>
        @if ($excerpt)
            <p class="text-sm text-gray-500 mt-1">{{ $excerpt }}</p>
        @endif
    </div>
</a>
