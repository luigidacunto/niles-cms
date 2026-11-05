@extends('adminlte::page')

@php $editing = $tipologia->exists; @endphp

@section('title', $editing ? 'Modifica tipologia' : 'Nuova tipologia')

@section('content_header')
    <h1>{{ $editing ? 'Modifica tipologia' : 'Nuova tipologia' }}</h1>
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

    <form method="POST" enctype="multipart/form-data" action="{{ $editing ? route('admin.corsi-tipologie.update', $tipologia) : route('admin.corsi-tipologie.store') }}">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="card">
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-8">
                        <label>Nome</label>
                        <input name="nome" value="{{ old('nome', $tipologia->nome) }}" class="form-control" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Sigla <x-admin.aiuto>Usata nel protocollo del corso, es. BLSD.</x-admin.aiuto></label>
                        <input name="sigla" value="{{ old('sigla', $tipologia->sigla) }}" class="form-control text-uppercase" required maxlength="20">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <div class="custom-control custom-switch mt-4">
                            <input type="checkbox" name="rilascia_attestato" id="rilascia_attestato" value="1" class="custom-control-input"
                                @checked(old('rilascia_attestato', $tipologia->rilascia_attestato))>
                            <label class="custom-control-label" for="rilascia_attestato">Rilascia attestato riconosciuto</label>
                        </div>
                    </div>
                    <div class="form-group col-md-4">
                        <div class="custom-control custom-switch mt-4">
                            <input type="checkbox" name="attivo" id="attivo" value="1" class="custom-control-input"
                                @checked(old('attivo', $tipologia->attivo ?? true))>
                            <label class="custom-control-label" for="attivo">Attiva (selezionabile nei nuovi corsi)</label>
                        </div>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Ordine</label>
                        <input name="order" type="number" min="0" value="{{ old('order', $tipologia->order ?? 0) }}" class="form-control">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-12">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" name="anagrafica_completa" id="anagrafica_completa" value="1" class="custom-control-input"
                                @checked(old('anagrafica_completa', $tipologia->anagrafica_completa))>
                            <label class="custom-control-label" for="anagrafica_completa">Richiesta anagrafica completa
                                <x-admin.aiuto>Agli iscritti si chiedono anche data e luogo di nascita (necessari per il rilascio dell'attestato) e il controllo del codice fiscale è più rigoroso: carattere di controllo, data di nascita e Italia/estero devono tornare con il codice. Lo si dice anche nel modulo di iscrizione.</x-admin.aiuto>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Costo predefinito (€) <x-admin.aiuto>Precompila il costo alla creazione di un nuovo corso; resta modificabile per il singolo corso.</x-admin.aiuto></label>
                        <input name="costo_predefinito" id="costo_predefinito" type="number" step="0.01" min="0" value="{{ old('costo_predefinito', $tipologia->costo_predefinito ?? 0) }}" class="form-control">
                    </div>
                    @include('admin.partials.iva-costo', ['ivaPrevista' => (bool) old('iva_prevista', $tipologia->iva_prevista ?? false), 'aliquota' => old('aliquota_iva', $tipologia->aliquota_iva ?? null), 'colonne' => 8, 'costoId' => 'costo_predefinito', 'costo' => old('costo_predefinito', $tipologia->costo_predefinito ?? 0)])
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Validità dell'attestato</label>
                        <select name="validita_tipo" class="form-control">
                            <option value="">Non scade</option>
                            @foreach (\App\Models\TipologiaCorso::VALIDITA_TIPI as $valore => $etichetta)
                                <option value="{{ $valore }}" @selected(old('validita_tipo', $tipologia->validita_tipo) === $valore)>{{ $etichetta }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <label>N</label>
                        <input name="validita_valore" type="number" min="1" max="99" value="{{ old('validita_valore', $tipologia->validita_valore) }}" class="form-control">
                    </div>
                    <div class="form-group col-md-6">
                        <label>&nbsp;<x-admin.aiuto>«Fine anno»: scade il 31/12 dopo N anni (corso a ottobre 2026, 2 anni → 31/12/2028). «Data precisa»: scade N mesi dopo la data del corso (24 mesi → stesso giorno, due anni dopo). Serve per i futuri promemoria.</x-admin.aiuto></label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Immagine <x-admin.aiuto>Usata da tutti i corsi di questa tipologia: elenco pubblico, pagina di iscrizione e anteprima sui social. Formato orizzontale consigliato (es. 1200×630). Max 5 MB.</x-admin.aiuto></label>
                    @if ($tipologia->immagine_url)
                        <div class="mb-2">
                            <img src="{{ $tipologia->immagine_url }}" alt="" style="max-height:140px" class="img-thumbnail">
                            <div class="form-check mt-1">
                                <input type="checkbox" name="rimuovi_immagine" id="rimuovi_immagine" value="1" class="form-check-input">
                                <label class="form-check-label" for="rimuovi_immagine">Rimuovi immagine</label>
                            </div>
                        </div>
                    @endif
                    <input type="file" name="immagine" accept="image/*" class="form-control-file">
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary">{{ $editing ? 'Salva' : 'Crea tipologia' }}</button>
                <a href="{{ route('admin.corsi-tipologie.index') }}" class="btn btn-link">Annulla</a>
            </div>
        </div>
    </form>
@stop
