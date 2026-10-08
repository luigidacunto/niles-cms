@extends('layouts.public')

@php
    $titoli = [
        'annullato' => 'Corso annullato',
        'concluso' => 'Corso concluso',
        'iscrizioni_chiuse' => 'Iscrizioni chiuse',
    ];
    $messaggi = [
        'annullato' => 'Il corso "'.$corso->tipologia->nome.'" è stato annullato.'.($corso->motivo_annullamento ? ' Motivo: '.$corso->motivo_annullamento : ''),
        'concluso' => 'Il corso "'.$corso->tipologia->nome.'" si è già svolto, non sono più possibili nuove iscrizioni.',
        'iscrizioni_chiuse' => 'Le iscrizioni al corso "'.$corso->tipologia->nome.'" non sono (più) aperte.',
    ];
@endphp

@section('title', ($titoli[$stato] ?? 'Iscrizioni non disponibili').' — '.config('app.public_name'))

@section('content')
<div class="max-w-2xl mx-auto px-4 py-16 text-center">
    <h1 class="text-2xl font-bold text-gray-900 mb-3">{{ $titoli[$stato] ?? 'Iscrizioni non disponibili' }}</h1>
    <p class="text-gray-600 mb-6">{{ $messaggi[$stato] ?? 'Le iscrizioni a questo corso non sono disponibili.' }}</p>
    <a href="{{ url('/') }}" class="text-[#cc0000] font-semibold hover:underline">Torna alla home</a>
</div>
@stop
