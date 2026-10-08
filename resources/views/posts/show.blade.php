@extends('layouts.public')

@section('title', $post->title.' — '.config('app.public_name'))
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($post->excerpt ?: $post->body), 160))
@section('og_type', 'article')
@if ($post->cover_image)
    @section('og_image', $post->cover_url)
@endif

@push('jsonld')
    <script type="application/ld+json">
        {!! json_encode(array_filter([
            '@@context' => 'https://schema.org',
            '@type' => 'NewsArticle',
            'headline' => $post->title,
            'description' => \Illuminate\Support\Str::limit(strip_tags($post->excerpt ?: $post->body), 300),
            'image' => $post->cover_image ? url($post->cover_url) : null,
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified' => ($post->last_edited_at ?? $post->published_at)?->toIso8601String(),
            'author' => $post->author_name ? ['@type' => 'Person', 'name' => $post->author_name] : null,
        ]), JSON_UNESCAPED_SLASHES) !!}
    </script>
@endpush

@push('jsonld')
    @include('partials.jsonld-breadcrumb', ['items' => [['Home', url('/')], [$post->title, route('posts.show', [$post->category, $post])]]])
@endpush

@section('content')

    {{-- Cover a piena larghezza, solo immagine (niente testo sovrapposto: il contrasto col bianco del
         titolo dipenderebbe dai colori di ogni singola foto — troppo fragile). Cliccabile per aprire
         l'immagine intera in un lightbox, senza il taglio del crop 16:9. --}}
    <x-lightbox :src="$post->cover_url" :alt="$post->title" class="h-[380px] sm:h-[520px]" />

    <div class="max-w-[70rem] mx-auto px-4 py-10">
        <nav aria-label="breadcrumb" class="text-sm text-gray-500 mb-6">
            <a href="{{ url('/') }}" class="hover:text-[#cc0000]">Home</a>
            <span class="mx-1">/</span>
            <span class="text-gray-700">{{ $post->category->name }}</span>
            <span class="mx-1">/</span>
            <span class="text-gray-700">{{ $post->title }}</span>
        </nav>

        <div class="mb-6">
            <h1 class="text-2xl sm:text-3xl font-semibold text-gray-900">{{ $post->title }}</h1>
            <div class="w-14 h-1 bg-[#cc0000] mt-3"></div>
            @if ($post->subtitle)
                <p class="text-lg font-medium text-gray-600 mt-4">{{ $post->subtitle }}</p>
            @endif
            <p class="flex items-center gap-1.5 text-sm text-gray-500 mt-4">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                {{ $post->published_at?->format('d/m/Y') }}
            </p>
        </div>

        {{-- .article-prose (resources/css/app.css) — condivisa con pages/show.blade.php, un solo posto
             per la tipografia del corpo testo su tutto il sito pubblico. --}}
        <div class="article-prose">
            {!! \App\Support\EmbedGate::protect(\App\Support\EmailObfuscator::protect($post->body)) !!}
        </div>

        @if ($post->images->isNotEmpty())
            <div class="mt-10">
                <x-section-title>Galleria</x-section-title>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                    @foreach ($post->images as $image)
                        <figure class="group">
                            <x-lightbox :src="$image->url" :alt="$image->alt ?: $post->title" ratio="1 / 1"
                                class="rounded transition-all duration-200 group-hover:shadow-lg" />
                            @if ($image->caption)
                                <figcaption class="text-xs text-gray-500 mt-1">{{ $image->caption }}</figcaption>
                            @endif
                        </figure>
                    @endforeach
                </div>
            </div>
        @endif

        <x-attachments-list :attachments="$post->attachments" />

        <x-share :title="$post->title" />
    </div>

@endsection
