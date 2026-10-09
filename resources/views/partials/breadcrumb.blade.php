{{-- Breadcrumb dinamico: risale la catena dei genitori, nessun percorso scritto a mano per pagina (vedi
     Page::breadcrumbTrail()). Parametri: $page (pagina corrente), $coda (etichetta finale facoltativa
     dopo la pagina, es. «Notizie» per l'archivio: in quel caso la pagina corrente resta un link). --}}
<nav aria-label="breadcrumb" class="text-sm text-gray-500 mb-6">
    <a href="{{ url('/') }}" class="hover:text-[#cc0000]">Home</a>
    @foreach ($page->breadcrumbTrail() as $crumb)
        <span class="mx-1">/</span>
        @if ($crumb->is($page) && ! isset($coda))
            <span class="text-gray-700">{{ $crumb->title }}</span>
        @else
            <a href="{{ route('pages.show', $crumb) }}" class="hover:text-[#cc0000]">{{ $crumb->title }}</a>
        @endif
    @endforeach
    @isset($coda)
        <span class="mx-1">/</span>
        <span class="text-gray-700">{{ $coda }}</span>
    @endisset
</nav>
