@extends('layouts.public')

@section('title', 'Trasparenza — '.config('app.public_name'))

@section('content')

    <div class="max-w-[70rem] mx-auto px-4 py-10">
        <nav aria-label="breadcrumb" class="text-sm text-gray-500 mb-6">
            <a href="{{ url('/') }}" class="hover:text-[#cc0000]">Home</a>
            <span class="mx-1">/</span>
            <span class="text-gray-700">Trasparenza</span>
        </nav>

        <div class="mb-8">
            <h1 class="text-2xl sm:text-3xl font-semibold text-gray-900">{{ $page->title ?? 'Trasparenza' }}</h1>
            <div class="w-14 h-1 bg-[#cc0000] mt-3"></div>
        </div>

        @if ($page?->body)
            <div class="article-prose mb-10">
                {!! $page->body !!}
            </div>
        @endif

        @forelse ($categories as $category)
            @php
                // Gruppi per "tipologia": ordinati per documento più recente del gruppo (desc); il
                // bucket senza tipologia ("Altri documenti") sempre per ultimo. Dentro ogni gruppo i
                // documenti dal più recente.
                $groups = $category->documents
                    ->groupBy(fn ($d) => $d->type ?: '')
                    ->map(fn ($docs) => $docs->sortByDesc('published_at')->values())
                    ->sortByDesc(fn ($docs, $type) => $type === '' ? -1 : optional($docs->first()->published_at)->timestamp ?? 0);
            @endphp

            <details class="border border-gray-200 rounded-md mb-4 group" open>
                <summary class="cursor-pointer select-none list-none px-4 py-3 flex items-center justify-between bg-gray-50 hover:bg-gray-100 rounded-md">
                    <span class="text-lg font-semibold text-gray-900">{{ $category->name }}</span>
                    <span class="flex items-center gap-3 text-sm text-gray-500">
                        {{ $category->documents->count() }} {{ $category->documents->count() === 1 ? 'documento' : 'documenti' }}
                        <svg class="w-4 h-4 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </span>
                </summary>

                <div class="px-4 pb-4 pt-1">
                    @if ($category->description)
                        <p class="text-gray-600 mt-2 mb-3">{{ $category->description }}</p>
                    @endif

                    @foreach ($groups as $type => $docs)
                        <details class="border-b border-gray-200 last:border-b-0 group/type" open>
                            <summary class="cursor-pointer select-none list-none py-3 flex items-center justify-between">
                                <span class="text-sm font-semibold uppercase tracking-wide text-gray-600">
                                    {{ $type !== '' ? $type : 'Altri documenti' }}
                                </span>
                                <span class="flex items-center gap-2 text-xs text-gray-400">
                                    {{ $docs->count() }}
                                    <svg class="w-3.5 h-3.5 transition-transform group-open/type:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </span>
                            </summary>

                            <ul class="pb-2">
                                @foreach ($docs as $document)
                                    <li class="py-2">
                                        <a href="{{ $document->downloadUrl }}" target="_blank" rel="noopener"
                                           class="group/doc flex items-start gap-3">
                                            <svg class="w-5 h-5 flex-shrink-0 text-[#cc0000] mt-0.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
                                            </svg>
                                            <span>
                                                <span class="text-gray-900 group-hover/doc:text-[#cc0000] font-medium">{{ $document->title }}</span>
                                                @if ($document->description)
                                                    <span class="block text-sm text-gray-600">{{ $document->description }}</span>
                                                @endif
                                                <span class="block text-xs text-gray-400 mt-0.5">
                                                    @if ($document->published_at)
                                                        {{ $document->published_at->format('d/m/Y') }}
                                                    @endif
                                                    @if ($document->humanSize)
                                                        · PDF, {{ $document->humanSize }}
                                                    @else
                                                        · PDF
                                                    @endif
                                                </span>
                                            </span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </details>
                    @endforeach
                </div>
            </details>
        @empty
            <p class="text-gray-600">Nessun documento pubblicato al momento.</p>
        @endforelse
    </div>

@endsection
