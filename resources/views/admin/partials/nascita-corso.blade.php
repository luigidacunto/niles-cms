{{-- Data e luogo di nascita (tipologie con anagrafica completa, per l'attestato).
     $data (Carbon|stringa|null), $luogo ('italia' | id Stato | 'altro'), $statoAltro, $comune, $provincia. Estero: provincia fissa «EE» (la imposta anche il server). --}}
@php
    $dataValore = $data instanceof \Carbon\Carbon ? $data->format('Y-m-d') : $data;
    $estero = $luogo !== 'italia';
    $altro = $luogo === 'altro';
    $stati = \App\Models\Stato::elencoAttivi();
@endphp
<p class="text-muted mb-2">Dati di nascita
    <x-admin.aiuto>Richiesti per il rilascio dell'attestato (tipologia con anagrafica completa). Devono tornare con il codice fiscale: carattere di controllo, data di nascita e Italia/estero. Per chi è nato all'estero la provincia è «EE».</x-admin.aiuto>
</p>
<div class="form-row">
    <div class="form-group col-md-3">
        <label class="small">Data di nascita</label>
        <input type="date" name="nominativo[data_nascita]" value="{{ $dataValore }}" min="1900-01-02" max="{{ now()->toDateString() }}" class="form-control" required>
    </div>
    <div class="form-group col-md-3">
        <label class="small">Nato/a in</label>
        <select name="nominativo[nascita_luogo]" id="nascita_luogo" class="form-control select-ricerca" required
            onchange="var v = this.value, e = v !== 'italia', p = document.getElementById('nascita_provincia'); document.getElementById('nascita-stato-altro').hidden = v !== 'altro'; document.getElementById('nascita_stato_altro').required = v === 'altro'; p.readOnly = e; p.value = e ? 'EE' : '';">
            <option value="italia" @selected($luogo === 'italia')>Italia</option>
            @foreach ($stati as $s)
                <option value="{{ $s->id }}" @selected((string) $luogo === (string) $s->id)>{{ $s->nome }}</option>
            @endforeach
            <option value="altro" @selected($altro)>Altro (Stato non in elenco)</option>
        </select>
    </div>
    <div class="form-group col-md-3" id="nascita-stato-altro" @unless($altro) hidden @endunless>
        <label class="small">Stato di nascita</label>
        <input name="nominativo[nascita_stato_altro]" id="nascita_stato_altro" value="{{ $statoAltro }}" class="form-control" @required($altro)>
    </div>
    <div class="form-group col-md-3">
        <label class="small">Comune / città di nascita</label>
        <input name="nominativo[nascita_comune]" value="{{ $comune }}" class="form-control" required>
    </div>
    <div class="form-group col-md-2">
        <label class="small">Prov.</label>
        <input name="nominativo[nascita_provincia]" id="nascita_provincia" value="{{ $estero ? 'EE' : $provincia }}" maxlength="2" class="form-control text-uppercase" @readonly($estero) @required(! $estero)>
    </div>
</div>
