{{-- `$rientro`: elenco dentro una sottocategoria → rientrato con una guida verticale a sinistra. --}}
<ul class="divide-y divide-gray-100 {{ ($rientro ?? false) ? 'ml-6 sm:ml-8 mr-2 mb-2 border-l-2 border-[#cc0000]/30' : '' }}">
    @foreach ($documenti as $doc)
        <li>
            <a href="{{ route('soci.documenti.anteprima', $doc) }}" class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50 transition-colors">
                <i class="fas {{ $doc->fa_icon }} {{ $doc->icon_color_class }} shrink-0 w-8 text-center" style="font-size:1.5rem"></i>
                <span class="flex-1">
                    <span class="block text-gray-800">{{ $doc->title }}</span>
                    @if ($doc->description)
                        <span class="block text-sm text-gray-500">{{ $doc->description }}</span>
                    @endif
                </span>
                @if ($doc->human_size)
                    <span class="text-xs text-gray-400 shrink-0">{{ $doc->human_size }}</span>
                @endif
            </a>
        </li>
    @endforeach
</ul>
