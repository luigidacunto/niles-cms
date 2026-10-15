@extends('layouts.public')

@section('title', 'Documenti — '.config('app.public_name'))

@section('noindex', '1')

@section('content')
    <div class="max-w-4xl mx-auto px-4 py-10">
        <x-soci-breadcrumb :items="[['Documenti']]" />
        <h1 class="text-2xl font-bold text-gray-900 mb-1">Documenti</h1>
        <div class="w-14 h-1 bg-[#cc0000] mb-6"></div>

        {{-- Due livelli: categoria (decide chi vede cosa) → sottocategoria (solo organizzazione). I documenti senza
             sottocategoria stanno direttamente sotto la categoria. --}}
        <div x-data="{ aperta: null }">
            @forelse ($categorie as $categoria)
                @php($gruppi = $categoria->documents->groupBy(fn ($d) => $d->sottocategoria ?: '')->sortKeys())
                <section class="mb-3 rounded-lg border border-gray-200 bg-white overflow-hidden">
                    <button type="button" @click="aperta = aperta === {{ $categoria->id }} ? null : {{ $categoria->id }}"
                            class="w-full flex items-center justify-between gap-3 px-5 py-4 text-left hover:bg-gray-50 transition-colors">
                        <span class="text-lg font-semibold text-gray-900">{{ $categoria->label() }}
                            <span class="ml-1 text-sm font-normal text-gray-400">({{ $categoria->documents->count() }})</span>
                        </span>
                        <svg class="w-4 h-4 text-gray-400 transition-transform" :class="aperta === {{ $categoria->id }} && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div x-show="aperta === {{ $categoria->id }}" x-cloak class="border-t border-gray-100">
                        @foreach ($gruppi as $sotto => $documenti)
                            @if ($sotto === '')
                                @include('soci.documenti._elenco', ['documenti' => $documenti])
                            @else
                                <div x-data="{ sotto: false }" class="border-t border-gray-100 first:border-t-0">
                                    <button type="button" @click="sotto = !sotto"
                                            class="w-full flex items-center justify-between gap-3 pl-8 sm:pl-10 pr-5 py-3 text-left bg-gray-50 hover:bg-gray-100 transition-colors">
                                        <span class="font-medium text-gray-800">{{ $sotto }}
                                            <span class="ml-1 text-sm font-normal text-gray-400">({{ $documenti->count() }})</span>
                                        </span>
                                        <svg class="w-3 h-3 text-gray-400 transition-transform" :class="sotto && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>
                                    <div x-show="sotto" x-cloak>
                                        @include('soci.documenti._elenco', ['documenti' => $documenti, 'rientro' => true])
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </section>
            @empty
                <p class="text-gray-500">Nessun documento disponibile al momento.</p>
            @endforelse
        </div>
    </div>
@endsection
