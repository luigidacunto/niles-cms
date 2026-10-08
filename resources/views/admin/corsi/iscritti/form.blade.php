@extends('adminlte::page')

@section('title', 'Nuovo iscritto — '.$corso->protocollo)

@section('content_header')
    <h1>Nuovo iscritto <small class="text-muted">{{ $corso->tipologia->nome }} — {{ $corso->protocollo }}</small></h1>
@stop

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <p class="text-muted">Per iscrizioni raccolte telefonicamente o di persona — stessi dati del modulo pubblico.</p>

    <form method="POST" action="{{ route('admin.corsi.iscritti.store', $corso) }}">
        @csrf

        <div class="card">
            <div class="card-header"><strong>Dati del partecipante</strong></div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Nome</label>
                        <input name="nominativo[nome]" value="{{ old('nominativo.nome') }}" class="form-control" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Cognome</label>
                        <input name="nominativo[cognome]" value="{{ old('nominativo.cognome') }}" class="form-control" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Email</label>
                        <input type="email" name="nominativo[email]" value="{{ old('nominativo.email') }}" class="form-control" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Telefono</label>
                        <input name="nominativo[telefono]" value="{{ old('nominativo.telefono') }}" class="form-control" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Codice fiscale</label>
                        <input name="nominativo[codice_fiscale]" value="{{ old('nominativo.codice_fiscale') }}" maxlength="16" class="form-control text-uppercase" required>
                    </div>
                </div>

                <p class="text-muted mb-2">Residenza</p>
                <div class="form-row">
                    <div class="form-group col-md-5">
                        <label class="small">Via / largo / piazza / località</label>
                        <input name="nominativo[via]" value="{{ old('nominativo.via') }}" class="form-control">
                    </div>
                    <div class="form-group col-md-3">
                        <label class="small">Comune</label>
                        <input name="nominativo[comune]" value="{{ old('nominativo.comune') }}" class="form-control">
                    </div>
                    <div class="form-group col-md-2">
                        <label class="small">Prov.</label>
                        <input name="nominativo[provincia]" value="{{ old('nominativo.provincia') }}" maxlength="2" class="form-control">
                    </div>
                    <div class="form-group col-md-2">
                        <label class="small">CAP</label>
                        <input name="nominativo[cap]" value="{{ old('nominativo.cap') }}" maxlength="5" class="form-control">
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><strong>Fatturazione</strong></div>
            <div class="card-body">
                @php($tipoAttuale = old('fatturazione.tipo', 'privato'))
                @php($modo = old('fatturazione_modo', 'stessi'))
                @php($sync = "var m = document.querySelector('[name=fatturazione_modo]:checked').value; var f = document.getElementById('fatturazione-completa'); f.disabled = f.hidden = m !== 'manuale'; var e = document.getElementById('fatturazione-esistente'); if (e) { e.disabled = e.hidden = m !== 'esistente'; } document.getElementById('metodo-pagamento').hidden = m === 'esistente';")
                <div class="mb-3">
                    <div class="form-check">
                        <input type="radio" name="fatturazione_modo" id="modo-stessi" value="stessi" class="form-check-input" @checked($modo === 'stessi') onchange="{{ $sync }}">
                        <label class="form-check-label" for="modo-stessi">Fatturazione al partecipante, con gli stessi dati indicati sopra</label>
                    </div>
                    @if ($fatturazioniEsistenti->isNotEmpty())
                        <div class="form-check">
                            <input type="radio" name="fatturazione_modo" id="modo-esistente" value="esistente" class="form-check-input" @checked($modo === 'esistente') onchange="{{ $sync }}">
                            <label class="form-check-label" for="modo-esistente">Aggiunto a un gruppo già inserito: usa una fatturazione esistente in questo corso</label>
                        </div>
                    @endif
                    <div class="form-check">
                        <input type="radio" name="fatturazione_modo" id="modo-manuale" value="manuale" class="form-check-input" @checked($modo === 'manuale') onchange="{{ $sync }}">
                        <label class="form-check-label" for="modo-manuale">Inserisci i dati di fatturazione a mano</label>
                    </div>
                </div>

                @if ($fatturazioniEsistenti->isNotEmpty())
                    <fieldset id="fatturazione-esistente" class="border-0 p-0 m-0 mb-3" @disabled($modo !== 'esistente') @if($modo !== 'esistente') hidden @endif>
                        <label class="small">Fatturazione esistente</label>
                        <select name="fatturazione_esistente_id" class="form-control">
                            @foreach ($fatturazioniEsistenti as $f)
                                <option value="{{ $f->id }}" @selected(old('fatturazione_esistente_id') == $f->id)>
                                    {{ $f->intestatario() }} — {{ $f->metodo_pagamento ?: 'metodo n/d' }} ({{ $f->iscrizioni_count }} {{ $f->iscrizioni_count === 1 ? 'iscritto' : 'iscritti' }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">L'iscritto condivide la stessa fatturazione (e metodo di pagamento) del gruppo.</small>
                    </fieldset>
                @endif

                {{-- fieldset disabled = i campi non vengono inviati (niente validazione sui campi nascosti). --}}
                <fieldset id="fatturazione-completa" class="border-0 p-0 m-0" @disabled($modo !== 'manuale') @if($modo !== 'manuale') hidden @endif>
                <div class="form-group">
                    <div class="form-check form-check-inline">
                        <input type="radio" name="fatturazione[tipo]" value="privato" id="tipo-privato" class="form-check-input"
                            @checked($tipoAttuale === 'privato') onchange="document.getElementById('blocco-privato').hidden = false; document.getElementById('blocco-azienda').hidden = true;">
                        <label class="form-check-label" for="tipo-privato">Privato</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input type="radio" name="fatturazione[tipo]" value="azienda" id="tipo-azienda" class="form-check-input"
                            @checked($tipoAttuale === 'azienda') onchange="document.getElementById('blocco-privato').hidden = true; document.getElementById('blocco-azienda').hidden = false;">
                        <label class="form-check-label" for="tipo-azienda">Azienda / associazione / libero professionista</label>
                    </div>
                </div>

                <div id="blocco-privato" @if($tipoAttuale !== 'privato') hidden @endif>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="small">Nome</label>
                            <input name="fatturazione[nome]" value="{{ old('fatturazione.nome') }}" class="form-control">
                        </div>
                        <div class="form-group col-md-6">
                            <label class="small">Cognome</label>
                            <input name="fatturazione[cognome]" value="{{ old('fatturazione.cognome') }}" class="form-control">
                        </div>
                    </div>
                </div>
                <div id="blocco-azienda" @if($tipoAttuale !== 'azienda') hidden @endif>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="small">Ragione sociale (o nome, se libero professionista)</label>
                            <input name="fatturazione[ragione_sociale]" value="{{ old('fatturazione.ragione_sociale') }}" class="form-control">
                        </div>
                        <div class="form-group col-md-3">
                            <label class="small">Partita IVA</label>
                            <input name="fatturazione[partita_iva]" value="{{ old('fatturazione.partita_iva') }}" maxlength="11" class="form-control">
                        </div>
                        <div class="form-group col-md-3">
                            <label class="small">Codice fiscale (se diverso, es. associazioni)</label>
                            <input name="fatturazione[codice_fiscale]" value="{{ old('fatturazione.codice_fiscale') }}" maxlength="16" class="form-control">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label class="small">Codice destinatario (fattura elettronica)</label>
                            <input name="fatturazione[codice_destinatario]" value="{{ old('fatturazione.codice_destinatario') }}" maxlength="7" class="form-control text-uppercase">
                        </div>
                        <div class="form-group col-md-4">
                            <label class="small">PEC</label>
                            <input type="email" name="fatturazione[pec]" value="{{ old('fatturazione.pec') }}" class="form-control">
                        </div>
                        <div class="form-group col-md-5">
                            <small class="text-muted d-block mt-4">Facoltativi: se mancano, la fattura va nel cassetto fiscale del cliente.</small>
                        </div>
                    </div>
                </div>

                <p class="text-muted mb-2">Indirizzo di fatturazione</p>
                <div class="form-row">
                    <div class="form-group col-md-5">
                        <label class="small">Via / largo / piazza / località</label>
                        <input name="fatturazione[via]" value="{{ old('fatturazione.via') }}" class="form-control">
                    </div>
                    <div class="form-group col-md-3">
                        <label class="small">Comune</label>
                        <input name="fatturazione[comune]" value="{{ old('fatturazione.comune') }}" class="form-control">
                    </div>
                    <div class="form-group col-md-2">
                        <label class="small">Prov.</label>
                        <input name="fatturazione[provincia]" value="{{ old('fatturazione.provincia') }}" maxlength="2" class="form-control">
                    </div>
                    <div class="form-group col-md-2">
                        <label class="small">CAP</label>
                        <input name="fatturazione[cap]" value="{{ old('fatturazione.cap') }}" maxlength="5" class="form-control">
                    </div>
                </div>
                </fieldset>

                <div id="metodo-pagamento" class="form-group col-md-4 pl-0" @if($modo === 'esistente') hidden @endif>
                    <label class="small">Metodo di pagamento</label>
                    <select name="fatturazione[metodo_pagamento]" class="form-control">
                        @foreach (\App\Models\CommitteeInfo::current()->metodi_pagamento as $metodo)
                            <option value="{{ $metodo }}">{{ $metodo }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="form-check mb-2">
                    <input type="checkbox" name="privacy_confermata" id="privacy_confermata" value="1" class="form-check-input" required
                        @checked(old('privacy_confermata'))>
                    <label class="form-check-label" for="privacy_confermata">
                        Confermo che il consenso privacy per l'iscrizione a questo corso è stato raccolto da questa persona. <span class="text-danger">(obbligatorio)</span>
                    </label>
                </div>
                <small class="text-muted d-block mt-2">I consensi per promemoria attestati e newsletter non si registrano da qui: la persona li sceglie dal link che riceverà via email.</small>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Aggiungi iscritto</button>
                <a href="{{ route('admin.corsi.iscritti.index', $corso) }}" class="btn btn-link">Torna all'elenco</a>
            </div>
        </div>
    </form>
@stop
