{{-- <x-soci-breadcrumb :items="[['Documenti', route('soci.documenti')], ['Salute']]" /> — briciole di pane delle pagine
     riservate ai soci. La radice «Area Soci» è fissa, in rosso e senza link (non esiste ancora una home dell'area
     riservata: se nascerà, basterà dargli qui l'URL). Ogni voce è [etichetta, url?]: senza url non è un link. --}}
@props(['items' => []])

<nav aria-label="breadcrumb" class="text-sm text-gray-500 mb-6">
    <span class="font-semibold text-[#cc0000]">Area Soci</span>
    @foreach ($items as $item)
        <span class="mx-1">/</span>
        @if (! empty($item[1]))
            <a href="{{ $item[1] }}" class="hover:text-[#cc0000]">{{ $item[0] }}</a>
        @else
            <span class="text-gray-700">{{ $item[0] }}</span>
        @endif
    @endforeach
</nav>
