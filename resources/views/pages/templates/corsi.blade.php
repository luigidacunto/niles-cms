{{-- Template "corsi" (pages.template): elenco dei corsi con iscrizioni aperte. $corsi arriva dal
     View::composer in AppServiceProvider. L'immagine è quella della tipologia (nessuna per singolo corso). --}}
<div class="mt-10">
    @forelse ($corsi as $corso)
        @if ($loop->first)
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @endif
        <article class="border border-gray-200 rounded overflow-hidden flex flex-col bg-white">
            @if ($corso->immagineUrl())
                <img src="{{ $corso->immagineUrl() }}" alt="" class="w-full aspect-[1200/630] object-cover" loading="lazy">
            @endif
            <div class="p-5 flex flex-col flex-1">
                <h2 class="text-lg font-semibold text-gray-900">{{ $corso->tipologia->nome }}</h2>
                <p class="text-sm text-gray-600 mt-2">{{ $corso->periodoLabel() }}</p>
                @if ($corso->sedeLabel())
                    <p class="text-sm text-gray-600">{{ $corso->sedeLabel() }}</p>
                @endif
                <p class="text-sm text-gray-600">
                    @if ($corso->costo > 0) Quota: {{ number_format($corso->costo, 2, ',', '.') }} € @else Gratuito @endif
                </p>
                <a href="{{ route('corsi.iscrizione.show', $corso) }}"
                   class="mt-5 inline-block self-start bg-[#cc0000] text-white font-medium px-5 py-2 rounded hover:bg-[#a30000] transition-colors">
                    Iscriviti
                </a>
            </div>
        </article>
        @if ($loop->last)
            </div>
        @endif
    @empty
        @php
            // Profili social configurati in "Dati del comitato": solo quelli compilati, nessun fallback.
            $info = \App\Models\CommitteeInfo::current();
            $social = array_filter([
                'Facebook' => $info->facebook_url, 'Instagram' => $info->instagram_url,
                'YouTube' => $info->youtube_url, 'X' => $info->x_url,
            ]);
        @endphp
        <div class="border border-gray-200 rounded p-6 text-gray-700">
            <p class="font-medium text-gray-900">Al momento non ci sono corsi con iscrizioni aperte.</p>
            <p class="mt-2">
                Ti invitiamo a visitare di nuovo questa pagina nei prossimi giorni
                @if ($social)
                    o a seguire i nostri canali social per restare aggiornato sui prossimi corsi:
                    @foreach ($social as $nome => $url)
                        <a href="{{ $url }}" target="_blank" rel="noopener" class="text-[#cc0000] hover:underline">{{ $nome }}</a>{{ $loop->last ? '.' : ',' }}
                    @endforeach
                @else
                    per restare aggiornato sui prossimi corsi.
                @endif
            </p>
        </div>
    @endforelse
</div>
