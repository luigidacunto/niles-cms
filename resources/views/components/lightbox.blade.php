@props(['src', 'alt' => '', 'class' => '', 'ratio' => null])

{{-- Immagine cliccabile che apre la versione intera a tutto schermo (Alpine, niente libreria).
     Usata dalla copertina e dalla griglia galleria in posts/show.blade.php.
     `class` va sul bottone-contenitore (dimensioni/arrotondamento); `ratio` (es. "1 / 1") impone
     un aspect-ratio inline così le miniature restano uniformi anche con foto di formati diversi;
     l'immagine riempie sempre il contenitore con object-cover. --}}
<div x-data="{ open: false }" {{ $attributes }}>
    <button type="button" @click="open = true"
            class="relative block w-full overflow-hidden cursor-zoom-in {{ $class }}"
            @if ($ratio) style="aspect-ratio: {{ $ratio }}" @endif>
        {{-- absolute inset-0: un <button> non risolve sempre `h-full` sul figlio (percentuale su
             altezza del button non deterministica in tutti i browser) → l'img si ancorava in alto
             invece di riempire e centrare il crop. --}}
        <img src="{{ $src }}" alt="{{ $alt }}" class="absolute inset-0 w-full h-full object-cover">
    </button>

    <div x-show="open" x-cloak @keydown.escape.window="open = false"
         class="fixed inset-0 z-50 bg-black/90 flex items-center justify-center p-4 cursor-zoom-out"
         @click="open = false">
        <img src="{{ $src }}" alt="{{ $alt }}" class="max-w-full max-h-full object-contain">
    </div>
</div>
