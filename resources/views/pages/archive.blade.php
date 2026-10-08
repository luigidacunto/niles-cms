@extends('layouts.public')

@section('title', 'Notizie · '.$page->title.' — '.config('app.public_name'))

@section('content')

    <div class="max-w-6xl mx-auto px-4 py-10">
        <nav aria-label="breadcrumb" class="text-sm text-gray-500 mb-6">
            <a href="{{ url('/') }}" class="hover:text-[#cc0000]">Home</a>
            @foreach ($page->breadcrumbTrail() as $crumb)
                <span class="mx-1">/</span>
                <a href="{{ route('pages.show', $crumb) }}" class="hover:text-[#cc0000]">{{ $crumb->title }}</a>
            @endforeach
            <span class="mx-1">/</span>
            <span class="text-gray-700">Notizie</span>
        </nav>

        <x-section-title>Notizie · {{ $page->title }}</x-section-title>

        <x-async-pagination :posts="$posts" partial="partials.archive-items" />
    </div>

@endsection
