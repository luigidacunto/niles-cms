@extends('layouts.public')

@section('title', $document->title.' — '.config('app.public_name'))

@section('noindex', '1')

@section('content')
    <div class="max-w-5xl mx-auto px-4 py-10">
        <x-soci-breadcrumb :items="array_filter([
            ['Documenti', route('soci.documenti')],
            [$document->category->label()],
            $document->sottocategoria ? [$document->sottocategoria] : null,
        ])" />

        <div class="flex flex-wrap items-start justify-between gap-4 mb-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">{{ $document->title }}</h1>
                @if ($document->description)
                    <p class="text-gray-600 mt-1">{{ $document->description }}</p>
                @endif
            </div>
            {{-- Il download è sempre una scelta esplicita: aprire il documento mostra solo l'anteprima. --}}
            <a href="{{ route('soci.documenti.scarica', $document) }}"
               class="inline-flex items-center gap-2 rounded border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:border-[#cc0000] hover:text-[#cc0000] transition-colors">
                <i class="fas fa-download"></i> Scarica{{ $document->human_size ? ' ('.$document->human_size.')' : '' }}
            </a>
        </div>

        @if ($immagine)
            <img src="{{ route('soci.documenti.file', $document) }}" alt="{{ $document->title }}" class="max-w-full mx-auto rounded border border-gray-200">
        @else
            <iframe src="{{ route('soci.documenti.file', $document) }}" title="{{ $document->title }}"
                    class="w-full rounded border border-gray-200" style="height:80vh"></iframe>
        @endif
    </div>
@endsection
