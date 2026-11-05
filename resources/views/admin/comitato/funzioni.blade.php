@extends('admin.comitato.layout', ['titolo' => 'Funzioni attive', 'azione' => route('admin.funzioni.update')])

@php
    // Interruttore a scorrimento (convenzione del pannello). Casella non spuntata = spenta (il controller usa $request->boolean).
    $interruttore = fn (string $campo, string $etichetta) => new \Illuminate\Support\HtmlString(
        '<div class="custom-control custom-switch">'
        .'<input type="checkbox" class="custom-control-input" id="'.$campo.'" name="'.$campo.'" value="1"'.(old($campo, $info->$campo) ? ' checked' : '').'>'
        .'<label class="custom-control-label" for="'.$campo.'">'.e($etichetta).'</label></div>'
    );
@endphp

@section('campi')
    <p class="text-muted">Parti del sito e del pannello che si possono accendere o spegnere. Spegnere una funzione non cancella nessun dato.</p>

    <div class="row">
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header"><strong>Area soci</strong></div>
                <div class="card-body">
                    <p class="text-muted">
                        Area riservata ai soci (accesso con codice via email, comunicazioni interne, documenti riservati, elenco membri).
                        <strong>Spenta</strong>: il pulsante nel sito, le pagine riservate e le relative voci del pannello scompaiono.
                        Riaccendendola si ritrova tutto com'era.
                    </p>
                    {{ $interruttore('area_soci_attiva', 'Area soci attiva') }}
                </div>
            </div>
            <div class="card">
                <div class="card-header"><strong>Pagamenti dei corsi</strong></div>
                <div class="card-body">
                    <p class="text-muted">
                        Per chi tiene la contabilità: per ogni corso a pagamento mostra i blocchi di fatturazione, con una guida alle righe
                        da inserire in fattura, e permette di registrare numero e data di fattura (e ricevuta) e l'incasso.
                        <strong>Spenta</strong>: la sezione e il relativo permesso degli utenti non hanno effetto.
                    </p>
                    {{ $interruttore('pagamenti_corsi_attivi', 'Gestione pagamenti dei corsi attiva') }}
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header"><strong>Statistiche</strong></div>
                <div class="card-body">
                    <p class="text-muted">
                        Interruttore rapido per spegnere temporaneamente uno strumento di statistica senza toccare il file di configurazione
                        del server. Ha effetto solo se lo strumento è già configurato lì.
                    </p>
                    <div class="form-group">
                        {{ $interruttore('goatcounter_enabled', 'GoatCounter attivo') }}
                        @unless (config('tracking.cookieless.src'))
                            <small class="text-muted d-block">Non configurato nel <code>.env</code> del server: l'interruttore non ha effetto finché non lo è.</small>
                        @endunless
                    </div>
                    <div class="form-group mb-0">
                        {{ $interruttore('ga_enabled', 'Google Analytics attivo') }}
                        @unless (config('tracking.ga.id'))
                            <small class="text-muted d-block">Non configurato nel <code>.env</code> del server: l'interruttore non ha effetto finché non lo è.</small>
                        @endunless
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
