@extends('layouts.public')

{{-- Pagina personale con token nell'URL: mai indicizzata (noindex), niente statistiche (il token finirebbe nei
     report). --}}
@section('noindex', '1')
@section('noanalytics', '1')
@section('title', 'La tua pagina personale — '.config('app.public_name'))

@php
    // C'è almeno un corso in programma la cui presenza non è ancora stata confermata?
    $daConfermare = $iscrizioni->contains(fn ($i) => ! $i->presenza_confermata_at && $i->presenzaConfermabile());
@endphp

@php
    // Subito dopo aver salvato (flash `confermato`): pagina di conferma con solo i dati e il messaggio in verde, senza il
    // modulo dei consensi. Ricaricandola o riaprendo il link dall'email si torna alla pagina completa.
    $soloConferma = session()->has('confermato');
@endphp

@section('content')
<div class="max-w-2xl mx-auto px-4 py-10">
    <h1 class="text-2xl font-bold text-gray-900">Ciao {{ $persona->nome }}</h1>
    <p class="text-sm text-gray-500 mt-1 mb-6">Questa è la tua pagina personale: non condividere il suo indirizzo con altri.</p>

    @if (session('confermato') || session('status'))
        <div class="bg-green-50 border border-green-200 text-green-800 rounded p-4 mb-6 text-sm">{{ session('confermato') ?? session('status') }}</div>
    @endif

    <section class="border border-gray-200 rounded p-5 mb-6">
        <h2 class="font-semibold text-gray-900 mb-3">I tuoi corsi</h2>
        @forelse ($iscrizioni as $iscrizione)
            <div class="py-2 {{ $loop->last ? '' : 'border-b border-gray-100' }}">
                <p class="font-medium text-gray-800">{{ $iscrizione->corso->tipologia->nome }}</p>
                <p class="text-sm text-gray-600">
                    {{ $iscrizione->corso->periodoLabel() }}@if ($iscrizione->corso->sedeLabel()) &middot; {{ $iscrizione->corso->sedeLabel() }}@endif
                    &middot; Protocollo {{ $iscrizione->corso->protocollo }}
                </p>
                @if (! $iscrizione->corso->annullato() && $iscrizione->presenzaConfermabile() && ($bonifico = \App\Support\DatiPagamento::bonifico($iscrizione)))
                    <div class="mt-2 border border-gray-200 rounded bg-gray-50 p-3 text-sm text-gray-700">
                        <p class="font-medium text-gray-800 mb-1">Come pagare: bonifico bancario</p>
                        <p>Importo {{ $bonifico['importo'] }} &middot; Intestatario {{ $bonifico['intestatario'] }}</p>
                        <p>IBAN <span class="font-mono">{{ $bonifico['iban'] }}</span>@if ($bonifico['banca']) &middot; {{ $bonifico['banca'] }}@endif</p>
                        <p>Causale: {{ $bonifico['causale'] }}</p>
                    </div>
                @endif
                @if ($iscrizione->ritirata())
                    <p class="text-sm font-medium text-gray-500 mt-1">Iscrizione ritirata</p>
                @elseif ($iscrizione->corso->annullato())
                    <p class="text-sm font-medium text-red-700 mt-1">Corso annullato</p>
                @elseif ($iscrizione->presenza_confermata_at)
                    <p class="text-sm font-medium text-green-700 mt-1">&#10003; Presenza confermata il {{ $iscrizione->presenza_confermata_at->format('d/m/Y') }}</p>
                @elseif ($iscrizione->presenzaConfermabile())
                    <p class="text-sm font-medium text-amber-700 mt-1">Presenza da confermare</p>
                @endif
            </div>
        @empty
            <p class="text-sm text-gray-600">Nessuna iscrizione registrata.</p>
        @endforelse
    </section>

    @if ($soloConferma)
        <p class="text-sm text-gray-600">Puoi chiudere questa pagina. Per cambiare le tue scelte in futuro riapri il link che trovi nell'email di iscrizione.</p>
    @else
    <section class="border border-gray-200 rounded p-5 mb-6">
        <h2 class="font-semibold text-gray-900 mb-1">Le comunicazioni che vuoi ricevere</h2>
        <p class="text-sm text-gray-600 mb-4">Sono facoltative e puoi cambiare idea quando vuoi: le tue scelte valgono da subito.@if ($daConfermare) Il pulsante qui sotto conferma anche la tua presenza ai corsi in programma. Se non puoi partecipare, avvisa la segreteria.@endif</p>

        <form method="POST" action="{{ route('preferenze.consensi', $persona->token) }}" class="space-y-3 text-sm">
            @csrf
            @foreach (\App\Models\Persona::FINALITA as $finalita)
                <label class="flex items-start gap-2">
                    <input type="checkbox" name="consenso_{{ $finalita }}" value="1" class="mt-1" @checked($persona->haConsenso($finalita))>
                    <span>{{ config("consensi.{$finalita}.testo") }}</span>
                </label>
            @endforeach

            <button type="submit" class="bg-[#cc0000] text-white font-semibold px-5 py-2 rounded hover:bg-[#a30000]">{{ $daConfermare ? 'Conferma presenza e salva' : 'Salva le mie scelte' }}</button>
        </form>
    </section>

    <section class="border border-gray-200 rounded p-5">
        <h2 class="font-semibold text-gray-900 mb-1">I tuoi dati</h2>
        @if ($persona->richiesta_cancellazione_at)
            <p class="text-sm text-gray-600">Hai chiesto la cancellazione dei tuoi dati il {{ $persona->richiesta_cancellazione_at->format('d/m/Y') }}. Il comitato la valuterà e ti ricontatterà se servono chiarimenti. Nel frattempo non ti invieremo altre comunicazioni facoltative.</p>
        @else
            <p class="text-sm text-gray-600 mb-3">
                Puoi chiedere in qualsiasi momento di cancellare i tuoi dati dai nostri archivi. Alcuni dati, come quelli di fatturazione
                di fatture già emesse, vanno conservati per obbligo di legge. Vedi l'<a href="{{ route('privacy-policy.show', 'corsi-popolazione') }}" class="underline text-[#cc0000]">informativa privacy</a>.
            </p>
            <form method="POST" action="{{ route('preferenze.cancellazione', $persona->token) }}"
                onsubmit="return confirm('Vuoi chiedere la cancellazione dei tuoi dati? I consensi verranno revocati subito.');">
                @csrf
                <button type="submit" class="border border-gray-300 text-gray-700 px-5 py-2 rounded text-sm hover:bg-gray-50">Chiedi la cancellazione dei miei dati</button>
            </form>
        @endif
    </section>
    @endif
</div>
@endsection
