@extends('layouts.public')

@section('title', $comunicazione->title.' — '.config('app.public_name'))

@section('noindex', '1')

@section('content')
    <div class="max-w-3xl mx-auto px-4 py-10">
        <x-soci-breadcrumb :items="[['Comunicazioni', route('soci.comunicazioni')], [$comunicazione->title]]" />

        <h1 class="text-2xl sm:text-3xl font-semibold text-gray-900">{{ $comunicazione->title }}</h1>
        <div class="w-14 h-1 bg-[#cc0000] mt-3"></div>
        <p class="text-sm text-gray-500 mt-4">{{ $comunicazione->published_at?->format('d/m/Y') }}</p>

        @if ($comunicazione->coverUrl())
            <x-lightbox :src="$comunicazione->coverUrl()" :alt="$comunicazione->title" ratio="16 / 9" class="mt-6 rounded-lg" />
        @endif

        <div class="article-prose mt-6">
            {!! \App\Support\EmbedGate::protect(\App\Support\EmailObfuscator::protect($comunicazione->body)) !!}
        </div>

        @if ($comunicazione->images->isNotEmpty())
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 mt-8">
                @foreach ($comunicazione->images as $image)
                    <x-lightbox :src="$image->url" :alt="$image->caption ?? ''" ratio="1 / 1" class="rounded-lg" />
                @endforeach
            </div>
        @endif

        <x-attachments-list :attachments="$allegati" />
    </div>
@endsection
