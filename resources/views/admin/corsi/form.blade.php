@extends('adminlte::page')

@php
    $editing = $corso->exists;
    $dateBloccate = $editing && ($corso->annullato() || $corso->chiuso);
@endphp

@section('title', $editing ? 'Modifica corso' : 'Nuovo corso')

@section('content_header')
    <h1>{{ $editing ? 'Modifica corso' : 'Nuovo corso' }}</h1>
@stop

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/flatpickr/flatpickr.min.css') }}">
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

    @if ($editing)
        <p class="text-muted">Protocollo: <code>{{ $corso->protocollo }}</code> (assegnato alla creazione, non modificabile)</p>
    @endif

    @if ($editing && $corso->annullato())
        <div class="alert alert-danger">
            <strong>Corso annullato</strong> il {{ $corso->annullato_at->format('d/m/Y H:i') }}
            da {{ $corso->annullatoDa?->name ?? '—' }}.<br>
            Motivo: {{ $corso->motivo_annullamento }}
            @if ($corso->puoRiattivare())
                <form method="POST" action="{{ route('admin.corsi.riattiva', $corso) }}" class="d-inline"
                    onsubmit="return confirm('Riattivare questo corso? Torna visibile e prenotabile normalmente.');">
                    @csrf @method('PUT')
                    <button type="submit" class="btn btn-sm btn-outline-danger ml-2">Riattiva corso</button>
                </form>
            @else
                <span class="d-block mt-1">La data del corso è passata: l'annullamento non è più reversibile.</span>
            @endif
        </div>
    @endif

    <form method="POST" action="{{ $editing ? route('admin.corsi.update', $corso) : route('admin.corsi.store') }}">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="card">
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Tipologia</label>
                        <select name="tipologia_corso_id" class="form-control" required @if($editing) disabled @endif
                            @unless($editing) onchange="document.getElementById('costo').value = this.options[this.selectedIndex].dataset.costo" @endunless>
                            @foreach ($tipologie as $tipologia)
                                <option value="{{ $tipologia->id }}" data-costo="{{ $tipologia->costo_predefinito }}"
                                    @selected(old('tipologia_corso_id', $corso->tipologia_corso_id) == $tipologia->id)>{{ $tipologia->nome }} ({{ $tipologia->sigla }})</option>
                            @endforeach
                        </select>
                        @unless($editing)
                            <small class="text-muted">Cambiando tipologia il costo si precompila da quello predefinito — resta comunque modificabile.</small>
                        @endunless
                        @if ($editing)
                            {{-- disabled non invia il valore: lo riproponiamo hidden per non perderlo in update --}}
                            <input type="hidden" name="tipologia_corso_id" value="{{ $corso->tipologia_corso_id }}">
                            <small class="text-muted">La tipologia non è modificabile dopo la creazione (il protocollo è già assegnato).</small>
                        @endif
                    </div>
                    <div class="form-group col-md-6">
                        @php
                            $sedeSceltaAttuale = old('sede_scelta', $corso->usa_indirizzo_comitato ? 'comitato' : (string) $corso->sede_corso_id);
                        @endphp
                        <label>Sede</label>
                        <select name="sede_scelta" id="sede_scelta" class="form-control"
                            onchange="document.getElementById('nuova-sede-campi').hidden = (this.value !== 'nuova')">
                            <option value="">— Nessuna —</option>
                            <option value="comitato" @selected($sedeSceltaAttuale === 'comitato')>
                                Sede del comitato{{ $committeeInfo->indirizzo ? ' — '.$committeeInfo->indirizzo : '' }}
                            </option>
                            @foreach ($sedi as $sede)
                                <option value="{{ $sede->id }}" @selected($sedeSceltaAttuale === (string) $sede->id)>{{ $sede->nome }}</option>
                            @endforeach
                            <option value="nuova" @selected($sedeSceltaAttuale === 'nuova')>+ Nuova sede…</option>
                        </select>

                        <div id="nuova-sede-campi" class="border rounded p-3 mt-2" @if($sedeSceltaAttuale !== 'nuova') hidden @endif>
                            <div class="form-group mb-2">
                                <label class="small mb-1">Nome sede</label>
                                <input name="nuova_sede[nome]" value="{{ old('nuova_sede.nome') }}" class="form-control form-control-sm">
                            </div>
                            <div class="form-row">
                                <div class="form-group col-6 mb-0">
                                    <label class="small mb-1">Via / largo / piazza / località</label>
                                    <input name="nuova_sede[via]" value="{{ old('nuova_sede.via') }}" class="form-control form-control-sm">
                                </div>
                                <div class="form-group col-3 mb-0">
                                    <label class="small mb-1">Comune</label>
                                    <input name="nuova_sede[comune]" value="{{ old('nuova_sede.comune') }}" class="form-control form-control-sm">
                                </div>
                                <div class="form-group col-1 mb-0">
                                    <label class="small mb-1">Prov.</label>
                                    <input name="nuova_sede[provincia]" value="{{ old('nuova_sede.provincia') }}" maxlength="2" class="form-control form-control-sm">
                                </div>
                                <div class="form-group col-2 mb-0">
                                    <label class="small mb-1">CAP</label>
                                    <input name="nuova_sede[cap]" value="{{ old('nuova_sede.cap') }}" maxlength="5" class="form-control form-control-sm">
                                </div>
                            </div>
                            <small class="text-muted d-block mt-2">Salvata al momento della creazione/modifica del corso, resterà poi selezionabile anche per i corsi futuri.</small>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label>Inizio</label>
                        <input name="data_inizio" class="form-control flatpickr-datetime" autocomplete="off"
                            value="{{ old('data_inizio', optional($corso->data_inizio)->format('Y-m-d H:i')) }}" required
                            @if($dateBloccate) disabled @endif>
                        @if ($dateBloccate)
                            <input type="hidden" name="data_inizio" value="{{ $corso->data_inizio->format('Y-m-d H:i') }}">
                        @endif
                    </div>
                    <div class="form-group col-md-3">
                        <label>Fine</label>
                        <input name="data_fine" class="form-control flatpickr-datetime" autocomplete="off"
                            value="{{ old('data_fine', optional($corso->data_fine)->format('Y-m-d H:i')) }}" required
                            @if($dateBloccate) disabled @endif>
                        @if ($dateBloccate)
                            <input type="hidden" name="data_fine" value="{{ $corso->data_fine->format('Y-m-d H:i') }}">
                        @endif
                        @if ($dateBloccate)
                            <small class="text-muted">Non modificabile: il corso è {{ $corso->annullato() ? 'annullato' : 'chiuso' }}.</small>
                        @endif
                    </div>
                    <div class="form-group col-md-3">
                        <label>Costo (€)</label>
                        <input name="costo" id="costo" type="number" step="0.01" min="0" value="{{ old('costo', $corso->costo ?? 0) }}" class="form-control" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Posti massimi</label>
                        <input name="posti_max" type="number" min="1" value="{{ old('posti_max', $corso->posti_max) }}" class="form-control">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label>Chiusura iscrizioni <small class="text-muted">(il form pubblico si disattiva da solo dopo)</small></label>
                        <input name="iscrizioni_chiusura_at" class="form-control flatpickr-datetime" autocomplete="off"
                            value="{{ old('iscrizioni_chiusura_at', optional($corso->iscrizioni_chiusura_at)->format('Y-m-d H:i')) }}">
                    </div>
                </div>

                <div class="form-group">
                    <label>Descrizione <small class="text-muted">(mostrata nella pagina pubblica di iscrizione)</small></label>
                    <textarea name="descrizione" class="form-control" rows="4">{{ old('descrizione', $corso->descrizione) }}</textarea>
                </div>

                <div class="form-check">
                    <input type="checkbox" name="pubblicato" id="pubblicato" value="1" class="form-check-input"
                        @checked(old('pubblicato', $corso->pubblicato ?? true))>
                    <label class="form-check-label" for="pubblicato">Pubblicato (il link di iscrizione è raggiungibile)</label>
                </div>

                @if ($editing)
                    <hr>
                    <p class="mb-0">
                        Link pubblico di iscrizione:
                        <a href="{{ route('corsi.iscrizione.show', $corso) }}" target="_blank">{{ route('corsi.iscrizione.show', $corso) }}</a>
                    </p>
                @endif
            </div>

            <div class="card-footer d-flex justify-content-between">
                <div>
                    <button type="submit" class="btn btn-primary">{{ $editing ? 'Salva' : 'Crea corso' }}</button>
                    <a href="{{ route('admin.corsi.index') }}" class="btn btn-link">Torna all'elenco</a>
                </div>
                @if ($editing && ! $corso->annullato())
                    <div>
                        @if ($corso->chiuso)
                            <form method="POST" action="{{ route('admin.corsi.riapri', $corso) }}" class="d-inline">
                                @csrf @method('PUT')
                                <button type="submit" class="btn btn-outline-secondary">Riapri (gestione interna)</button>
                            </form>
                        @else
                            @php($puoChiudere = $corso->data_fine->isPast())
                            <form method="POST" action="{{ route('admin.corsi.chiudi', $corso) }}" class="d-inline">
                                @csrf @method('PUT')
                                <button type="submit" class="btn btn-outline-secondary" @disabled(! $puoChiudere)
                                    title="@unless($puoChiudere) Disponibile solo dopo la data di fine del corso @endunless">
                                    Chiudi (gestione interna)
                                </button>
                            </form>
                        @endif
                        <button type="button" class="btn btn-outline-danger" onclick="annullaCorso()">Annulla corso</button>
                    </div>
                @endif
            </div>
        </div>
    </form>

    @if ($editing && ! $corso->annullato())
        <form id="annulla-corso-form" method="POST" action="{{ route('admin.corsi.annulla', $corso) }}" hidden>
            @csrf @method('PUT')
            <input type="hidden" name="motivo" id="annulla-corso-motivo">
        </form>
        <script>
            function annullaCorso() {
                const motivo = prompt('Motivo dell\'annullamento (visibile anche nella pagina pubblica del corso):');
                if (motivo === null || motivo.trim() === '') return;
                document.getElementById('annulla-corso-motivo').value = motivo.trim();
                document.getElementById('annulla-corso-form').submit();
            }
        </script>
    @endif

    @push('js')
        <script src="{{ asset('vendor/flatpickr/flatpickr.min.js') }}"></script>
        <script src="{{ asset('vendor/flatpickr/flatpickr-it.js') }}"></script>
        <script>
            flatpickr.localize(flatpickr.l10ns.it);
            document.querySelectorAll('.flatpickr-datetime:not([disabled])').forEach(function (el) {
                // allowInput: senza, l'altInput generato da flatpickr è readonly — Bootstrap lo mostra
                // grigio come un campo disabilitato, anche se non lo è (bug reale segnalato).
                flatpickr(el, { enableTime: true, time_24hr: true, dateFormat: 'Y-m-d H:i', altInput: true, altFormat: 'd/m/Y H:i', allowInput: true });
            });
        </script>
    @endpush
@stop
