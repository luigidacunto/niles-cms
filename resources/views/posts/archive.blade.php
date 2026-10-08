@extends('layouts.public')

@section('title', 'Archivio Notizie — '.config('app.public_name'))

@section('content')

    <div class="max-w-6xl mx-auto px-4 py-10">
        <nav aria-label="breadcrumb" class="text-sm text-gray-500 mb-6">
            <a href="{{ url('/') }}" class="hover:text-[#cc0000]">Home</a>
            <span class="mx-1">/</span>
            <span class="text-gray-700">Archivio Notizie</span>
        </nav>

        <x-section-title>Archivio Notizie</x-section-title>

        <x-async-pagination :posts="$posts" partial="partials.archive-items" />
    </div>

@endsection
