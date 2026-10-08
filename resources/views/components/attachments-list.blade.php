{{-- <x-attachments-list :attachments="..." /> — elenco allegati (documenti collegati) in fondo a un
     post/pagina, condiviso da posts/show.blade.php e pages/show.blade.php. Icona automatica per
     estensione, riconoscibile per tipo (Font Awesome "solid", stesso vendor locale dell'admin — vedi
     Document::faIcon() / iconColorClass()). --}}
@props(['attachments'])

@if ($attachments->isNotEmpty())
    <div class="mt-10">
        <x-section-title>Allegati</x-section-title>
        <ul class="divide-y divide-gray-200 border border-gray-200 rounded-lg overflow-hidden">
            @foreach ($attachments as $doc)
                <li>
                    <a href="{{ $doc->download_url }}" target="_blank"
                       class="flex items-center gap-3 p-3 hover:bg-gray-50 transition-colors">
                        <i class="fas {{ $doc->fa_icon }} {{ $doc->icon_color_class }} shrink-0 w-8 text-center" style="font-size:1.75rem"></i>
                        <span class="flex-1 text-gray-800">{{ $doc->title }}</span>
                        @if ($doc->human_size)
                            <span class="text-xs text-gray-400 shrink-0">{{ $doc->human_size }}</span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
@endif
