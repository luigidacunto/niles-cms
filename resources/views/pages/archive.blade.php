@extends('layouts.public')

@section('title', 'Notizie · '.$page->title.' — '.config('app.public_name'))

@section('content')

    <div class="max-w-6xl mx-auto px-4 py-10">
        @include('partials.breadcrumb', ['page' => $page, 'coda' => 'Notizie'])

        <x-section-title>Notizie · {{ $page->title }}</x-section-title>

        <x-async-pagination :posts="$posts" partial="partials.archive-items" />
    </div>

@endsection
