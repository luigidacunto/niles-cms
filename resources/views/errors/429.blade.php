@extends('layouts.public')

@section('title', 'Troppi tentativi — '.config('app.public_name'))

@php
    // Secondi di attesa indicati dal rate limiter (header Retry-After); mostrati in minuti, arrotondati per eccesso.
    $secondi = (int) ($exception?->getHeaders()['Retry-After'] ?? 0);
    $minuti = $secondi > 0 ? (int) ceil($secondi / 60) : null;
    $info = \App\Models\CommitteeInfo::current();
@endphp

@section('content')

    <div class="max-w-[40rem] mx-auto px-4 py-20 text-center">
        <p class="text-[#cc0000] text-6xl font-bold">429</p>
        <h1 class="text-2xl sm:text-3xl font-semibold text-gray-900 mt-4">Troppi tentativi ravvicinati</h1>
        <div class="w-14 h-1 bg-[#cc0000] mt-4 mx-auto"></div>
        <p class="text-gray-600 mt-6">
            Per proteggere il sito da richieste automatiche abbiamo messo una pausa temporanea.
            @if ($minuti)
                Riprova tra {{ $minuti }} {{ $minuti === 1 ? 'minuto' : 'minuti' }}.
            @else
                Riprova tra qualche minuto.
            @endif
        </p>
        @if ($info->email || $info->telefono)
            <p class="text-gray-600 mt-3">
                Se hai bisogno di iscriverti con urgenza, contatta la segreteria
                @if ($info->telefono) al {{ $info->telefono }} @endif
                @if ($info->email) @if ($info->telefono) oppure @endif scrivi a <a href="mailto:{{ $info->email }}" class="text-[#cc0000] hover:underline">{{ $info->email }}</a> @endif.
            </p>
        @endif

        <div class="mt-10">
            <a href="{{ url('/') }}" class="inline-block bg-[#cc0000] text-white px-6 py-3 rounded font-medium hover:bg-[#a30000]">
                Torna alla home
            </a>
        </div>
    </div>

@endsection
