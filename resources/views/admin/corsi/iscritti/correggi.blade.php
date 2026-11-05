@extends('adminlte::page')

@section('title', 'Correggi iscritto — '.$corso->protocollo)

@section('content_header')
    <h1>Correggi iscritto <small class="text-muted">{{ $corso->tipologia->nome }} — {{ $corso->protocollo }}</small></h1>
@stop

@php
    // Valore di un campo: input precedente (dopo un errore) oppure dato attuale.
    $n = fn ($campo) => old("nominativo.$campo", $iscrizione->{$campo});
    $f = fn ($campo) => old("fatturazione.$campo", $fatturazione?->{$campo});
    $tipo = $f('tipo');
@endphp

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="alert alert-info">
        Correzione di errori di inserimento (solo amministratori). Se cambi il <strong>codice fiscale</strong>, l'iscrizione passa alla persona
        che ha quel codice (se esiste già in anagrafica, altrimenti ne viene creata una): la persona precedente, se non ha altri
        riferimenti, si può poi eliminare da <a href="{{ route('admin.persone.show', $iscrizione->persona) }}">Persone</a>.
        Ogni modifica viene annotata nel registro dell'applicazione.
    </div>

    <form method="POST" action="{{ route('admin.corsi.iscritti.aggiorna', [$corso, $iscrizione]) }}">
        @csrf @method('PUT')

        <div class="card">
            <div class="card-header"><strong>Partecipante</strong></div>
            <div class="card-body">
                <div class="form-row">
                    @foreach (['nome' => 'Nome', 'cognome' => 'Cognome', 'email' => 'Email', 'telefono' => 'Telefono'] as $campo => $label)
                        <div class="form-group col-md-6">
                            <label>{{ $label }}</label>
                            <input name="nominativo[{{ $campo }}]" value="{{ $n($campo) }}" class="form-control" required>
                        </div>
                    @endforeach
                    <div class="form-group col-md-6">
                        <label>Codice fiscale</label>
                        <input name="nominativo[codice_fiscale]" value="{{ $n('codice_fiscale') }}" maxlength="16" class="form-control text-uppercase" required>
                    </div>
                </div>
                <p class="text-muted mb-2">Residenza</p>
                <div class="form-row">
                    <div class="form-group col-md-5"><label class="small">Via / largo / piazza / località</label><input name="nominativo[via]" value="{{ $n('via') }}" class="form-control" required></div>
                    <div class="form-group col-md-3"><label class="small">Comune</label><input name="nominativo[comune]" value="{{ $n('comune') }}" class="form-control" required></div>
                    <div class="form-group col-md-2"><label class="small">Prov.</label><input name="nominativo[provincia]" value="{{ $n('provincia') }}" maxlength="2" class="form-control" required></div>
                    <div class="form-group col-md-2"><label class="small">CAP</label><input name="nominativo[cap]" value="{{ $n('cap') }}" maxlength="5" class="form-control" required></div>
                </div>
                @if ($corso->richiedeAnagraficaCompleta())
                    @php
                        // Estero: lo Stato è salvato come nome; se è ancora in elenco si preseleziona, altrimenti «Altro» con il testo.
                        $statoId = $iscrizione->nascita_estero ? \App\Models\Stato::where('nome', $iscrizione->nascita_stato)->value('id') : null;
                    @endphp
                    @include('admin.partials.nascita-corso', [
                        'data' => old('nominativo.data_nascita', $iscrizione->data_nascita),
                        'luogo' => old('nominativo.nascita_luogo', ! $iscrizione->nascita_estero ? 'italia' : ($statoId ?: 'altro')),
                        'statoAltro' => old('nominativo.nascita_stato_altro', $statoId ? null : $iscrizione->nascita_stato),
                        'comune' => old('nominativo.nascita_comune', $iscrizione->nascita_comune),
                        'provincia' => old('nominativo.nascita_provincia', $iscrizione->nascita_provincia),
                    ])
                @endif
            </div>
        </div>

        <div class="card">
            @if ($fatturazione)
            <div class="card-header"><strong>Fatturazione e pagamento</strong></div>
            <div class="card-body">
                @if ($condivisaCon->isNotEmpty())
                    <div class="alert alert-warning">
                        <strong>Questi dati sono condivisi con {{ $condivisaCon->count() }} {{ $condivisaCon->count() === 1 ? 'altro iscritto' : 'altri iscritti' }}</strong>
                        ({{ $condivisaCon->map(fn ($i) => $i->nome.' '.$i->cognome)->join(', ') }}): ogni modifica qui sotto vale per tutti.
                    </div>
                @endif

                <div class="form-group">
                    @foreach (['privato' => 'Privato', 'azienda' => 'Azienda / associazione / libero professionista'] as $valore => $label)
                        <div class="form-check form-check-inline">
                            <input type="radio" name="fatturazione[tipo]" value="{{ $valore }}" id="tipo-{{ $valore }}" class="form-check-input" @checked($tipo === $valore)
                                onchange="document.getElementById('blocco-privato').hidden = this.value !== 'privato'; document.getElementById('blocco-azienda').hidden = this.value !== 'azienda';">
                            <label class="form-check-label" for="tipo-{{ $valore }}">{{ $label }}</label>
                        </div>
                    @endforeach
                </div>

                <div id="blocco-privato" @if ($tipo !== 'privato') hidden @endif>
                    <div class="form-row">
                        <div class="form-group col-md-6"><label class="small">Nome</label><input name="fatturazione[nome]" value="{{ $f('nome') }}" class="form-control"></div>
                        <div class="form-group col-md-6"><label class="small">Cognome</label><input name="fatturazione[cognome]" value="{{ $f('cognome') }}" class="form-control"></div>
                    </div>
                </div>
                <div id="blocco-azienda" @if ($tipo !== 'azienda') hidden @endif>
                    <div class="form-row">
                        <div class="form-group col-md-6"><label class="small">Ragione sociale</label><input name="fatturazione[ragione_sociale]" value="{{ $f('ragione_sociale') }}" class="form-control"></div>
                        <div class="form-group col-md-3"><label class="small">Partita IVA</label><input name="fatturazione[partita_iva]" value="{{ $f('partita_iva') }}" maxlength="11" class="form-control"></div>
                        <div class="form-group col-md-3"><label class="small">Codice destinatario</label><input name="fatturazione[codice_destinatario]" value="{{ $f('codice_destinatario') }}" maxlength="7" class="form-control text-uppercase"></div>
                        <div class="form-group col-md-6"><label class="small">PEC</label><input type="email" name="fatturazione[pec]" value="{{ $f('pec') }}" class="form-control"></div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4"><label class="small">Codice fiscale</label><input name="fatturazione[codice_fiscale]" value="{{ $f('codice_fiscale') }}" maxlength="16" class="form-control text-uppercase"></div>
                    <div class="form-group col-md-4">
                        <label class="small">Metodo di pagamento</label>
                        <select name="fatturazione[metodo_pagamento]" class="form-control select-ricerca">
                            <option value="">—</option>
                            @foreach ($metodi as $metodo)
                                <option value="{{ $metodo }}" @selected($f('metodo_pagamento') === $metodo)>{{ $metodo }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-5"><label class="small">Via / largo / piazza / località</label><input name="fatturazione[via]" value="{{ $f('via') }}" class="form-control"></div>
                    <div class="form-group col-md-3"><label class="small">Comune</label><input name="fatturazione[comune]" value="{{ $f('comune') }}" class="form-control"></div>
                    <div class="form-group col-md-2"><label class="small">Prov.</label><input name="fatturazione[provincia]" value="{{ $f('provincia') }}" maxlength="2" class="form-control"></div>
                    <div class="form-group col-md-2"><label class="small">CAP</label><input name="fatturazione[cap]" value="{{ $f('cap') }}" maxlength="5" class="form-control"></div>
                </div>
                <small class="text-muted">Email e telefono di fatturazione non si modificano da qui.</small>
            </div>
            @endif
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Salva correzioni</button>
                <a href="{{ route('admin.corsi.iscritti.index', $corso) }}" class="btn btn-link">Annulla</a>
            </div>
        </div>
    </form>
@stop
