@extends('layouts.public')

@section('title', 'Pagina non trovata — '.config('app.public_name'))

@section('content')

    <div class="max-w-[40rem] mx-auto px-4 py-20 text-center">
        <p class="text-[#cc0000] text-6xl font-bold">404</p>
        <h1 class="text-2xl sm:text-3xl font-semibold text-gray-900 mt-4">Pagina non trovata</h1>
        <div class="w-14 h-1 bg-[#cc0000] mt-4 mx-auto"></div>
        <p class="text-gray-600 mt-6">
            La pagina che cerchi potrebbe essere stata spostata, rimossa o non esiste più.
        </p>

        <div class="mt-10 flex flex-wrap justify-center gap-4">
            <a href="{{ url('/') }}" class="inline-block bg-[#cc0000] text-white px-6 py-3 rounded font-medium hover:bg-[#a30000]">
                Torna alla home
            </a>
            <a href="{{ route('posts.archive') }}" class="inline-block border border-gray-300 text-gray-700 px-6 py-3 rounded font-medium hover:bg-gray-50">
                Vai alle news
            </a>
        </div>
    </div>

@endsection
