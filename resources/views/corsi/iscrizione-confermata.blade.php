@extends('layouts.public')

@section('title', 'Iscrizione confermata — '.config('app.public_name'))

@section('content')
<div class="max-w-2xl mx-auto px-4 py-16 text-center">
    <h1 class="text-2xl font-bold text-gray-900 mb-3">Iscrizione registrata</h1>
    <p class="text-gray-600 mb-6">
        Grazie! L'iscrizione al corso "{{ $corso->tipologia->nome }}" ({{ $corso->protocollo }}) è stata registrata.
        Sarai ricontattato/a dal Comitato per i dettagli su pagamento e svolgimento del corso.
    </p>
    <a href="{{ url('/') }}" class="text-[#cc0000] font-semibold hover:underline">Torna alla home</a>
</div>
@stop
