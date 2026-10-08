@extends('layouts.public')

@section('title', $comunicazione->title.' — '.config('app.public_name'))

@section('noindex', '1')

@section('content')
    <div class="max-w-3xl mx-auto px-4 py-10">
        <nav aria-label="breadcrumb" class="text-sm text-gray-500 mb-6">
            <a href="{{ route('soci.comunicazioni') }}" class="hover:text-[#cc0000]">Comunicazioni</a>
            <span class="mx-1">/</span>
            <span class="text-gray-700">{{ $comunicazione->title }}</span>
        </nav>

        <h1 class="text-2xl sm:text-3xl font-semibold text-gray-900">{{ $comunicazione->title }}</h1>
        <div class="w-14 h-1 bg-[#cc0000] mt-3"></div>
        <p class="text-sm text-gray-500 mt-4">{{ $comunicazione->published_at?->format('d/m/Y') }}</p>

        <div class="article-prose mt-6">
            {!! \App\Support\EmbedGate::protect(\App\Support\EmailObfuscator::protect($comunicazione->body)) !!}
        </div>

        <x-attachments-list :attachments="$comunicazione->attachments" />
    </div>
@endsection
