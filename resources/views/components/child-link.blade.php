@props(['href', 'title', 'excerpt' => null])

{{-- Link a una pagina figlia, in fondo a una pagina padre (pages/show): blocco a tutta larghezza, titolo rosso centrato,
     estratto sotto, freccia a destra. Volutamente diverso da <x-card-link> (schede senza bordo in griglia) e dal
     pulsante «Tutte le notizie» (contorno rosso, a larghezza del testo). --}}
<a href="{{ $href }}"
   {{ $attributes->merge(['class' => 'group relative block w-full border border-gray-200 rounded-lg bg-white px-12 py-4 text-center transition hover:border-[#cc0000] hover:bg-red-50/50 hover:shadow-md']) }}>
    <span class="block text-lg font-semibold text-[#cc0000]">{{ $title }}</span>
    @if (filled($excerpt))
        <span class="block mt-1 text-sm text-gray-600">{{ $excerpt }}</span>
    @endif
    <svg class="absolute right-5 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-300 transition group-hover:text-[#cc0000] group-hover:translate-x-0.5"
         fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
</a>
