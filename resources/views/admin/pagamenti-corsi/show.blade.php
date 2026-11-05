@extends('adminlte::page')

@section('title', 'Pagamenti — '.$corso->protocollo)

@section('content_header')
    <h1>Pagamenti <small class="text-muted">{{ $corso->tipologia->nome }} — {{ $corso->protocollo }}</small></h1>
@stop

@php
    $euro = fn (int $cent) => number_format($cent / 100, 2, ',', '.').' €';
    $data = fn ($d) => $d?->format('d/m/Y');
@endphp

@section('content')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if (session('avviso'))
        <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> {{ session('avviso') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    @if ($senzaFatturazione)
        <div class="alert alert-warning">
            {{ $senzaFatturazione }} {{ $senzaFatturazione === 1 ? 'iscritto attivo non ha' : 'iscritti attivi non hanno' }} dati di fatturazione
            (iscritti quando il corso era gratuito): non compaiono qui sotto.
        </div>
    @endif

    <p class="text-muted">
        Quota {{ number_format($corso->costo, 2, ',', '.') }} € a persona. Ogni riga è un blocco di fatturazione, cioè una fattura: clic sulla riga per i dati e per registrare fattura e incasso.
    </p>

    <form method="GET" class="form-inline mb-3">
        <select name="stato" class="form-control mr-2" onchange="this.form.submit()">
            <option value="">Tutti</option>
            <option value="da_fatturare" @selected($stato === 'da_fatturare')>Da fatturare</option>
            <option value="da_incassare" @selected($stato === 'da_incassare')>Da incassare</option>
        </select>
        <a href="{{ route('admin.pagamenti-corsi.index') }}" class="btn btn-link">Tutti i corsi</a>
    </form>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Intestatario</th>
                        <th>Metodo</th>
                        <th>Per chi</th>
                        <th class="text-right">Totale</th>
                        <th>Fattura</th>
                        <th>Incasso</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($blocchi as $riga)
                        @php
                            $b = $riga['blocco'];
                            $stIncasso = $b->statoIncasso($riga['totale_cent']);
                        @endphp
                        <tr style="cursor:pointer" data-toggle="modal" data-target="#blocco-{{ $b->id }}">
                            <td>{{ $b->intestatario() }}</td>
                            <td>{{ $b->metodo_pagamento ?: '—' }}</td>
                            <td>{{ $riga['attivi']->map(fn ($i) => $i->nome.' '.$i->cognome)->join(', ') ?: '—' }}</td>
                            <td class="text-right">{{ $euro($riga['totale_cent']) }}</td>
                            <td>
                                @if ($b->fatturaEmessa())
                                    <span class="badge badge-success">n. {{ $b->fattura_numero }}</span>
                                @else
                                    <span class="badge badge-warning">Da emettere</span>
                                @endif
                                @if ($b->ricevutaEmessa()) <span class="badge badge-info" title="Ricevuta n. {{ $b->ricevuta_numero }}">Ricevuta</span> @endif
                            </td>
                            <td>
                                @if ($stIncasso === 'incassata') <span class="badge badge-success">Incassata</span>
                                @elseif ($stIncasso === 'parziale') <span class="badge badge-danger">Parziale {{ number_format($b->incassato_importo, 2, ',', '.') }} €</span>
                                @else <span class="badge badge-warning">Da incassare</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Nessun blocco di fatturazione.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @foreach ($blocchi as $riga)
        @php
            $b = $riga['blocco'];
            $totale = $riga['totale_cent'];
            $quotaCent = (int) round($corso->costo * 100);
            $aliquota = $corso->iva_prevista && $corso->aliquota_iva ? (float) $corso->aliquota_iva : null;
            $scorporo = $aliquota ? \App\Support\IvaCorso::scorpora($quotaCent, $aliquota) : null;
            $aliquotaTesto = $aliquota ? \App\Support\IvaCorso::aliquotaTesto($aliquota) : null;
            $descrizione = fn ($i) => 'Corso '.$corso->protocollo.' del '.$corso->data_inizio->format('d/m/Y').' per '.$i->nome.' '.$i->cognome;
            $dettaglioImporto = $scorporo ? 'imponibile '.$euro($scorporo['imponibile']).' + IVA '.$aliquotaTesto.' '.$euro($scorporo['iva']).' = '.$euro($quotaCent) : $euro($quotaCent);
            $n = $riga['attivi']->count();
            $totaleTesto = $scorporo ? 'Totale: imponibile '.$euro($scorporo['imponibile'] * $n).' + IVA '.$aliquotaTesto.' '.$euro($scorporo['iva'] * $n).' = '.$euro($totale) : 'Totale: '.$euro($totale);
            $testoGuida = $riga['attivi']->map(fn ($i) => $descrizione($i).' — '.$dettaglioImporto)->push($totaleTesto)->join(PHP_EOL);
            $apertoPerErrori = (int) old('_blocco') === $b->id;
            // Campi della fattura elettronica (FatturaPA): privato = cognome e nome separati, azienda = denominazione. Senza codice
            // destinatario vale 0000000 (la fattura va nel cassetto fiscale del cliente). Quarto elemento = necessario alla fattura:
            // se vuoto resta visibile come «mancante»; i facoltativi vuoti si nascondono.
            $azienda = $b->tipo === 'azienda';
            $senzaPivaNeCf = $azienda && blank($b->partita_iva) && blank($b->codice_fiscale);
            $campiDati = collect([
                ['Tipo', $azienda ? 'Azienda / associazione / libero professionista' : 'Privato', null, true],
                ...($azienda ? [['Denominazione', $b->ragione_sociale, null, true]] : [['Cognome', $b->cognome, null, true], ['Nome', $b->nome, null, true]]),
                ...($senzaPivaNeCf ? [['Partita IVA o codice fiscale', null, null, true]]
                    : [['Partita IVA', $b->partita_iva, null, false], ['Codice fiscale', $b->codice_fiscale, null, ! $azienda]]),
                ['Indirizzo', $b->via, null, true],
                ['CAP', $b->cap, null, true],
                ['Comune', $b->comune, null, true],
                ['Provincia', $b->provincia, null, true],
                ['Nazione', 'IT', null, true],
                ['Codice destinatario', $b->codice_destinatario ?: '0000000', $b->codice_destinatario ? null : 'predefinito: nessun codice indicato', true],
                ['PEC', $b->pec, null, false],
                ['Email', $b->email, null, false],
                ['Telefono', $b->telefono, null, false],
                ['Metodo di pagamento', $b->metodo_pagamento, null, false],
            ])->filter(fn ($c) => filled($c[1]) || $c[3])->values();
            $testoDati = $campiDati->filter(fn ($c) => filled($c[1]))->map(fn ($c) => $c[0].': '.$c[1])->join(PHP_EOL);
            $incassato = $apertoPerErrori ? (bool) old('incassato') : $b->incassato_at !== null;
        @endphp
        <div class="modal fade" id="blocco-{{ $b->id }}" tabindex="-1" role="dialog" @if($apertoPerErrori) data-riapri="1" @endif>
            <div class="modal-dialog modal-xl" role="document">
                <form method="POST" action="{{ route('admin.pagamenti-corsi.update', [$corso, $b]) }}" class="modal-content">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_blocco" value="{{ $b->id }}">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $b->intestatario() }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Chiudi"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <h6 class="text-muted d-flex justify-content-between">
                            <span>Dati di fatturazione <small>(da copiare nella fattura elettronica)</small></span>
                            <button type="button" class="btn btn-xs btn-outline-secondary copia" data-testo="{{ $testoDati }}"><i class="fas fa-copy"></i> Copia tutti</button>
                        </h6>
                        <table class="table table-sm table-borderless mb-3">
                            @foreach ($campiDati as [$etichetta, $valore, $nota])
                                <tr>
                                    <td class="text-muted" style="width: 25%">{{ $etichetta }}</td>
                                    @if (filled($valore))
                                        <td style="width: 1%"><button type="button" class="btn btn-xs btn-link copia py-0" data-testo="{{ $valore }}" title="Copia {{ $etichetta }}"><i class="fas fa-copy"></i><span class="sr-only">Copia {{ $etichetta }}</span></button></td>
                                        <td class="pl-0">{{ $valore }} @if ($nota) <small class="text-muted">({{ $nota }})</small> @endif</td>
                                    @else
                                        <td style="width: 1%"></td>
                                        <td class="pl-0 text-danger font-weight-bold">— mancante</td>
                                    @endif
                                </tr>
                            @endforeach
                        </table>

                        <h6 class="text-muted d-flex justify-content-between">
                            <span>Righe da inserire in fattura (guida)</span>
                            <button type="button" class="btn btn-xs btn-outline-secondary copia" data-testo="{{ $testoGuida }}"><i class="fas fa-copy"></i> Copia</button>
                        </h6>
                        <table class="table table-sm mb-3">
                            @if ($scorporo)
                                <tr class="text-muted small"><th>Descrizione</th><th class="text-right">Imponibile</th><th class="text-right">IVA {{ $aliquotaTesto }}</th><th class="text-right">Importo</th></tr>
                            @endif
                            @foreach ($riga['attivi'] as $i)
                                <tr>
                                    <td>@include('admin.pagamenti-corsi._copia', ['testo' => $descrizione($i), 'titolo' => 'descrizione']){{ $descrizione($i) }}</td>
                                    @if ($scorporo)
                                        <td class="text-right text-nowrap">@include('admin.pagamenti-corsi._copia', ['testo' => number_format($scorporo['imponibile'] / 100, 2, ',', ''), 'titolo' => 'imponibile']){{ $euro($scorporo['imponibile']) }}</td>
                                        <td class="text-right text-nowrap">{{ $euro($scorporo['iva']) }}</td>
                                    @endif
                                    <td class="text-right text-nowrap">@include('admin.pagamenti-corsi._copia', ['testo' => number_format($quotaCent / 100, 2, ',', ''), 'titolo' => 'importo']){{ $euro($quotaCent) }}</td>
                                </tr>
                            @endforeach
                            <tr class="font-weight-bold">
                                <td>Totale</td>
                                @if ($scorporo)
                                    <td class="text-right text-nowrap">{{ $euro($scorporo['imponibile'] * $n) }}</td>
                                    <td class="text-right text-nowrap">{{ $euro($scorporo['iva'] * $n) }}</td>
                                @endif
                                <td class="text-right text-nowrap">{{ $euro($totale) }}</td>
                            </tr>
                        </table>

                        <div class="form-row">
                            <div class="col-md-6">
                                <h6 class="text-muted d-flex justify-content-between"><span>Fattura</span><button type="button" class="btn btn-xs btn-link text-danger py-0 svuota">Svuota</button></h6>
                                <div class="form-row">
                                    <div class="form-group col-6"><label class="small">Numero</label><input name="fattura_numero" value="{{ $apertoPerErrori ? old('fattura_numero') : $b->fattura_numero }}" maxlength="50" class="form-control"></div>
                                    <div class="form-group col-6"><label class="small">Data</label><input type="date" name="fattura_data" value="{{ $apertoPerErrori ? old('fattura_data') : $b->fattura_data?->format('Y-m-d') }}" class="form-control"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted d-flex justify-content-between"><span>Ricevuta <small>(facoltativa, in più alla fattura)</small></span><button type="button" class="btn btn-xs btn-link text-danger py-0 svuota">Svuota</button></h6>
                                <div class="form-row">
                                    <div class="form-group col-6"><label class="small">Numero</label><input name="ricevuta_numero" value="{{ $apertoPerErrori ? old('ricevuta_numero') : $b->ricevuta_numero }}" maxlength="50" class="form-control"></div>
                                    <div class="form-group col-6"><label class="small">Data</label><input type="date" name="ricevuta_data" value="{{ $apertoPerErrori ? old('ricevuta_data') : $b->ricevuta_data?->format('Y-m-d') }}" class="form-control"></div>
                                </div>
                            </div>
                        </div>

                        <h6 class="text-muted">Incasso</h6>
                                        <div class="form-check mb-2">
                            <input type="checkbox" class="form-check-input incassato-check" id="incassato-{{ $b->id }}" name="incassato" value="1" @checked($incassato)>
                            <label class="form-check-label" for="incassato-{{ $b->id }}">Quota incassata</label>
                        </div>
                        <div class="form-row incasso-campi" @unless($incassato) hidden @endunless>
                            <div class="form-group col-md-4">
                                <label class="small">Importo incassato (€)</label>
                                <input type="number" step="0.01" min="0" name="incassato_importo" class="form-control incasso-importo" data-totale="{{ number_format($totale / 100, 2, '.', '') }}"
                                    value="{{ $apertoPerErrori ? old('incassato_importo') : ($b->incassato_importo ?? number_format($totale / 100, 2, '.', '')) }}">
                            </div>
                            <div class="form-group col-md-8">
                                <label class="small">Nota <span class="text-danger nota-obbligo" hidden>(obbligatoria: importo diverso dal totale)</span></label>
                                <input name="incasso_nota" maxlength="1000" class="form-control incasso-nota" value="{{ $apertoPerErrori ? old('incasso_nota') : $b->incasso_nota }}">
                            </div>
                        </div>

                        @if ($b->pagamento_registrato_at)
                            <small class="text-muted">Ultima registrazione: {{ $b->pagamento_registrato_at->format('d/m/Y H:i') }}{{ $b->registratoDa ? ' — '.$b->registratoDa->name : '' }}.</small>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link" data-dismiss="modal">Chiudi</button>
                        <button type="submit" class="btn btn-primary">Salva</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@stop

@push('js')
<script>
    // Nota obbligatoria (e segnalata) quando l'importo incassato è diverso dal totale dovuto; i campi incasso compaiono con la spunta.
    document.querySelectorAll('.modal form').forEach(function (form) {
        var check = form.querySelector('.incassato-check');
        var campi = form.querySelector('.incasso-campi');
        var importo = form.querySelector('.incasso-importo');
        var nota = form.querySelector('.incasso-nota');
        var obbligo = form.querySelector('.nota-obbligo');
        function aggiorna() {
            campi.hidden = !check.checked;
            importo.required = check.checked;
            var diverso = check.checked && importo.value !== '' && Math.round(parseFloat(importo.value) * 100) !== Math.round(parseFloat(importo.dataset.totale) * 100);
            nota.required = diverso;
            obbligo.hidden = !diverso;
        }
        check.addEventListener('change', aggiorna);
        importo.addEventListener('input', aggiorna);
        aggiorna();
    });
    // Pulsanti «copia» (dati di fatturazione, guida alla fattura): copiano data-testo e mostrano per un attimo la spunta.
    document.querySelectorAll('.copia').forEach(function (btn) {
        btn.addEventListener('click', function () {
            navigator.clipboard.writeText(btn.dataset.testo);
            var icona = btn.querySelector('i'), prima = icona.className;
            icona.className = 'fas fa-check text-success';
            setTimeout(function () { icona.className = prima; }, 1200);
        });
    });
    // «Svuota» azzera numero e data del documento (fattura o ricevuta) per correggere un inserimento sbagliato.
    document.querySelectorAll('.svuota').forEach(function (btn) {
        btn.addEventListener('click', function () {
            btn.closest('.col-md-6').querySelectorAll('input').forEach(function (i) { i.value = ''; });
        });
    });
    var daRiaprire = document.querySelector('.modal[data-riapri]');
    if (daRiaprire) { $(daRiaprire).modal('show'); }
</script>
@endpush
