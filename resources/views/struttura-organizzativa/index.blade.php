@extends('layouts.public')

@section('title', ($page->title ?? 'Struttura Organizzativa').' — '.config('app.public_name'))

@section('content')

    <div class="max-w-[70rem] mx-auto px-4 py-10">
        <nav aria-label="breadcrumb" class="text-sm text-gray-500 mb-6">
            @foreach ($page->breadcrumbTrail() as $crumb)
                @if ($loop->first)
                    <a href="{{ url('/') }}" class="hover:text-[#cc0000]">Home</a>
                    <span class="mx-1">/</span>
                @endif
                @if ($loop->last)
                    <span class="text-gray-700">{{ $crumb->title }}</span>
                @else
                    <a href="{{ route('pages.show', $crumb) }}" class="hover:text-[#cc0000]">{{ $crumb->title }}</a>
                    <span class="mx-1">/</span>
                @endif
            @endforeach
        </nav>

        <div class="mb-8">
            <h1 class="text-2xl sm:text-3xl font-semibold text-gray-900">{{ $page->title ?? 'Struttura Organizzativa' }}</h1>
            <div class="w-14 h-1 bg-[#cc0000] mt-3"></div>
        </div>

        @if ($page->body)
            <div class="article-prose mb-10">
                {!! $page->body !!}
            </div>
        @endif

        @if ($members->isNotEmpty())
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($members as $member)
                    <div class="border border-gray-200 rounded-lg overflow-hidden bg-white">
                        <img src="{{ $member->photoUrl }}" alt="{{ $member->name }}"
                             class="w-full aspect-[3/4] object-cover bg-gray-100">
                        <div class="p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-[#cc0000]">{{ $member->roleName }}</p>
                            <p class="text-lg font-semibold text-gray-900 mt-0.5">{{ $member->name }}</p>
                            @if ($member->bio)
                                <p class="text-sm text-gray-600 mt-2 whitespace-pre-line">{{ $member->bio }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-600">Le informazioni sulla struttura organizzativa non sono ancora disponibili.</p>
        @endif

        @if ($organigramma)
            <div class="mt-10 border border-gray-200 rounded-lg p-4 bg-gray-50">
                <a href="{{ $organigramma->downloadUrl }}" target="_blank" rel="noopener"
                   class="group flex items-start gap-3">
                    <svg class="w-5 h-5 flex-shrink-0 text-[#cc0000] mt-0.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
                    </svg>
                    <span>
                        <span class="text-gray-900 group-hover:text-[#cc0000] font-medium">Organigramma del Comitato</span>
                        <span class="block text-xs text-gray-400 mt-0.5">
                            {{ strtoupper($organigramma->extension) }}@if ($organigramma->humanSize), {{ $organigramma->humanSize }}@endif
                        </span>
                    </span>
                </a>
            </div>
        @endif
    </div>

@endsection
