@extends('layouts.public')

@section('title', 'I miei dati — '.config('app.public_name'))

@section('noindex', '1')

@section('content')
    <div class="max-w-2xl mx-auto px-4 py-10">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">I miei dati</h1>

        <dl class="divide-y divide-gray-200 border-y border-gray-200 text-sm">
            @php
                $righe = [
                    'Cognome' => $member->cognome,
                    'Nome' => $member->nome,
                    'Ruolo' => $member->ruoloLabel(),
                    'Codice fiscale' => $member->codice_fiscale,
                    'Data di nascita' => $member->data_nascita?->format('d/m/Y'),
                    'Email' => $member->email,
                    'Telefono' => $member->telefono,
                    'Altri numeri' => $member->telefoni_aggiuntivi ? implode(', ', $member->telefoni_aggiuntivi) : null,
                ];
            @endphp
            @foreach ($righe as $etichetta => $valore)
                <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="font-medium text-gray-500">{{ $etichetta }}</dt>
                    <dd class="mt-1 sm:mt-0 sm:col-span-2 text-gray-900">{{ $valore ?: '—' }}</dd>
                </div>
            @endforeach
        </dl>

        <p class="mt-6 text-sm text-gray-500">
            Per correggere i tuoi dati rivolgiti alla segreteria del comitato. Finché sei socio alcuni dati sono
            conservati per obbligo di legge e non possono essere cancellati. Maggiori dettagli nell'<a href="{{ route('privacy-policy.show', 'soci') }}" class="underline text-[#cc0000]">informativa privacy</a>.
        </p>

        <div class="mt-8 border-t border-gray-200 pt-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-2">Accesso all'area riservata</h2>
            @if ($member->richiesta_disattivazione_at)
                <p class="text-sm text-gray-700">
                    Hai chiesto la disattivazione dell'accesso il {{ $member->richiesta_disattivazione_at->format('d/m/Y') }}.
                    La richiesta verrà confermata da un amministratore del comitato.
                </p>
            @else
                <p class="text-sm text-gray-600 mb-3">Puoi chiedere in qualsiasi momento di disattivare il tuo accesso all'area riservata. La richiesta è confermata da un amministratore.</p>
                <form method="POST" action="{{ route('soci.profilo.disattivazione') }}"
                      onsubmit="return confirm('Chiedere la disattivazione del tuo accesso all\'area riservata?');">
                    @csrf
                    <button type="submit" class="rounded border border-gray-400 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        Richiedi la disattivazione dell'accesso
                    </button>
                </form>
            @endif
        </div>
    </div>
@endsection
