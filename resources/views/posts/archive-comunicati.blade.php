@extends('layouts.public')

@section('title', 'Archivio Comunicati Stampa — '.config('app.public_name'))

@section('content')

    <div class="max-w-4xl mx-auto px-4 py-10">
        <nav aria-label="breadcrumb" class="text-sm text-gray-500 mb-6">
            <a href="{{ url('/') }}" class="hover:text-[#cc0000]">Home</a>
            <span class="mx-1">/</span>
            <span class="text-gray-700">Archivio Comunicati Stampa</span>
        </nav>

        <x-section-title>Archivio Comunicati Stampa</x-section-title>

        <x-async-pagination :posts="$posts" partial="partials.comunicati-items" />
    </div>

@endsection
